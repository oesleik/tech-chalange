# Lambda de autenticação: valida o CPF, confirma que o cliente existe no RDS e emite o JWT
# aceito pelas rotas protegidas do PHP. Acessada só via API Gateway (POST /autenticacao), por
# isso segue o mesmo toggle do apigateway.tf (não existe no MiniStack, assim como o RDS).

# Empacota src/ + node_modules (mysql2). Rode `npm ci --omit=dev` em lambda/autenticacao antes
# do plan; o workflow de deploy já faz isso.
data "archive_file" "autenticacao" {
  count       = local.api_gateway_enabled
  type        = "zip"
  source_dir  = "${path.module}/../lambda/autenticacao"
  output_path = "${path.module}/.terraform/build/autenticacao.zip"
  excludes    = ["test", "README.md", "package-lock.json", "node_modules/@types", "node_modules/undici-types"]
}

data "aws_iam_policy_document" "lambda_assume" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["lambda.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "autenticacao" {
  count              = local.api_gateway_enabled
  name               = "${var.cluster_name}-autenticacao"
  assume_role_policy = data.aws_iam_policy_document.lambda_assume.json
}

resource "aws_iam_role_policy_attachment" "autenticacao_logs" {
  count      = local.api_gateway_enabled
  role       = aws_iam_role.autenticacao[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole"
}

# Criar/remover as ENIs nas sub-redes privadas (vpc_config).
resource "aws_iam_role_policy_attachment" "autenticacao_vpc" {
  count      = local.api_gateway_enabled
  role       = aws_iam_role.autenticacao[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaVPCAccessExecutionRole"
}

# Sem regras de entrada; a saída é só para o MySQL. Sem NAT na sub-rede privada,
# a Lambda não alcança a internet (nem precisa: o JWT_SECRET e a senha vêm por env var).
resource "aws_security_group" "autenticacao" {
  count       = local.api_gateway_enabled
  name        = "${var.cluster_name}-autenticacao"
  description = "Lambda de autenticacao"
  vpc_id      = aws_vpc.this.id

  tags = {
    Name = "${var.cluster_name}-autenticacao"
  }
}

resource "aws_vpc_security_group_egress_rule" "autenticacao_to_rds" {
  count                        = local.api_gateway_enabled
  security_group_id            = aws_security_group.autenticacao[0].id
  description                  = "Lambda para o MySQL do RDS"
  referenced_security_group_id = aws_security_group.rds[0].id
  from_port                    = 3306
  to_port                      = 3306
  ip_protocol                  = "tcp"
}

# Criado antes da função para controlar a retenção (senão a Lambda cria sem expiração).
resource "aws_cloudwatch_log_group" "autenticacao" {
  count             = local.api_gateway_enabled
  name              = "/aws/lambda/${var.cluster_name}-autenticacao"
  retention_in_days = 7
}

resource "aws_lambda_function" "autenticacao" {
  count            = local.api_gateway_enabled
  function_name    = "${var.cluster_name}-autenticacao"
  role             = aws_iam_role.autenticacao[0].arn
  runtime          = "nodejs24.x"
  handler          = "src/index.handler"
  filename         = data.archive_file.autenticacao[0].output_path
  source_code_hash = data.archive_file.autenticacao[0].output_base64sha256
  architectures    = ["arm64"]
  memory_size      = 128
  # Cold start na VPC + handshake TLS com o RDS passam fácil dos 3s.
  timeout = 10

  vpc_config {
    subnet_ids         = aws_subnet.private[*].id
    security_group_ids = [aws_security_group.autenticacao[0].id]
  }

  environment {
    variables = {
      # Mesmas variáveis/valores do PHP (charts/tech-challenge/values.yaml), senão o token é rejeitado.
      JWT_SECRET = var.jwt_secret
      JWT_ISSUER = var.jwt_issuer
      JWT_TTL    = tostring(var.jwt_ttl)

      # Usuário somente leitura criado pelo job rds-user-setup do Helm.
      DB_HOST     = aws_db_instance.mysql[0].address
      DB_PORT     = tostring(aws_db_instance.mysql[0].port)
      DB_DATABASE = var.rds_db_name
      DB_USERNAME = var.lambda_db_username
      DB_PASSWORD = var.lambda_db_password
    }
  }

  logging_config {
    log_format = "Text"
    log_group  = aws_cloudwatch_log_group.autenticacao[0].name
  }

  lifecycle {
    precondition {
      condition     = var.jwt_secret != null
      error_message = "Defina jwt_secret (TF_VAR_jwt_secret) com o mesmo JWT_SECRET usado pelo PHP."
    }

    precondition {
      condition     = var.lambda_db_password != null
      error_message = "Defina lambda_db_password (TF_VAR_lambda_db_password) com o mesmo LAMBDA_DB_PASSWORD do job rds-user-setup."
    }
  }

  depends_on = [
    aws_iam_role_policy_attachment.autenticacao_logs,
    aws_iam_role_policy_attachment.autenticacao_vpc,
  ]
}

resource "aws_apigatewayv2_integration" "autenticacao" {
  count                  = local.api_gateway_enabled
  api_id                 = aws_apigatewayv2_api.this[0].id
  integration_type       = "AWS_PROXY"
  integration_uri        = aws_lambda_function.autenticacao[0].invoke_arn
  payload_format_version = "2.0"
}

# Rota específica tem precedência sobre o $default (que vai para o NLB/PHP).
resource "aws_apigatewayv2_route" "autenticacao" {
  count     = local.api_gateway_enabled
  api_id    = aws_apigatewayv2_api.this[0].id
  route_key = "POST /autenticacao"
  target    = "integrations/${aws_apigatewayv2_integration.autenticacao[0].id}"
}

resource "aws_lambda_permission" "autenticacao_apigw" {
  count         = local.api_gateway_enabled
  statement_id  = "AllowApiGatewayInvoke"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.autenticacao[0].function_name
  principal     = "apigateway.amazonaws.com"
  source_arn    = "${aws_apigatewayv2_api.this[0].execution_arn}/*/*/autenticacao"
}

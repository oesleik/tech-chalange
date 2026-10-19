# Lambda de autenticação: valida o CPF e emite o JWT aceito pelas rotas protegidas do PHP.
# Acessada só via API Gateway (POST /autenticacao), por isso segue o mesmo toggle do
# apigateway.tf (não existe no MiniStack).

data "archive_file" "autenticacao" {
  count       = local.api_gateway_enabled
  type        = "zip"
  source_dir  = "${path.module}/../lambda/autenticacao/src"
  output_path = "${path.module}/.terraform/build/autenticacao.zip"
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
  handler          = "index.handler"
  filename         = data.archive_file.autenticacao[0].output_path
  source_code_hash = data.archive_file.autenticacao[0].output_base64sha256
  architectures    = ["arm64"]
  memory_size      = 128
  timeout          = 3

  # Mesmas variáveis/valores do PHP (charts/tech-challenge/values.yaml), senão o token é rejeitado.
  environment {
    variables = {
      JWT_SECRET = var.jwt_secret
      JWT_ISSUER = var.jwt_issuer
      JWT_TTL    = tostring(var.jwt_ttl)
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
  }

  depends_on = [aws_iam_role_policy_attachment.autenticacao_logs]
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

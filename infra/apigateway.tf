locals {
  api_gateway_enabled = var.use_ministack ? 0 : 1
}

resource "aws_security_group" "vpc_link" {
  count       = local.api_gateway_enabled
  name        = "${var.cluster_name}-apigw-vpc-link"
  description = "ENIs do VPC Link do API Gateway"
  vpc_id      = aws_vpc.this.id

  tags = {
    Name = "${var.cluster_name}-apigw-vpc-link"
  }
}

resource "aws_security_group" "nlb" {
  count       = local.api_gateway_enabled
  name        = "${var.cluster_name}-nlb"
  description = "NLB interno na frente do NodePort"
  vpc_id      = aws_vpc.this.id

  tags = {
    Name = "${var.cluster_name}-nlb"
  }
}

resource "aws_vpc_security_group_egress_rule" "vpc_link_to_nlb" {
  count                        = local.api_gateway_enabled
  security_group_id            = aws_security_group.vpc_link[0].id
  description                  = "VPC Link para o NLB"
  referenced_security_group_id = aws_security_group.nlb[0].id
  from_port                    = 80
  ip_protocol                  = "tcp"
  to_port                      = 80
}

resource "aws_vpc_security_group_ingress_rule" "nlb_from_vpc_link" {
  count                        = local.api_gateway_enabled
  security_group_id            = aws_security_group.nlb[0].id
  description                  = "Somente o VPC Link do API Gateway"
  referenced_security_group_id = aws_security_group.vpc_link[0].id
  from_port                    = 80
  ip_protocol                  = "tcp"
  to_port                      = 80
}

resource "aws_vpc_security_group_egress_rule" "nlb_to_nodeport" {
  count                        = local.api_gateway_enabled
  security_group_id            = aws_security_group.nlb[0].id
  description                  = "NLB para o NodePort (trafego e health check)"
  referenced_security_group_id = aws_eks_cluster.this.vpc_config[0].cluster_security_group_id
  from_port                    = 30080
  ip_protocol                  = "tcp"
  to_port                      = 30080
}

resource "aws_lb" "api" {
  count              = local.api_gateway_enabled
  name               = "${var.cluster_name}-api"
  internal           = true
  load_balancer_type = "network"
  subnets            = aws_subnet.public[*].id
  security_groups    = [aws_security_group.nlb[0].id]

  enable_cross_zone_load_balancing = true
}

resource "aws_lb_target_group" "nodeport" {
  count       = local.api_gateway_enabled
  name        = "${var.cluster_name}-nodeport"
  port        = 30080
  protocol    = "TCP"
  target_type = "instance"
  vpc_id      = aws_vpc.this.id

  preserve_client_ip = false

  health_check {
    protocol = "HTTP"
    port     = "traffic-port"
    path     = "/nginx-health"
  }
}

resource "aws_lb_listener" "api" {
  count             = local.api_gateway_enabled
  load_balancer_arn = aws_lb.api[0].arn
  port              = 80
  protocol          = "TCP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.nodeport[0].arn
  }
}

resource "aws_autoscaling_attachment" "nodeport" {
  count                  = local.api_gateway_enabled
  autoscaling_group_name = aws_eks_node_group.this.resources[0].autoscaling_groups[0].name
  lb_target_group_arn    = aws_lb_target_group.nodeport[0].arn
}

resource "aws_apigatewayv2_vpc_link" "this" {
  count              = local.api_gateway_enabled
  name               = "${var.cluster_name}-vpc-link"
  subnet_ids         = aws_subnet.public[*].id
  security_group_ids = [aws_security_group.vpc_link[0].id]
}

resource "aws_apigatewayv2_api" "this" {
  count         = local.api_gateway_enabled
  name          = "${var.cluster_name}-api"
  protocol_type = "HTTP"
}

resource "aws_apigatewayv2_integration" "nlb" {
  count                  = local.api_gateway_enabled
  api_id                 = aws_apigatewayv2_api.this[0].id
  integration_type       = "HTTP_PROXY"
  integration_method     = "ANY"
  integration_uri        = aws_lb_listener.api[0].arn
  connection_type        = "VPC_LINK"
  connection_id          = aws_apigatewayv2_vpc_link.this[0].id
  payload_format_version = "1.0"
}

resource "aws_apigatewayv2_route" "default" {
  count     = local.api_gateway_enabled
  api_id    = aws_apigatewayv2_api.this[0].id
  route_key = "$default"
  target    = "integrations/${aws_apigatewayv2_integration.nlb[0].id}"
}

resource "aws_cloudwatch_log_group" "api_gateway" {
  count             = local.api_gateway_enabled
  name              = "/aws/apigateway/${var.cluster_name}-api"
  retention_in_days = 7
}

resource "aws_apigatewayv2_stage" "default" {
  count       = local.api_gateway_enabled
  api_id      = aws_apigatewayv2_api.this[0].id
  name        = "$default"
  auto_deploy = true

  access_log_settings {
    destination_arn = aws_cloudwatch_log_group.api_gateway[0].arn
    format = jsonencode({
      requestId          = "$context.requestId"
      ip                 = "$context.identity.sourceIp"
      requestTime        = "$context.requestTime"
      httpMethod         = "$context.httpMethod"
      path               = "$context.path"
      status             = "$context.status"
      responseLength     = "$context.responseLength"
      responseLatency    = "$context.responseLatency"
      integrationLatency = "$context.integrationLatency"
      integrationStatus  = "$context.integrationStatus"
      integrationError   = "$context.integrationErrorMessage"
    })
  }
}

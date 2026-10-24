provider "aws" {
  region                      = var.aws_region
  access_key                  = var.use_ministack ? var.aws_access_key : null
  secret_key                  = var.use_ministack ? var.aws_secret_key : null
  skip_credentials_validation = var.use_ministack
  skip_metadata_api_check     = var.use_ministack
  skip_requesting_account_id  = var.use_ministack
  skip_region_validation      = var.use_ministack
  s3_use_path_style           = var.use_ministack

  dynamic "endpoints" {
    for_each = var.use_ministack ? [var.ministack_endpoint] : []
    content {
      ec2 = endpoints.value
      eks = endpoints.value
      iam = endpoints.value
      sts = endpoints.value
    }
  }
}

# Sem account id (MiniStack, PR sem secrets) o provider recebe valores fictícios:
# ele só fala com o New Relic quando algum recurso newrelic_* existe. Key ausente com a
# integração ligada cai na precondition do vínculo (newrelic.tf).
provider "newrelic" {
  account_id = local.newrelic_enabled == 1 ? tonumber(trimspace(var.newrelic_account_id)) : 0
  api_key    = coalesce(var.newrelic_api_key, "disabled")
  region     = var.newrelic_region
}

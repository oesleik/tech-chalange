terraform {
  required_version = ">= 1.5.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    tls = {
      source  = "hashicorp/tls"
      version = "~> 4.0"
    }
  }

  # MiniStack: sem bloco backend -> state local (terraform.tfstate).
  # AWS/CI: o workflow gera backend.tf (S3) na hora do init. Ver docs/infrastructure/runbook_aws.md
}

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

  # MiniStack: Makefile gera backend.tf (local) + env/backend-ministack.hcl.
  # AWS/CI: o workflow gera backend.tf (S3). Ver docs/infrastructure/runbook_aws.md
}

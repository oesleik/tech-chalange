# Integração AWS do New Relic. Duas peças independentes:
# - API polling: role que o New Relic assume, vínculo da conta AWS no New Relic e as
#   integrações (RDS, EC2, Lambda, API Gateway). O destroy desfaz o vínculo.
# - Pipeline de logs: CloudWatch Logs -> subscription filter -> Firehose -> New Relic.
# Só existe na AWS e quando o account id é informado (TF_VAR_newrelic_account_id).

locals {
  # O secret ausente chega como "" pelo TF_VAR, não como null.
  newrelic_enabled = var.use_ministack || try(trimspace(var.newrelic_account_id), "") == "" ? 0 : 1
  newrelic_account = local.newrelic_enabled == 1 ? tonumber(trimspace(var.newrelic_account_id)) : 0

  # Log groups do CloudWatch encaminhados ao New Relic.
  newrelic_log_groups = local.newrelic_enabled == 1 ? {
    api_gateway  = aws_cloudwatch_log_group.api_gateway[0].name
    autenticacao = aws_cloudwatch_log_group.autenticacao[0].name
  } : {}
}

# --- API polling -------------------------------------------------------------

data "aws_iam_policy_document" "newrelic_integration_assume" {
  count = local.newrelic_enabled

  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${var.newrelic_aws_principal_account_id}:root"]
    }

    condition {
      test     = "StringEquals"
      variable = "sts:ExternalId"
      values   = [var.newrelic_account_id]
    }
  }
}

resource "aws_iam_role" "newrelic_integration" {
  count              = local.newrelic_enabled
  name               = "${var.cluster_name}-newrelic-integration"
  assume_role_policy = data.aws_iam_policy_document.newrelic_integration_assume[0].json
}

# Política recomendada pelo New Relic para o polling (leitura da conta inteira).
resource "aws_iam_role_policy_attachment" "newrelic_integration_read" {
  count      = local.newrelic_enabled
  role       = aws_iam_role.newrelic_integration[0].name
  policy_arn = "arn:aws:iam::aws:policy/ReadOnlyAccess"
}

# O New Relic assume a role ao vincular; role recém-criada demora a propagar no IAM.
resource "time_sleep" "newrelic_integration_iam" {
  count           = local.newrelic_enabled
  create_duration = "30s"

  depends_on = [aws_iam_role_policy_attachment.newrelic_integration_read]
}

resource "newrelic_cloud_aws_link_account" "this" {
  count                  = local.newrelic_enabled
  name                   = "${var.cluster_name}-aws"
  arn                    = aws_iam_role.newrelic_integration[0].arn
  metric_collection_mode = "PULL"

  lifecycle {
    precondition {
      condition     = var.newrelic_api_key != null && var.newrelic_api_key != ""
      error_message = "Defina newrelic_api_key (TF_VAR_newrelic_api_key) com uma User API key do New Relic."
    }
  }

  depends_on = [time_sleep.newrelic_integration_iam]
}

resource "newrelic_cloud_aws_integrations" "this" {
  count             = local.newrelic_enabled
  linked_account_id = newrelic_cloud_aws_link_account.this[0].id

  rds {
    aws_regions              = [var.aws_region]
    metrics_polling_interval = 300
  }

  ec2 {
    aws_regions              = [var.aws_region]
    metrics_polling_interval = 300
  }

  lambda {
    aws_regions              = [var.aws_region]
    metrics_polling_interval = 300
  }

  api_gateway {
    aws_regions              = [var.aws_region]
    metrics_polling_interval = 300
  }
}

# --- Logs via Firehose -------------------------------------------------------

# Só recebe o que o Firehose não conseguiu entregar ao New Relic.
resource "aws_s3_bucket" "newrelic_firehose_backup" {
  count         = local.newrelic_enabled
  bucket        = "${var.cluster_name}-${data.aws_caller_identity.current[0].account_id}-newrelic-firehose"
  force_destroy = true
}

resource "aws_s3_bucket_public_access_block" "newrelic_firehose_backup" {
  count                   = local.newrelic_enabled
  bucket                  = aws_s3_bucket.newrelic_firehose_backup[0].id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_lifecycle_configuration" "newrelic_firehose_backup" {
  count  = local.newrelic_enabled
  bucket = aws_s3_bucket.newrelic_firehose_backup[0].id

  rule {
    id     = "expire-failed-deliveries"
    status = "Enabled"

    filter {}

    expiration {
      days = 7
    }
  }
}

data "aws_iam_policy_document" "newrelic_firehose_assume" {
  count = local.newrelic_enabled

  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["firehose.amazonaws.com"]
    }

    condition {
      test     = "StringEquals"
      variable = "aws:SourceAccount"
      values   = [data.aws_caller_identity.current[0].account_id]
    }
  }
}

resource "aws_iam_role" "newrelic_firehose" {
  count              = local.newrelic_enabled
  name               = "${var.cluster_name}-newrelic-firehose"
  assume_role_policy = data.aws_iam_policy_document.newrelic_firehose_assume[0].json
}

data "aws_iam_policy_document" "newrelic_firehose_backup" {
  count = local.newrelic_enabled

  statement {
    actions = [
      "s3:AbortMultipartUpload",
      "s3:GetBucketLocation",
      "s3:GetObject",
      "s3:ListBucket",
      "s3:ListBucketMultipartUploads",
      "s3:PutObject",
    ]
    resources = [
      aws_s3_bucket.newrelic_firehose_backup[0].arn,
      "${aws_s3_bucket.newrelic_firehose_backup[0].arn}/*",
    ]
  }
}

resource "aws_iam_role_policy" "newrelic_firehose_backup" {
  count  = local.newrelic_enabled
  name   = "s3-backup"
  role   = aws_iam_role.newrelic_firehose[0].id
  policy = data.aws_iam_policy_document.newrelic_firehose_backup[0].json
}

resource "aws_kinesis_firehose_delivery_stream" "newrelic_logs" {
  count       = local.newrelic_enabled
  name        = "${var.cluster_name}-newrelic-logs"
  destination = "http_endpoint"

  http_endpoint_configuration {
    url                = var.newrelic_logs_endpoint
    name               = "New Relic"
    access_key         = var.newrelic_logs_ingest_key
    role_arn           = aws_iam_role.newrelic_firehose[0].arn
    buffering_size     = 1
    buffering_interval = 60
    retry_duration     = 60
    s3_backup_mode     = "FailedDataOnly"

    s3_configuration {
      role_arn           = aws_iam_role.newrelic_firehose[0].arn
      bucket_arn         = aws_s3_bucket.newrelic_firehose_backup[0].arn
      compression_format = "GZIP"
    }

    request_configuration {
      content_encoding = "GZIP"
    }
  }

  lifecycle {
    precondition {
      condition     = var.newrelic_logs_ingest_key != null && var.newrelic_logs_ingest_key != ""
      error_message = "Defina newrelic_logs_ingest_key (TF_VAR_newrelic_logs_ingest_key) com uma license key INGEST - LICENSE do New Relic."
    }
  }

  depends_on = [aws_iam_role_policy.newrelic_firehose_backup]
}

data "aws_iam_policy_document" "newrelic_logs_to_firehose_assume" {
  count = local.newrelic_enabled

  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["logs.amazonaws.com"]
    }

    condition {
      test     = "StringLike"
      variable = "aws:SourceArn"
      values   = ["arn:aws:logs:${var.aws_region}:${data.aws_caller_identity.current[0].account_id}:*"]
    }
  }
}

resource "aws_iam_role" "newrelic_logs_to_firehose" {
  count              = local.newrelic_enabled
  name               = "${var.cluster_name}-newrelic-logs-to-firehose"
  assume_role_policy = data.aws_iam_policy_document.newrelic_logs_to_firehose_assume[0].json
}

data "aws_iam_policy_document" "newrelic_logs_to_firehose" {
  count = local.newrelic_enabled

  statement {
    actions   = ["firehose:PutRecord", "firehose:PutRecordBatch"]
    resources = [aws_kinesis_firehose_delivery_stream.newrelic_logs[0].arn]
  }
}

resource "aws_iam_role_policy" "newrelic_logs_to_firehose" {
  count  = local.newrelic_enabled
  name   = "firehose-put"
  role   = aws_iam_role.newrelic_logs_to_firehose[0].id
  policy = data.aws_iam_policy_document.newrelic_logs_to_firehose[0].json
}

# Padrão vazio: encaminha todos os eventos do log group.
resource "aws_cloudwatch_log_subscription_filter" "newrelic" {
  for_each        = local.newrelic_log_groups
  name            = "${var.cluster_name}-newrelic"
  log_group_name  = each.value
  filter_pattern  = ""
  destination_arn = aws_kinesis_firehose_delivery_stream.newrelic_logs[0].arn
  role_arn        = aws_iam_role.newrelic_logs_to_firehose[0].arn

  depends_on = [aws_iam_role_policy.newrelic_logs_to_firehose]
}

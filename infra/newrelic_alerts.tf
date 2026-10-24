# Synthetic do /health, policy "tech-challenge" com as condições NRQL e notificação por
# e-mail (TF_VAR_newrelic_alert_emails, separados por vírgula). Sem e-mail, as condições
# abrem incidentes só no New Relic. Os alertas criados à mão no console ficam fora daqui.

locals {
  newrelic_alert_emails = compact([for email in split(",", var.newrelic_alert_emails) : trimspace(email)])
  newrelic_notify       = local.newrelic_enabled == 1 && length(local.newrelic_alert_emails) > 0 ? 1 : 0
}

# --- Synthetic ---------------------------------------------------------------

# Ping não conta na cota de checks sintéticos. Passa pelo API Gateway, NLB e pod.
resource "newrelic_synthetics_monitor" "health" {
  count                     = local.newrelic_enabled
  account_id                = local.newrelic_account
  name                      = local.newrelic_monitor_name
  type                      = "SIMPLE"
  status                    = "ENABLED"
  period                    = "EVERY_5_MINUTES"
  uri                       = "${aws_apigatewayv2_stage.default[0].invoke_url}health"
  locations_public          = ["US_EAST_1"]
  validation_string         = "\"status\":\"ok\""
  verify_ssl                = true
  bypass_head_request       = true
  treat_redirect_as_failure = true
}

# --- Policy ------------------------------------------------------------------

resource "newrelic_alert_policy" "tech_challenge" {
  count               = local.newrelic_enabled
  account_id          = local.newrelic_account
  name                = "tech-challenge"
  incident_preference = "PER_CONDITION_AND_TARGET"
}

# Aplicação: falha de OS vem do Transaction (rotas de OS); falha de integração, do
# TransactionError pela classe da exceção da integração.

resource "newrelic_nrql_alert_condition" "os_falha" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "Ordem de serviço: falha"
  description                  = "Rota de ordem de serviço terminou com exceção ou 5xx (evento Transaction do APM)."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 60
  aggregation_method           = "event_flow"
  aggregation_delay            = 120
  fill_option                  = "none"

  nrql {
    query = "FROM Transaction SELECT count(*) WHERE ${local.newrelic_where_os_tx} AND ${local.newrelic_where_falha} FACET name"
  }

  critical {
    operator              = "above"
    threshold             = 0
    threshold_duration    = 60
    threshold_occurrences = "at_least_once"
  }
}

resource "newrelic_nrql_alert_condition" "integracao_falha" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "Integração: falha"
  description                  = "Chamada a serviço externo falhou (TransactionError por classe da exceção, ex.: EmailException)."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 60
  aggregation_method           = "event_flow"
  aggregation_delay            = 120
  fill_option                  = "none"

  nrql {
    query = "FROM TransactionError SELECT count(*) WHERE ${local.newrelic_where_apm} AND ${local.newrelic_where_integ} FACET error.class"
  }

  critical {
    operator              = "above"
    threshold             = 0
    threshold_duration    = 60
    threshold_occurrences = "at_least_once"
  }
}

resource "newrelic_nrql_alert_condition" "api_5xx" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "API: taxa de 5xx (%)"
  description                  = "Percentual de transações com resposta 5xx."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 60
  aggregation_method           = "event_flow"
  aggregation_delay            = 120
  fill_option                  = "none"

  nrql {
    query = "FROM Transaction SELECT percentage(count(*), WHERE httpResponseCode LIKE '5%') WHERE ${local.newrelic_where_apm}"
  }

  critical {
    operator              = "above"
    threshold             = 5
    threshold_duration    = 300
    threshold_occurrences = "all"
  }

  warning {
    operator              = "above"
    threshold             = 1
    threshold_duration    = 300
    threshold_occurrences = "all"
  }
}

resource "newrelic_nrql_alert_condition" "api_latencia_p95" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "API: latência p95 (s)"
  description                  = "p95 da duração das transações (o /health é ignorado pelo APM)."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 60
  aggregation_method           = "event_flow"
  aggregation_delay            = 120
  fill_option                  = "none"

  nrql {
    query = "FROM Transaction SELECT percentile(duration, 95) WHERE ${local.newrelic_where_apm}"
  }

  critical {
    operator              = "above"
    threshold             = 2
    threshold_duration    = 300
    threshold_occurrences = "all"
  }

  warning {
    operator              = "above"
    threshold             = 1
    threshold_duration    = 300
    threshold_occurrences = "all"
  }
}

# Uptime: um check a cada 5 min. Com uma localização o percentual é 0 ou 100.
resource "newrelic_nrql_alert_condition" "health_synthetic" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "Uptime: /health falhando"
  description                  = "Synthetic ${local.newrelic_monitor_name} falhou (API Gateway, NLB, pod ou banco)."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 300
  aggregation_method           = "event_timer"
  aggregation_timer            = 60
  fill_option                  = "none"

  nrql {
    query = "FROM SyntheticCheck SELECT percentage(count(*), WHERE result != 'SUCCESS') WHERE monitorName = '${local.newrelic_monitor_name}'"
  }

  critical {
    operator              = "above"
    threshold             = 0
    threshold_duration    = 300
    threshold_occurrences = "at_least_once"
  }
}

# Kubernetes: cpuCoresUtilization e memoryWorkingSetUtilization são % do limit, e todos
# os containers do namespace têm limit (charts/tech-challenge/values-aws.yaml).

resource "newrelic_nrql_alert_condition" "container_cpu" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "Container: CPU (% do limit)"
  description                  = "Uso de CPU do container em relação ao limit. Acima do limit o container sofre throttling."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 60
  aggregation_method           = "event_flow"
  aggregation_delay            = 120
  fill_option                  = "none"

  nrql {
    query = "FROM K8sContainerSample SELECT average(cpuCoresUtilization) WHERE ${local.newrelic_where_k8s} FACET podName, containerName"
  }

  critical {
    operator              = "above"
    threshold             = 90
    threshold_duration    = 300
    threshold_occurrences = "all"
  }

  warning {
    operator              = "above"
    threshold             = 75
    threshold_duration    = 300
    threshold_occurrences = "all"
  }
}

resource "newrelic_nrql_alert_condition" "container_memoria" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "Container: memória (% do limit)"
  description                  = "Working set do container em relação ao limit. No limit o container é morto (OOMKilled)."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 60
  aggregation_method           = "event_flow"
  aggregation_delay            = 120
  fill_option                  = "none"

  nrql {
    query = "FROM K8sContainerSample SELECT average(memoryWorkingSetUtilization) WHERE ${local.newrelic_where_k8s} FACET podName, containerName"
  }

  critical {
    operator              = "above"
    threshold             = 90
    threshold_duration    = 300
    threshold_occurrences = "all"
  }

  warning {
    operator              = "above"
    threshold             = 80
    threshold_duration    = 300
    threshold_occurrences = "all"
  }
}

resource "newrelic_nrql_alert_condition" "node_cpu" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "Node: CPU alocável (%)"
  description                  = "CPU usada no node em relação à alocável."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 60
  aggregation_method           = "event_flow"
  aggregation_delay            = 120
  fill_option                  = "none"

  nrql {
    query = "FROM K8sNodeSample SELECT average(allocatableCpuCoresUtilization) WHERE ${local.newrelic_where_node} FACET nodeName"
  }

  critical {
    operator              = "above"
    threshold             = 90
    threshold_duration    = 300
    threshold_occurrences = "all"
  }

  warning {
    operator              = "above"
    threshold             = 80
    threshold_duration    = 300
    threshold_occurrences = "all"
  }
}

resource "newrelic_nrql_alert_condition" "node_memoria" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "Node: memória alocável (%)"
  description                  = "Memória usada no node em relação à alocável. Perto de 100% o kubelet começa a despejar pods."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 60
  aggregation_method           = "event_flow"
  aggregation_delay            = 120
  fill_option                  = "none"

  nrql {
    query = "FROM K8sNodeSample SELECT average(allocatableMemoryUtilization) WHERE ${local.newrelic_where_node} FACET nodeName"
  }

  critical {
    operator              = "above"
    threshold             = 90
    threshold_duration    = 300
    threshold_occurrences = "all"
  }

  warning {
    operator              = "above"
    threshold             = 80
    threshold_duration    = 300
    threshold_occurrences = "all"
  }
}

# isLimited = 1 quando o HPA quer escalar mas já está no maxReplicas: teto de capacidade.
resource "newrelic_nrql_alert_condition" "hpa_limitado" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "HPA: limitado no máximo de réplicas"
  description                  = "O HPA quer mais réplicas, mas já está no maxReplicas (charts/tech-challenge/values-aws.yaml)."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 60
  aggregation_method           = "event_flow"
  aggregation_delay            = 120
  fill_option                  = "none"

  nrql {
    query = "FROM K8sHpaSample SELECT max(isLimited) WHERE ${local.newrelic_where_k8s} FACET displayName"
  }

  critical {
    operator              = "above"
    threshold             = 0
    threshold_duration    = 600
    threshold_occurrences = "all"
  }
}

# RDS: vem do API polling do CloudWatch (5 min, com atraso), por isso event_timer.

resource "newrelic_nrql_alert_condition" "rds_cpu" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "RDS: CPU (%)"
  description                  = "CPU da instância ${var.cluster_name}-mysql."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 300
  aggregation_method           = "event_timer"
  aggregation_timer            = 300
  fill_option                  = "none"

  nrql {
    query = "FROM DatastoreSample SELECT average(provider.cpuUtilization.Average) WHERE ${local.newrelic_where_rds}"
  }

  critical {
    operator              = "above"
    threshold             = 80
    threshold_duration    = 900
    threshold_occurrences = "all"
  }
}

# A instância é pequena e opera com ~60 MiB livres (o MySQL ocupa o buffer pool). 30 MiB
# indica pressão real de memória sem disparar no estado normal.
resource "newrelic_nrql_alert_condition" "rds_memoria_livre" {
  count                        = local.newrelic_enabled
  account_id                   = local.newrelic_account
  policy_id                    = newrelic_alert_policy.tech_challenge[0].id
  type                         = "static"
  name                         = "RDS: memória livre (bytes)"
  description                  = "FreeableMemory mínima da instância ${var.cluster_name}-mysql abaixo de 30 MiB."
  enabled                      = true
  violation_time_limit_seconds = 86400
  aggregation_window           = 300
  aggregation_method           = "event_timer"
  aggregation_timer            = 300
  fill_option                  = "none"

  nrql {
    query = "FROM DatastoreSample SELECT min(provider.freeableMemory.Minimum) WHERE ${local.newrelic_where_rds}"
  }

  critical {
    operator              = "below"
    threshold             = 31457280
    threshold_duration    = 900
    threshold_occurrences = "all"
  }
}

# --- Notificação -------------------------------------------------------------

resource "newrelic_notification_destination" "email" {
  count      = local.newrelic_notify
  account_id = local.newrelic_account
  name       = "tech-challenge-email"
  type       = "EMAIL"

  property {
    key   = "email"
    value = join(",", local.newrelic_alert_emails)
  }
}

resource "newrelic_notification_channel" "email" {
  count          = local.newrelic_notify
  account_id     = local.newrelic_account
  name           = "tech-challenge-email"
  type           = "EMAIL"
  destination_id = newrelic_notification_destination.email[0].id
  product        = "IINT"

  property {
    key   = "subject"
    value = "[tech-challenge] {{issueTitle}}"
  }
}

resource "newrelic_workflow" "tech_challenge" {
  count                 = local.newrelic_notify
  account_id            = local.newrelic_account
  name                  = "tech-challenge"
  enabled               = true
  muting_rules_handling = "NOTIFY_ALL_ISSUES"

  issues_filter {
    name = "policy tech-challenge"
    type = "FILTER"

    predicate {
      attribute = "labels.policyIds"
      operator  = "EXACTLY_MATCHES"
      values    = [newrelic_alert_policy.tech_challenge[0].id]
    }
  }

  destination {
    channel_id            = newrelic_notification_channel.email[0].id
    notification_triggers = ["ACTIVATED", "ACKNOWLEDGED", "CLOSED"]
  }
}

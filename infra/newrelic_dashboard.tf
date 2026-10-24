# Dashboard "tech-challenge" no New Relic. Os nomes de métrica Custom/* são contrato com
# a aplicação (ver docs/observability/newrelic.md). Timeslice customizada chega em
# segundos; contagem é count(newrelic.timeslice.value), não sum.

locals {
  newrelic_app_name      = "tech-challenge-prod"
  newrelic_where_apm     = "appName = '${local.newrelic_app_name}'"
  newrelic_where_custom  = "appName = '${local.newrelic_app_name}' AND metricTimesliceName"
  newrelic_where_os_tx   = "appName = '${local.newrelic_app_name}' AND (name LIKE '%/ordens-servico%' OR name LIKE '%/consulta/ordem-servico%')"
  newrelic_where_falha   = "(error IS true OR httpResponseCode LIKE '5%')"
  newrelic_where_integ   = "error.class LIKE '%EmailException'"
  newrelic_where_k8s     = "clusterName = 'tech-challenge-prod' AND namespaceName = 'tech-challenge'"
  newrelic_where_node    = "clusterName = 'tech-challenge-prod'"
  newrelic_where_rds     = "provider = 'RdsDbInstance' AND entityName = '${var.cluster_name}-mysql'"
  newrelic_where_lambda  = "provider = 'LambdaFunction' AND entityName = '${var.cluster_name}-autenticacao'"
  newrelic_where_app_log = "newrelic.source = 'logs.APM' AND entity.name = '${local.newrelic_app_name}'"
  newrelic_where_gw_log  = "logGroup = '/aws/apigateway/${var.cluster_name}-api'"
  newrelic_where_fn_log  = "logGroup = '/aws/lambda/${var.cluster_name}-autenticacao'"
  newrelic_monitor_name  = "${var.cluster_name}-health"
}

resource "newrelic_one_dashboard" "tech_challenge" {
  count       = local.newrelic_enabled
  account_id  = local.newrelic_account
  name        = "tech-challenge"
  permissions = "public_read_only"

  # --- Visão geral -----------------------------------------------------------

  page {
    name = "Visão geral"

    widget_billboard {
      title  = "Throughput (rpm)"
      row    = 1
      column = 1
      width  = 2
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT rate(count(*), 1 minute) AS 'rpm' WHERE ${local.newrelic_where_apm}"
      }
    }

    widget_billboard {
      title    = "Taxa de erro (%)"
      row      = 1
      column   = 3
      width    = 2
      height   = 3
      warning  = 1
      critical = 5

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT percentage(count(*), WHERE error IS true) AS 'Erros' WHERE ${local.newrelic_where_apm}"
      }
    }

    widget_billboard {
      title    = "Latência p95 (s)"
      row      = 1
      column   = 5
      width    = 2
      height   = 3
      warning  = 1
      critical = 2

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT percentile(duration, 95) AS 'p95' WHERE ${local.newrelic_where_apm}"
      }
    }

    widget_billboard {
      title  = "Uptime /health (%)"
      row    = 1
      column = 7
      width  = 2
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM SyntheticCheck SELECT percentage(count(*), WHERE result = 'SUCCESS') AS 'Sucesso' WHERE monitorName = '${local.newrelic_monitor_name}'"
      }
    }

    widget_billboard {
      title  = "OS criadas"
      row    = 1
      column = 9
      width  = 2
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Metric SELECT count(newrelic.timeslice.value) AS 'Criadas' WHERE ${local.newrelic_where_custom} = 'Custom/OrdemServico/Criada'"
      }
    }

    widget_billboard {
      title    = "Falhas em OS"
      row      = 1
      column   = 11
      width    = 2
      height   = 3
      critical = 1

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT count(*) AS 'Falhas' WHERE ${local.newrelic_where_os_tx} AND ${local.newrelic_where_falha}"
      }
    }

    widget_line {
      title            = "Throughput e erros"
      row              = 4
      column           = 1
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT rate(count(*), 1 minute) AS 'rpm', rate(filter(count(*), WHERE error IS true), 1 minute) AS 'erros/min' WHERE ${local.newrelic_where_apm} TIMESERIES"
      }
    }

    widget_line {
      title            = "Latência (s)"
      row              = 4
      column           = 7
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT percentile(duration, 50, 95, 99) WHERE ${local.newrelic_where_apm} TIMESERIES"
      }

      # Mesmos limiares do alerta de p95 (newrelic_alerts.tf).
      threshold {
        name     = "Warning"
        from     = 1
        to       = 2
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 2
        to       = 1000
        severity = "critical"
      }
    }

    widget_table {
      title  = "Pods disponíveis"
      row    = 7
      column = 1
      width  = 4
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sDeploymentSample SELECT latest(podsAvailable) AS 'Disponíveis', latest(podsDesired) AS 'Desejados' WHERE ${local.newrelic_where_k8s} FACET deploymentName"
      }
    }

    widget_line {
      title            = "CPU dos containers (% do limit)"
      row              = 7
      column           = 5
      width            = 4
      height           = 3
      y_axis_left_zero = true
      y_axis_left_min  = 0
      y_axis_left_max  = 100

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sContainerSample SELECT average(cpuCoresUtilization) WHERE ${local.newrelic_where_k8s} FACET containerName TIMESERIES"
      }

      threshold {
        name     = "Warning"
        from     = 75
        to       = 90
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 90
        to       = 1000
        severity = "critical"
      }
    }

    widget_line {
      title            = "Memória dos containers (% do limit)"
      row              = 7
      column           = 9
      width            = 4
      height           = 3
      y_axis_left_zero = true
      y_axis_left_min  = 0
      y_axis_left_max  = 100

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sContainerSample SELECT average(memoryWorkingSetUtilization) WHERE ${local.newrelic_where_k8s} FACET containerName TIMESERIES"
      }

      threshold {
        name     = "Warning"
        from     = 80
        to       = 90
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 90
        to       = 1000
        severity = "critical"
      }
    }
  }

  # --- API -------------------------------------------------------------------

  page {
    name = "API"

    widget_line {
      title            = "Latência p50/p95/p99 (s)"
      row              = 1
      column           = 1
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT percentile(duration, 50, 95, 99) WHERE ${local.newrelic_where_apm} TIMESERIES"
      }

      # Mesmos limiares do alerta de p95 (newrelic_alerts.tf).
      threshold {
        name     = "Warning"
        from     = 1
        to       = 2
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 2
        to       = 1000
        severity = "critical"
      }
    }

    widget_line {
      title            = "Throughput (rpm)"
      row              = 1
      column           = 7
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT rate(count(*), 1 minute) AS 'rpm' WHERE ${local.newrelic_where_apm} TIMESERIES"
      }
    }

    widget_table {
      title  = "Por transação"
      row    = 4
      column = 1
      width  = 12
      height = 4

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT count(*) AS 'Requisições', percentile(duration, 50) AS 'p50 (s)', percentile(duration, 95) AS 'p95 (s)', percentile(duration, 99) AS 'p99 (s)', percentage(count(*), WHERE error IS true) AS 'Erro (%)' WHERE ${local.newrelic_where_apm} FACET name LIMIT 50"
      }
    }

    widget_line {
      title            = "Taxa de erro (%)"
      row              = 8
      column           = 1
      width            = 6
      height           = 3
      y_axis_left_zero = true
      y_axis_left_min  = 0
      y_axis_left_max  = 10

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT percentage(count(*), WHERE error IS true) AS 'Erros', percentage(count(*), WHERE httpResponseCode LIKE '5%') AS '5xx' WHERE ${local.newrelic_where_apm} TIMESERIES"
      }

      # Mesmos limiares do alerta de 5xx. Eixo baixo: o normal fica perto de 0%.
      threshold {
        name     = "Warning"
        from     = 1
        to       = 5
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 5
        to       = 1000
        severity = "critical"
      }
    }

    widget_stacked_bar {
      title  = "Status HTTP"
      row    = 8
      column = 7
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT count(*) WHERE ${local.newrelic_where_apm} FACET httpResponseCode TIMESERIES"
      }
    }

    # Integração + gateway = total (responseLatency). Linhas sem integração (integrationLatency "-",
    # ex.: erro do próprio gateway) ficam fora para as duas médias cobrirem as mesmas requisições.
    widget_stacked_bar {
      title  = "API Gateway: integração + gateway (ms)"
      row    = 11
      column = 1
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Log SELECT average(numeric(integrationLatency)) AS 'Integração', average(numeric(responseLatency)) - average(numeric(integrationLatency)) AS 'Gateway' WHERE ${local.newrelic_where_gw_log} AND path != '/health' AND integrationLatency != '-' TIMESERIES"
      }
    }

    widget_line {
      title            = "API Gateway: latência p50/p95/p99 (ms)"
      row              = 11
      column           = 7
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Log SELECT percentile(numeric(responseLatency), 50, 95, 99) WHERE ${local.newrelic_where_gw_log} AND path != '/health' TIMESERIES"
      }
    }
  }

  # --- Kubernetes ------------------------------------------------------------

  page {
    name = "Kubernetes"

    widget_line {
      title            = "CPU por container (% do limit)"
      row              = 1
      column           = 1
      width            = 6
      height           = 3
      y_axis_left_zero = true
      y_axis_left_min  = 0
      y_axis_left_max  = 100

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sContainerSample SELECT average(cpuCoresUtilization) WHERE ${local.newrelic_where_k8s} FACET podName, containerName TIMESERIES"
      }

      # Mesmos limiares dos alertas (newrelic_alerts.tf).
      threshold {
        name     = "Warning"
        from     = 75
        to       = 90
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 90
        to       = 1000
        severity = "critical"
      }
    }

    widget_line {
      title            = "Memória por container (% do limit)"
      row              = 1
      column           = 7
      width            = 6
      height           = 3
      y_axis_left_zero = true
      y_axis_left_min  = 0
      y_axis_left_max  = 100

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sContainerSample SELECT average(memoryWorkingSetUtilization) WHERE ${local.newrelic_where_k8s} FACET podName, containerName TIMESERIES"
      }

      threshold {
        name     = "Warning"
        from     = 80
        to       = 90
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 90
        to       = 1000
        severity = "critical"
      }
    }

    # HPA: réplicas atuais em % do maxReplicas, para comparar HPAs com máximos diferentes.
    # 100% = sem margem para escalar (o alerta de isLimited cobre ficar preso ali).
    widget_line {
      title            = "HPA: uso da capacidade (% do máximo)"
      row              = 4
      column           = 1
      width            = 6
      height           = 3
      y_axis_left_zero = true
      y_axis_left_min  = 0
      y_axis_left_max  = 100

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sHpaSample SELECT latest(currentReplicas) / latest(maxReplicas) * 100 WHERE ${local.newrelic_where_k8s} FACET displayName TIMESERIES"
      }

      threshold {
        name     = "Warning"
        from     = 75
        to       = 100
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 100
        to       = 1000
        severity = "critical"
      }
    }

    widget_table {
      title  = "HPA"
      row    = 4
      column = 7
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sHpaSample SELECT latest(currentReplicas) AS 'Atuais', latest(desiredReplicas) AS 'Desejadas', latest(minReplicas) AS 'Mínimo', latest(maxReplicas) AS 'Máximo', latest(isLimited) AS 'No limite' WHERE ${local.newrelic_where_k8s} FACET displayName"
      }
    }

    widget_line {
      title            = "CPU usada x request x limit (cores)"
      row              = 7
      column           = 1
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sContainerSample SELECT average(cpuUsedCores) AS 'Usada', latest(cpuRequestedCores) AS 'Request', latest(cpuLimitCores) AS 'Limit' WHERE ${local.newrelic_where_k8s} FACET containerName TIMESERIES"
      }
    }

    widget_line {
      title            = "Memória usada x limit (MiB)"
      row              = 7
      column           = 7
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sContainerSample SELECT average(memoryWorkingSetBytes) / 1048576 AS 'Working set', latest(memoryLimitBytes) / 1048576 AS 'Limit' WHERE ${local.newrelic_where_k8s} FACET containerName TIMESERIES"
      }
    }

    widget_line {
      title            = "Nodes: CPU alocável (%)"
      row              = 10
      column           = 1
      width            = 6
      height           = 3
      y_axis_left_zero = true
      y_axis_left_min  = 0
      y_axis_left_max  = 100

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sNodeSample SELECT average(allocatableCpuCoresUtilization) WHERE ${local.newrelic_where_node} FACET nodeName TIMESERIES"
      }

      threshold {
        name     = "Warning"
        from     = 80
        to       = 90
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 90
        to       = 1000
        severity = "critical"
      }
    }

    widget_line {
      title            = "Nodes: memória alocável (%)"
      row              = 10
      column           = 7
      width            = 6
      height           = 3
      y_axis_left_zero = true
      y_axis_left_min  = 0
      y_axis_left_max  = 100

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sNodeSample SELECT average(allocatableMemoryUtilization) WHERE ${local.newrelic_where_node} FACET nodeName TIMESERIES"
      }

      threshold {
        name     = "Warning"
        from     = 80
        to       = 90
        severity = "warning"
      }

      threshold {
        name     = "Critical"
        from     = 90
        to       = 1000
        severity = "critical"
      }
    }

    widget_table {
      title  = "Restarts por container"
      row    = 13
      column = 1
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sContainerSample SELECT latest(restartCount) AS 'Restarts', latest(status) AS 'Status' WHERE ${local.newrelic_where_k8s} FACET podName, containerName"
      }
    }

    widget_table {
      title  = "Deployments"
      row    = 13
      column = 7
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sDeploymentSample SELECT latest(podsAvailable) AS 'Disponíveis', latest(podsDesired) AS 'Desejados', latest(podsUnavailable) AS 'Indisponíveis' WHERE ${local.newrelic_where_k8s} FACET deploymentName"
      }
    }
  }

  # --- Saúde e uptime --------------------------------------------------------

  page {
    name = "Saúde e uptime"

    widget_billboard {
      title  = "Uptime /health (%)"
      row    = 1
      column = 1
      width  = 3
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM SyntheticCheck SELECT percentage(count(*), WHERE result = 'SUCCESS') AS 'Sucesso' WHERE monitorName = '${local.newrelic_monitor_name}'"
      }
    }

    widget_line {
      title            = "Synthetic: duração (ms)"
      row              = 1
      column           = 4
      width            = 5
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM SyntheticCheck SELECT average(duration) AS 'Média', max(duration) AS 'Máximo' WHERE monitorName = '${local.newrelic_monitor_name}' TIMESERIES"
      }
    }

    widget_table {
      title  = "Synthetic: falhas"
      row    = 1
      column = 9
      width  = 4
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM SyntheticCheck SELECT timestamp, locationLabel, result, error WHERE monitorName = '${local.newrelic_monitor_name}' AND result != 'SUCCESS' LIMIT 50"
      }
    }

    widget_line {
      title            = "Pods ready"
      row              = 4
      column           = 1
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sPodSample SELECT uniqueCount(podName) WHERE ${local.newrelic_where_k8s} AND status = 'Running' AND isReady = 1 FACET deploymentName TIMESERIES"
      }
    }

    widget_line {
      title            = "Restarts (acumulado)"
      row              = 4
      column           = 7
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM K8sContainerSample SELECT max(restartCount) WHERE ${local.newrelic_where_k8s} FACET containerName TIMESERIES"
      }
    }

    widget_stacked_bar {
      title  = "/health no API Gateway por status"
      row    = 7
      column = 1
      width  = 12
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Log SELECT count(*) WHERE ${local.newrelic_where_gw_log} AND path = '/health' FACET status TIMESERIES"
      }
    }
  }

  # --- Ordens de serviço -----------------------------------------------------

  page {
    name = "Ordens de serviço"

    widget_stacked_bar {
      title             = "OS criadas por dia (30 dias)"
      row               = 1
      column            = 1
      width             = 6
      height            = 3
      ignore_time_range = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Metric SELECT count(newrelic.timeslice.value) AS 'Criadas' WHERE ${local.newrelic_where_custom} = 'Custom/OrdemServico/Criada' SINCE 30 days ago TIMESERIES 1 day"
      }
    }

    # TempoNaSituacao/<situação> mede quanto a OS ficou na situação que acabou de deixar.
    widget_billboard {
      title  = "Tempo médio na situação (min)"
      row    = 1
      column = 7
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Metric SELECT filter(average(newrelic.timeslice.value), WHERE metricTimesliceName = 'Custom/OrdemServico/TempoNaSituacao/EmDiagnostico') / 60 AS 'Em diagnóstico', filter(average(newrelic.timeslice.value), WHERE metricTimesliceName = 'Custom/OrdemServico/TempoNaSituacao/EmExecucao') / 60 AS 'Em execução', filter(average(newrelic.timeslice.value), WHERE metricTimesliceName = 'Custom/OrdemServico/TempoNaSituacao/Finalizada') / 60 AS 'Finalizada' WHERE ${local.newrelic_where_apm}"
      }
    }

    widget_bar {
      title  = "Tempo médio por situação (min)"
      row    = 4
      column = 1
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Metric SELECT average(newrelic.timeslice.value) / 60 AS 'Minutos' WHERE ${local.newrelic_where_custom} LIKE 'Custom/OrdemServico/TempoNaSituacao/%' FACET aparse(metricTimesliceName, 'Custom/OrdemServico/TempoNaSituacao/*') AS 'Situação'"
      }
    }

    widget_stacked_bar {
      title  = "Transições por situação de destino"
      row    = 4
      column = 7
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Metric SELECT count(newrelic.timeslice.value) WHERE ${local.newrelic_where_custom} LIKE 'Custom/OrdemServico/Transicao/%' FACET aparse(metricTimesliceName, 'Custom/OrdemServico/Transicao/*') AS 'Situação' TIMESERIES"
      }
    }

    widget_stacked_bar {
      title  = "Falhas por rota"
      row    = 7
      column = 1
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT count(*) WHERE ${local.newrelic_where_os_tx} AND ${local.newrelic_where_falha} FACET name TIMESERIES"
      }
    }

    widget_table {
      title  = "Endpoints de OS"
      row    = 7
      column = 7
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Transaction SELECT count(*) AS 'Requisições', percentile(duration, 95) AS 'p95 (s)', percentage(count(*), WHERE error IS true) AS 'Erro (%)' WHERE ${local.newrelic_where_apm} AND name LIKE '%ordens-servico%' FACET name"
      }
    }
  }

  # --- Integrações -----------------------------------------------------------

  page {
    name = "Integrações"

    widget_line {
      title            = "RDS: CPU (%)"
      row              = 1
      column           = 1
      width            = 4
      height           = 3
      y_axis_left_zero = true
      y_axis_left_min  = 0
      y_axis_left_max  = 100

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM DatastoreSample SELECT average(provider.cpuUtilization.Average) AS 'Média', max(provider.cpuUtilization.Maximum) AS 'Máximo' WHERE ${local.newrelic_where_rds} TIMESERIES"
      }

      threshold {
        name     = "Critical"
        from     = 80
        to       = 100
        severity = "critical"
      }
    }

    widget_line {
      title            = "RDS: memória livre (MiB)"
      row              = 1
      column           = 5
      width            = 4
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM DatastoreSample SELECT average(provider.freeableMemory.Average) / 1048576 AS 'Média', min(provider.freeableMemory.Minimum) / 1048576 AS 'Mínimo' WHERE ${local.newrelic_where_rds} TIMESERIES"
      }

      threshold {
        name     = "Critical"
        from     = 0
        to       = 30
        severity = "critical"
      }
    }

    widget_line {
      title            = "RDS: conexões"
      row              = 1
      column           = 9
      width            = 4
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM DatastoreSample SELECT max(provider.databaseConnections.Maximum) AS 'Conexões' WHERE ${local.newrelic_where_rds} TIMESERIES"
      }
    }

    widget_line {
      title            = "Lambda autenticação: invocações e erros"
      row              = 4
      column           = 1
      width            = 4
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM ServerlessSample SELECT sum(provider.invocations.Sum) AS 'Invocações', sum(provider.errors.Sum) AS 'Erros', sum(provider.throttles.Sum) AS 'Throttles' WHERE ${local.newrelic_where_lambda} TIMESERIES"
      }
    }

    widget_line {
      title            = "Lambda autenticação: duração (ms)"
      row              = 4
      column           = 5
      width            = 4
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM ServerlessSample SELECT average(provider.duration.Average) AS 'Média', max(provider.duration.Maximum) AS 'Máximo' WHERE ${local.newrelic_where_lambda} TIMESERIES"
      }
    }

    widget_line {
      title            = "Lambda autenticação: erros no log"
      row              = 4
      column           = 9
      width            = 4
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Log SELECT count(*) AS 'Erros' WHERE ${local.newrelic_where_fn_log} AND (message LIKE '%ERROR%' OR message LIKE '%Task timed out%') TIMESERIES"
      }
    }

    widget_stacked_bar {
      title  = "API Gateway: 5xx e erros de integração"
      row    = 7
      column = 1
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Log SELECT filter(count(*), WHERE numeric(status) >= 500) AS '5xx', filter(count(*), WHERE integrationError IS NOT NULL AND integrationError != '-') AS 'Erro de integração' WHERE ${local.newrelic_where_gw_log} TIMESERIES"
      }
    }

    widget_stacked_bar {
      title  = "Falhas de integração (por exceção)"
      row    = 7
      column = 7
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM TransactionError SELECT count(*) WHERE ${local.newrelic_where_apm} AND ${local.newrelic_where_integ} FACET error.class TIMESERIES"
      }
    }

    widget_stacked_bar {
      title  = "Exceções por classe"
      row    = 10
      column = 1
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM TransactionError SELECT count(*) WHERE ${local.newrelic_where_apm} FACET error.class TIMESERIES"
      }
    }

    widget_table {
      title  = "Exceções recentes"
      row    = 10
      column = 7
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM TransactionError SELECT count(*) AS 'Ocorrências', latest(error.message) AS 'Mensagem' WHERE ${local.newrelic_where_apm} FACET error.class, transactionName"
      }
    }
  }

  # --- Logs ------------------------------------------------------------------

  page {
    name = "Logs"

    widget_markdown {
      title  = "Correlação de requisições"
      row    = 1
      column = 1
      width  = 12
      height = 1
      text   = "O API Gateway sobrescreve o header `X-Request-Id` com o `requestId` do access log. A aplicação grava esse valor em `request.id` na transação e em `context.request.id` no log. Para seguir uma requisição: copie o `requestId` da tabela do API Gateway e filtre os logs da aplicação por ele; o `trace.id` leva ao trace distribuído."
    }

    widget_log_table {
      title  = "Aplicação"
      row    = 2
      column = 1
      width  = 12
      height = 4

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Log SELECT level, message, trace.id, `context.request.id`, `context.http.route`, `context.http.status_code` WHERE ${local.newrelic_where_app_log} LIMIT 100"
      }
    }

    widget_stacked_bar {
      title  = "Volume de logs por nível"
      row    = 6
      column = 1
      width  = 6
      height = 3

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Log SELECT count(*) WHERE ${local.newrelic_where_app_log} FACET level TIMESERIES"
      }
    }

    widget_line {
      title            = "Erros na aplicação"
      row              = 6
      column           = 7
      width            = 6
      height           = 3
      y_axis_left_zero = true

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Log SELECT count(*) AS 'Erros' WHERE ${local.newrelic_where_app_log} AND level IN ('ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY') TIMESERIES"
      }
    }

    widget_log_table {
      title  = "API Gateway (access log)"
      row    = 9
      column = 1
      width  = 12
      height = 4

      nrql_query {
        account_id = local.newrelic_account
        query      = "FROM Log SELECT requestId, httpMethod, path, status, responseLatency, integrationLatency, integrationError, ip WHERE ${local.newrelic_where_gw_log} LIMIT 100"
      }
    }
  }
}

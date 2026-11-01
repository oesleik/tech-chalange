# ADR-009 - New Relic no EKS

**Status:** Aceita  
**Data:** 19/10/2026  
**Participantes:** Augusto, Claudio, Daniel, Fernando, Oeslei  

## Contexto

A API já tem observabilidade local com OpenTelemetry, Prometheus, Grafana, Loki e Jaeger. Em produção o processo roda no EKS e essa stack não sai do cluster de desenvolvimento. Faltava um lugar único para ver transações, erros, logs e o estado do Kubernetes na AWS.

## Decisão

Usar o agente PHP do New Relic na imagem da aplicação e o chart `nri-bundle` no cluster de produção.

- O agente fica **desligado** fora do EKS (`NEW_RELIC_ENABLED=false`).
- A license key entra só em runtime, por variável de ambiente e Secret do Kubernetes. Não é gravada na imagem nem no Git.
- Logs da aplicação vão para stderr em JSON. O próprio agente PHP faz o forwarding (logs in context). O coletor `newrelic-logging` do bundle fica desligado, para não enviar a mesma linha duas vezes.
- A stack OpenTelemetry local não é removida. No chart do EKS, `OTEL_PHP_AUTOLOAD_ENABLED` não é ligado.
- A infraestrutura AWS declara em Terraform a integração New Relic, o pipeline de logs por CloudWatch e Firehose, dashboards, synthetics e condições de alerta quando a integração está habilitada.
- O chart da aplicação habilita o agente New Relic para o ambiente AWS; a coleta do cluster é configurada pelo chart `nri-bundle`.

## Consequências

- O pod PHP no EKS reserva mais memória (+64Mi) por causa do daemon do agente.
- Ligar New Relic e OpenTelemetry ao mesmo tempo duplica trace da mesma requisição. Cada ambiente usa um dos dois.
- O nome da transação é o padrão da rota (`GET /clientes/{id:[0-9]+}`), não a URI com o id.
- Probes em `/health` e `/healthz` não entram no Apdex. O liveness do Nginx (`/nginx-health`) nem chega ao PHP.
- Os recursos e alertas New Relic são condicionais à configuração da integração e aos secrets correspondentes; não existem no ambiente quando ela está desabilitada.

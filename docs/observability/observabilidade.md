# Observabilidade

Implementação de observabilidade da API utilizando **OpenTelemetry**, **Prometheus**, **Grafana**, **Loki** e **Jaeger**.

## Stack

| Componente | Função |
|---|---|
| OpenTelemetry PHP | Instrumentação da aplicação |
| OpenTelemetry Collector | Recepção e encaminhamento da telemetria |
| Prometheus | Métricas |
| Loki | Logs |
| Jaeger | Traces |
| Grafana | Visualização |

Fluxo principal:

```text
API PHP
  ↓
OpenTelemetry Collector
  ├── Prometheus → métricas
  ├── Loki       → logs
  └── Jaeger     → traces
             ↓
          Grafana
```

## Arquivos

A configuração fica separada da aplicação:

```text
docker/
├── grafana/provisioning/
├── loki/local-config.yaml
├── otel/config.yaml
└── prometheus/prometheus.yml

docker-compose.observability.yml
```

O middleware da aplicação está em:

```text
src/Middleware/OpenTelemetryMiddleware.php
```

## Pré-requisitos

Docker, Docker Compose e Make.

A aplicação deve estar configurada com PHP 8.4 e as dependências do projeto.

## Subir o ambiente

```bash
make otel-up
```

Verificar os containers:

```bash
make otel-status
```

Parar o ambiente:

```bash
make otel-down
```

Ver logs do Collector:

```bash
make otel-logs
```

## Interfaces

| Serviço | URL |
|---|---|
| Grafana | http://localhost:3000 |
| Prometheus | http://localhost:9090 |
| Jaeger | http://localhost:16686 |
| Loki | http://localhost:3100 |


Os comandos `open-*` do `Makefile` também podem ser utilizados para abrir as interfaces no navegador.

## Testar métricas

Execute uma requisição válida ou gere um erro HTTP pela API/Swagger.

Exemplo de erro:

```bash
curl -i \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  http://localhost/estoque/pecas/99999
```

Principais métricas:

```text
tech_challenge_http_requests_total
tech_challenge_http_errors_requests_total
tech_challenge_http_request_duration_seconds
```

No Prometheus:

```promql
tech_challenge_http_requests_total
```

```promql
tech_challenge_http_errors_requests_total
```

## Testar logs

No Grafana, acessar:

```text
Explore → Loki
```

Consulta básica:

```logql
{service_name="tech-challenge-api"}
```

Para erros:

```logql
{service_name="tech-challenge-api"} | detected_level = "error"
```

## Testar traces

Acessar:

```text
http://localhost:16686
```

e pesquisar pelo serviço:

```text
tech-challenge-api
```

## Comandos Make

| Comando | Função |
|---|---|
| `make otel-up` | Sobe a aplicação com a stack de observabilidade |
| `make otel-down` | Para os serviços |
| `make otel-status` | Exibe o status dos serviços |
| `make otel-logs` | Exibe os logs do OpenTelemetry Collector |
| `make open-grafana` | Abre o Grafana |
| `make open-prometheus` | Abre o Prometheus |
| `make open-jaeger` | Abre o Jaeger |

Para consultar todos os comandos disponíveis:

```bash
make help
```

## Validação

A implementação foi validada localmente com:

- requisições HTTP válidas;
- respostas `404`;
- métricas de requisições e erros;
- envio de logs para Loki;
- visualização dos logs no Grafana;
- envio de traces para Jaeger;
- coleta das métricas pelo Prometheus.

---

*FIAP PosTech Software Architecture — Tech Challenge Fase 2*

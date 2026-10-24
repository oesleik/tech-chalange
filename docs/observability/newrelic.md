# New Relic

O New Relic observa a API no **EKS**. No laptop, no Minikube e no MiniStack ele fica desligado. A stack OpenTelemetry local (`make otel-up`) não muda.

Decisão registrada em [ADR-009](../adr/009-newrelic.md).

## O que entra no cluster

| Peça | Onde | Função |
|---|---|---|
| Agente PHP 12.11.0.40 | imagem `php:8.4-fpm` (Debian/glibc) | transações, erros, logs in context |
| `nri-bundle` 8.0.34 | namespace `newrelic`, só no workflow de EKS | métricas e eventos do Kubernetes |

O tarball `linux` do agente traz binário x86_64 e aarch64. O instalador usa a arquitetura do `docker build`. O workflow de produção roda em amd64. Um build em Apple Silicon gera imagem arm64 com o mesmo Dockerfile.

Logs da aplicação saem em JSON no stderr. O agente PHP encaminha esses registros e associa `trace.id`. O subchart `newrelic-logging` fica desligado: se os dois estiverem ligados, a mesma linha é cobrada duas vezes.

OpenTelemetry e New Relic não devem ficar ligados juntos. Os dois instrumentam a requisição e o trace duplica. No chart do EKS, `OTEL_PHP_AUTOLOAD_ENABLED` não é definido.

## Variáveis

| Variável | Dev / Compose | EKS |
|---|---|---|
| `NEW_RELIC_ENABLED` | `false` | `true` (`values-aws.yaml`) |
| `NEW_RELIC_APP_NAME` | `tech-challenge-local` | `tech-challenge-prod` |
| `NEW_RELIC_LICENSE_KEY` | vazia | Secret `newrelic-license`, chave `license-key` |
| `NEW_RELIC_LOG_FORWARDING` | `true` | `true` |

O PHP não substitui `${VAR}` de ambiente dentro de `.ini` (isso referencia outra diretiva). O entrypoint da imagem renderiza `docker/php/newrelic.ini.template` com `envsubst` antes do PHP-FPM.

A imagem oficial já define `clear_env = no` em `/usr/local/etc/php-fpm.d/docker.conf`. O build falha se essa linha sumir: sem ela o worker do FPM não enxerga a license key.

Licença que começa com `eu01` é da região EU e o agente PHP detecta sozinho. O deployment marker precisa de `NEW_RELIC_REGION=EU` no GitHub. O padrão do workflow é `US`.

## O que a aplicação envia

- Transação no formato `MÉTODO padrão`, por exemplo `GET /clientes/{id:[0-9]+}`. O id real não entra no nome.
- Atributos: `http.method`, `http.route`, `http.status_code` e `request.id` se o header `X-Request-Id` existir.
- Erro 5xx e exceção não tratada viram error event.
- `/health` e `/healthz` são ignorados. O readiness do Nginx usa `/health`. O liveness `/nginx-health` responde no Nginx e não chega ao PHP. O probe do PHP-FPM é TCP na porta 9000 e não cria transação.

### Métricas customizadas

Enviadas com `newrelic_custom_metric`. Os nomes são contrato com dashboards e alertas.

| Métrica | Valor | Quando |
|---|---|---|
| `Custom/OrdemServico/Criada` | 1 | OS criada com sucesso |
| `Custom/OrdemServico/Transicao/<NovaSituacao>` | 1 | Mudança de situação gravada |
| `Custom/OrdemServico/TempoNaSituacao/<SituacaoAnterior>` | ms | A OS sai da situação anterior |

- O agente agrega por minuto (count, total, min, max). Média: `SELECT average(newrelic.timeslice.value) FROM Metric WHERE metricTimesliceName LIKE 'Custom/OrdemServico/TempoNaSituacao/%' FACET metricTimesliceName`.

Não vão para atributo nem para log de propósito: CPF, CNPJ, placa, senha, token, corpo da requisição. SQL sai ofuscado (`record_sql = obfuscated`).

Algumas exceções de domínio colocam CPF ou placa na mensagem (`ClienteNaoEncontradoException`, `VeiculoNaoEncontradoException`). Os controllers devolvem isso como 404, e 404 não é enviado ao New Relic. Se uma dessas exceções escapar como 500, a mensagem vai no error event. O texto da exceção não deve carregar o identificador.

## Helm

`charts/tech-challenge/values.yaml` deixa `newrelic.enabled: false`. `values-aws.yaml` liga e usa o app name de produção.

O chart só **referencia** o Secret. Quem cria o Secret é o workflow, no namespace `tech-challenge`. O mesmo Secret, com a mesma chave, é criado no namespace `newrelic` para o `nri-bundle` (`global.customSecretName`). A key não passa por `--set`, para não ficar no histórico do Helm.

No EKS o request/limit de memória do PHP sobe 64Mi (192Mi / 320Mi). O daemon do agente mora no mesmo container. O HPA de memória usa o request como base.

`nri-metadata-injection` fica desligado neste primeiro corte. O nome `kubeEvents` do roteiro antigo é `nri-kube-events` no chart 8.0.34.

Minikube e MiniStack não instalam o bundle.

## CI

No workflow `aws-deploy`, antes do Helm da aplicação:

1. Falha se faltar `NEW_RELIC_LICENSE_KEY`, `NEW_RELIC_API_KEY` ou `NEW_RELIC_ACCOUNT_ID`.
2. Cria os Secrets nos dois namespaces.
3. Instala `newrelic/nri-bundle` na versão de `charts/newrelic/VERSION`.

Depois do deploy, o marker `newrelic/deployment-marker-action@v2.6.2` só roda se a variable `NEW_RELIC_ENTITY_GUID` estiver preenchida. A action atual marca pelo GUID; não recebe `accountId`. O secret da conta também alimenta o Terraform da integração AWS.

Secrets e variables ficam no GitHub. Não entram no repositório.

## Integração AWS

`infra/newrelic.tf`, aplicado pelo **AWS deploy**. Só é criado quando o secret `NEW_RELIC_ACCOUNT_ID` existe.

| Peça | Função |
|---|---|
| Role `tech-challenge-newrelic-integration` (`ReadOnlyAccess`) | API polling de métricas. Trust na conta AWS do New Relic com External ID = account id |
| Vínculo `tech-challenge-aws` (`newrelic_cloud_aws_link_account`, modo `PULL`) | Conta AWS ligada ao New Relic usando a role acima |
| Integrações `rds`, `ec2`, `lambda`, `api_gateway` | Polling de 300 s em `us-east-1` |
| Firehose `tech-challenge-newrelic-logs` | Entrega logs do CloudWatch no endpoint de logs do New Relic |
| Subscription filters (padrão vazio) | `/aws/apigateway/tech-challenge-api` e `/aws/lambda/tech-challenge-autenticacao` |
| Bucket `tech-challenge-<conta>-newrelic-firehose` | Só entregas que falharam; expira em 7 dias |

O provider `newrelic/newrelic` usa `NEW_RELIC_ACCOUNT_ID` e `NEW_RELIC_API_KEY` (User API key). A região vem da variable `NEW_RELIC_REGION` (padrão `US`). Sem account id, o provider recebe valores fictícios e nenhum recurso New Relic é criado (MiniStack, validação de PR).

## Validar

Local, com a extensão instalada e o agente desligado:

```bash
docker compose up -d --build
make nr-check
docker compose exec php php -i | grep -E 'newrelic\.(license|appname|enabled)'
```

`php -m` deve listar `newrelic`. `newrelic.enabled` deve ser `false` e a license deve estar vazia. A suíte de testes passa nesse modo.

Para ver dados no APM a partir da máquina, preencha a key no ambiente (não no Git), suba com `NEW_RELIC_ENABLED=true` e chame uma rota que não seja `/health`. O nome da transação aparece pelo padrão da rota.

No EKS, depois do workflow: a entidade `tech-challenge-prod` recebe transação, error event em 5xx e log associado ao trace. O namespace `newrelic` tem os pods do bundle.

## Se não aparecer dado

- Extensão ausente em `php -m`: o build não rodou o `newrelic-install`, ou a arquitetura do tarball não bate com a imagem.
- Daemon não sobe: o processo é `www-data`. O log do daemon precisa escrever em `/var/log/newrelic`, criado na imagem. Não há `newrelic.daemon.*` no ini; o agente sobe o daemon sozinho.
- APM vazio com a flag ligada: Secret ausente, `clear_env` voltou a `yes`, ou a key é de outra região.
- Log duplicado: `newrelic-logging` foi ligado junto com o forwarding do agente PHP.
- Worker sem variável: conferir `clear_env = no` no pool `www`.

## Dashboard e alertas

`infra/newrelic_dashboard.tf` e `infra/newrelic_alerts.tf`, aplicados pelo **AWS deploy**. O output `newrelic_dashboard_url` traz o link do dashboard.

Dashboard `tech-challenge`:

| Página | Conteúdo |
|---|---|
| Visão geral | rpm, taxa de erro, p95, uptime do `/health`, OS criadas, falhas em OS, CPU/memória dos containers |
| API | p50/p95/p99, throughput, tabela por transação, taxa de erro e 5xx, status HTTP; no API Gateway, latência empilhada integração + gateway e percentis (sem `/health`) |
| Kubernetes | CPU e memória por container em % do limit, réplicas do HPA (atuais, desejadas, máximo, se está no limite), CPU/memória em valor absoluto, CPU/memória alocável dos nodes, restarts, deployments |
| Saúde e uptime | Sucesso e duração do synthetic, falhas, pods ready, restarts, `/health` no access log |
| Ordens de serviço | Criadas por dia (30 dias), tempo médio por situação (min), transições, falhas por rota (`Transaction`), endpoints de OS |
| Integrações | RDS (CPU, memória livre, conexões), Lambda de autenticação (invocações, erros, duração, erros no log), 5xx e erros de integração do API Gateway, falhas de integração (`TransactionError` de `EmailException`), exceções por classe |
| Logs | Logs da aplicação, volume por nível, erros, access log do API Gateway |

Eixos e limiares:

- Gráfico em % de capacidade (CPU/memória de container, node e RDS) vai de 0 a 100: o que importa é a distância até 100%.
- Taxa de erro vai de 0 a 10%: o normal fica perto de 0, e uma escala até 100 esconderia a diferença entre 0,5% e 4%.
- Os demais gráficos de linha começam em 0 (`y_axis_left_zero`) para oscilação pequena não parecer pico.
- As faixas de limiar (warning/critical) são as mesmas dos alertas: latência, taxa de erro, CPU/memória e RDS.
- Integração + gateway somam o `responseLatency`. Requisição sem integração (`integrationLatency` = `-`, como erro do próprio gateway) fica fora para as duas médias cobrirem as mesmas requisições. Não há autorizador no API Gateway (o JWT é validado no PHP), então não há latência de autorizador a separar.

A métrica customizada chega em segundos (o agente converte os ms). Contagem usa `count(newrelic.timeslice.value)`.

Synthetic `tech-challenge-health`: ping (`SIMPLE`) a cada 5 min em `/health`. Exige `"status":"ok"` no corpo, então falha também com o banco fora. Ping não consome a cota de checks sintéticos.

Policy `tech-challenge` (um incidente por condição e alvo):

| Condição | Critical | Warning |
|---|---|---|
| Ordem de serviço: falha (`Transaction` 5xx/erro nas rotas de OS, por rota) | > 0 em 1 min | — |
| Integração: falha (`TransactionError` com `error.class` de integração, hoje `EmailException`) | > 0 em 1 min | — |
| API: taxa de 5xx | > 5% por 5 min | > 1% por 5 min |
| API: latência p95 | > 2 s por 5 min | > 1 s por 5 min |
| Uptime: `/health` falhando | um check com falha | — |
| Container: CPU (% do limit) | > 90% por 5 min | > 75% por 5 min |
| Container: memória (% do limit) | > 90% por 5 min | > 80% por 5 min |
| Node: CPU alocável | > 90% por 5 min | > 80% por 5 min |
| Node: memória alocável | > 90% por 5 min | > 80% por 5 min |
| HPA: limitado no máximo de réplicas (`isLimited`, por HPA) | > 0 por 10 min | — |
| RDS: CPU | > 80% por 15 min | — |
| RDS: memória livre | < 30 MiB por 15 min | — |

- Container em % do limit: só funciona porque todo container do chart tem limit em `values-aws.yaml`. Container sem limit não gera o atributo.
- RDS: a instância pequena opera com cerca de 60 MiB livres (o buffer pool do MySQL ocupa o resto). Por isso o limiar de 30 MiB, e não um percentual.

Notificação: variable `NEW_RELIC_ALERT_EMAILS` no GitHub, e-mails separados por vírgula. Com ela preenchida, o deploy cria destino e canal de e-mail e o workflow `tech-challenge`, que notifica abertura, reconhecimento e fechamento de incidentes da policy. Vazia, os incidentes só aparecem no New Relic.

### Correlação de requisição

O API Gateway sobrescreve o header `X-Request-Id` com `$context.requestId` (`request_parameters` da integração com o NLB). O mesmo valor aparece:

- no access log do API Gateway, campo `requestId`;
- na transação do APM, atributo `request.id`;
- no log da aplicação, atributo `context.request.id`.

A rota `POST /autenticacao` vai direto para a Lambda e não passa por esse header.

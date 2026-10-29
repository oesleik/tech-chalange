# ADR-010 - API Gateway como ponto de entrada

**Status:** Aceita
**Data:** 26/10/2026

## Contexto

A aplicação roda no EKS e o banco fica em sub-redes privadas. Precisamos de um ponto de entrada público único, sem expor NodePort ou IP de node, que roteie `POST /autenticacao` para a Lambda ([ADR-006](006-node-lambda.md), [ADR-008](008-lambda-acesso-rds.md)) e as demais rotas para a aplicação PHP, garantindo segurança e centralização do tráfego.

## Decisão

Usar o **Amazon API Gateway (HTTP API)**, definido em Terraform (`infra/apigateway.tf` e `infra/lambda.tf`).

- `POST /autenticacao` vai direto para a Lambda.
- A rota `$default` vai para o EKS via **VPC Link → NLB interno → Nginx (NodePort 30080)**. O security group do node só aceita tráfego do NLB.
- O gateway **não tem autorizador**: o JWT é validado pelo PHP (middleware).
- O gateway sobrescreve `X-Request-Id` com o `requestId` do gateway, para correlacionar access log, logs da aplicação e traces.
- A URL pública é dinâmica (muda após destroy/redeploy) e não deve ser fixada no código.

## Consequências

- Entrada única, com TLS e roteamento gerenciados e a aplicação sem endpoint público direto.
- A validação do JWT fica só no PHP: requisições inválidas chegam ao cluster antes de serem rejeitadas.

## Alternativas consideradas

- **Kong ou Traefik no cluster:** mais flexíveis (plugins, rate limit), mas exigem operação e disponibilidade próprias dentro do EKS.
- **Ingress/ALB direto:** dispensa o API Gateway, mas não cobre a rota serverless da Lambda no mesmo ponto de entrada.
- **Lambda authorizer no gateway:** rejeitaria requisições inválidas antes do cluster, ao custo de duplicar a validação do JWT fora do PHP.

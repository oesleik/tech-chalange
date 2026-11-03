# RFC-003 - Autenticação e comunicação das APIs

- **Status:** Aceito e adotado no projeto
- **Decisões registradas em:** ADR-008 e ADR-010
**Escopo:** Rota de autenticação, JWT e roteamento das APIs HTTP.

## Contexto

A API PHP precisa proteger rotas administrativas, enquanto clientes precisam autenticar-se para receber um token. A entrada pública deve encaminhar autenticação e demais requisições para componentes distintos sem expor diretamente o NodePort do EKS.

## Proposta

- Usar API Gateway HTTP API como ponto público único.
- Integrar `POST /autenticacao` diretamente à Lambda Node.js.
- Fazer a Lambda validar CPF/CNPJ, consultar a tabela `clientes` no RDS e emitir JWT HS256.
- Encaminhar a rota `$default` ao PHP no EKS por VPC Link, NLB interno e Nginx.
- Validar o Bearer JWT em middleware PHP nas rotas administrativas. O API Gateway não valida o JWT.
- Usar um JWT dedicado com expiração para os links de aprovação/rejeição de orçamento por e-mail.

## Alternativas consideradas

1. Manter autenticação como parte da API PHP. Evita segundo runtime e leitura direta do banco, mas faria a autenticação depender do EKS.
2. Usar um authorizer no API Gateway. Rejeita tokens antes do cluster, porém duplica ou move a validação para a borda.
3. Consultar a API PHP a partir da Lambda. Mantém o contexto Clientes como proprietário da consulta, mas adiciona dependência do EKS à autenticação.

## Consequências

- A Lambda e o PHP compartilham segredo, emissor, TTL, algoritmo e formato normalizado de CPF/CNPJ; divergências invalidam tokens.
- A Lambda depende do schema `clientes` e da conectividade privada com o RDS.
- Sem authorizer no gateway, requests com token inválido chegam ao cluster antes da rejeição.
- A autenticação usa JWT stateless; revogação individual imediata não está implementada.
- O endpoint diferencia documento inexistente com `404`, o que revela existência de cadastro; o ADR-008 registra o risco de enumeração.
- O fluxo e os participantes estão ilustrados em [Fluxo de autenticação](../arch/autenticacao.md).

# ADR-013 - Divisão em quatro repositórios com contrato via SSM

**Status:** Aceita  
**Data:** 29/10/2026  
**Participantes:** Augusto, Claudio, Daniel, Fernando, Oeslei  

## Contexto

A fase 3 pede que o projeto seja dividido em quatro repositórios, cada um com CI próprio:

| Repositório | Conteúdo |
|---|---|
| `tech-challenge-infra-k8s` | Terraform: VPC, EKS, ECR, NLB, API Gateway, New Relic |
| `tech-challenge-infra-db` | Terraform: RDS MySQL |
| `tech-chalange` | Aplicação PHP e chart Helm |
| `tech-challenge-lambda` | Lambda de autenticação e o Terraform dela |

Até aqui tudo vivia em um único state Terraform (`infra/`) e em um único workflow (`aws-deploy.yml`), que criava rede, cluster, banco, Lambda e publicava a aplicação em sequência. Os recursos têm dependências entre si: o RDS precisa das sub-redes e do SG do EKS, a Lambda precisa do RDS e do API Gateway, a aplicação precisa do cluster, do ECR e do endpoint do banco. Separados, cada repositório precisa descobrir o que os outros criaram, sem acoplar os pipelines.

## Decisão

### Contrato entre repositórios: SSM Parameter Store

Quem cria um recurso publica os identificadores que os outros consomem em `/tech-challenge/<escopo>/<nome>`. Quem depende lê com `data "aws_ssm_parameter"` (Terraform) ou `aws ssm get-parameter` (workflow).

| Parâmetro | Publicado por | Consumido por |
|---|---|---|
| `network/vpc_id`, `network/private_subnet_ids` | infra-k8s | infra-db, lambda |
| `eks/cluster_security_group_id` | infra-k8s | infra-db (regra EKS → RDS) |
| `eks/cluster_name`, `ecr/php_repository_url` | infra-k8s | app (kubeconfig, push da imagem) |
| `apigw/invoke_url` | infra-k8s | app (resumo do deploy), lambda (output da URL) |
| `apigw/api_id`, `apigw/execution_arn` | infra-k8s | lambda (rota e permissão) |
| `observability/*` (opcional) | infra-k8s | lambda (subscription de logs) |
| `db/address`, `db/name` | infra-db | app, lambda |
| `db/port`, `db/security_group_id` | infra-db | lambda |
| `app/image_tag` | app (workflow) | guard de deploy da lambda; guards de destroy do infra-db e do infra-k8s |
| `lambda/autenticacao_function_name` | lambda | guards de destroy da app, do infra-db e do infra-k8s |
| `db/*` (existência) | infra-db | guard de destroy do infra-k8s |

Alternativas descartadas:

- **`terraform_remote_state`:** acopla o consumidor ao layout do state do produtor (outputs, backend, chave) e exige permissão de leitura no state inteiro, que contém segredos. O SSM expõe só o que foi publicado de propósito.
- **Data sources por tag/nome** (`aws_vpc` com filtro): funciona, mas o contrato fica implícito e espalhado. Uma tag renomeada quebra o consumidor sem erro claro.
- **Repositório de módulos compartilhados / monorepo com pipelines por pasta:** não atende ao requisito de quatro repositórios.

### Pipelines independentes, com fail-fast

Não há pipeline orquestrador nem `workflow_dispatch` encadeado. Cada repositório tem `deploy.yml` e `destroy.yml` manuais, e a ordem é responsabilidade de quem opera:

```
Deploy:  infra-k8s → infra-db → app → lambda
Destroy: lambda → app → infra-db → infra-k8s
```

Cada Deploy confere se os parâmetros que consome existem e, se não, aborta dizendo qual repositório rodar antes. Cada Destroy confere se os escopos dos dependentes estão vazios. Por isso a app publica `app/image_tag` mesmo sem consumidor de valor: o parâmetro é o marcador de que ela está de pé.

### Regras de acesso ao banco: quem sobe depois cria

O SG do RDS não tem regras inline. A regra EKS → RDS é criada no infra-db (o cluster já existe quando o banco sobe). A regra Lambda → RDS é criada no repositório da Lambda, sobre o SG publicado em `db/security_group_id`. Assim nenhum repositório referencia algo que ainda não existe e não há dependência circular entre states. O custo é o destroy do infra-db precisar esperar a Lambda.

### Segredos duplicados

`DB_ROOT_PASSWORD` (infra-db e app), `JWT_SECRET` e `LAMBDA_DB_PASSWORD` (app e lambda) ficam como GitHub Secrets em cada repositório que os usa. O job `rds-user-setup` continua no chart da aplicação e cria também o usuário MySQL da Lambda, por isso a Lambda só sobe depois da app.

### Uma role OIDC para os quatro repositórios

A role `tech-challenge-gha-apply` passa a confiar nos `deploy.yml`/`destroy.yml` da `master` dos quatro repositórios. O CI de PR não assume role.

### Ambiente local só com Docker Compose

Minikube, MiniStack e `values-minikube.yaml`/`values-aws-local.yaml` foram removidos (substitui o [ADR-004](004-minikube-ministack-eks.md)). O Terraform é validado no CI só estaticamente (`fmt`, `init -backend=false`, `validate`) e o chart com `helm lint`/`helm template`.

## Consequências

### Positivas

- Cada repositório tem ciclo de vida, state e CI próprios. Mudar a Lambda não exige plan do cluster.
- O contrato é explícito e listado no README de cada repositório. Renomear um parâmetro é uma mudança de contrato visível.
- Erros de ordem aparecem no primeiro passo do workflow, com mensagem acionável, e não como timeout de destroy de SG.
- Menos ferramentas locais para manter (sem Minikube/MiniStack).

### Negativas

- **Segredos compartilhados podem divergir.** O erro só aparece em runtime: `rds-user-setup` falha, token rejeitado com 401 ou Lambda com 503. Um Secrets Manager central resolveria, com custo e mais uma peça. Aceito por ser projeto de estudos.
- **Contrato implícito por nome.** O dashboard e os alertas do New Relic (infra-k8s) filtram por `tech-challenge-mysql` e `tech-challenge-autenticacao`, nomes definidos em outros repositórios. Renomear quebra a observabilidade sem quebrar o deploy.
- **Ordem manual.** Subir o ambiente são quatro execuções. Um orquestrador (`workflow_call`/`repository_dispatch`) reduziria o trabalho, mas recriaria o acoplamento entre pipelines.
- **Role única.** Um repositório comprometido tem acesso a tudo o que a role permite. Aceito por simplicidade por ser projeto de estudos.
- **Sem teste de integração do Terraform** sem custo AWS: o CI só valida a sintaxe. `plan`/`apply` reais só no Deploy.

# Runbook: bootstrap AWS real + GitHub Actions (Terraform)

Passos **manuais via CLI** que o Terraform **não** cria (e nenhum Destroy apaga): state remoto + OIDC do GitHub Actions. Valem para os quatro repositórios ([ADR-013](../adr/013-divisao-repositorios.md)):

| Repositório | State |
|---|---|
| `oesleik/tech-challenge-infra-k8s` | `tech-challenge/infra-k8s.tfstate` |
| `oesleik/tech-challenge-infra-db` | `tech-challenge/infra-db.tfstate` |
| `oesleik/tech-chalange` | — (só Helm) |
| `oesleik/tech-challenge-lambda` | `tech-challenge/lambda.tfstate` |

O CI de PR de cada repositório só faz validação estática (`terraform validate`, `helm lint`, testes), **sem** assumir role na AWS. Subir o ambiente: **Actions -> Deploy** em cada repositório, na ordem infra-k8s → infra-db → app → lambda (role **apply**, branch `master`). Parar o custo: **Actions -> Destroy** na ordem inversa (digite `destroy`). Procedimento completo no [guia de deploy](./deploy_aws.md).

---

## Resumo dos recursos

| Recurso | Onde é configurado |
|---|---|
| Bucket S3 + tabela DynamoDB (state/lock) | CLI (setup) |
| IdP `token.actions.githubusercontent.com` + role `gha-apply` (única, compartilhada pelos 4 repos) | CLI (setup) |
| Grupo IAM `tech-challenge-devs` (acesso AWS dos devs) | CLI (setup) |
| Access entries EKS dos devs | Deploy do **infra-k8s** (variable `EKS_ADMIN_PRINCIPAL_ARNS`) |
| Variables do Actions (`TF_STATE_*`, `AWS_ROLE_ARN_APPLY`...) | `gh variable set` em cada repositório ([tabela](#variables-e-secrets-por-repositório)) |
| Secrets do Actions (`DB_*`, `JWT_*`, `MAIL_*`, `NEW_RELIC_*`) | `gh secret set` em cada repositório ([tabela](#variables-e-secrets-por-repositório)) |
| Cluster EKS + node group + ECR | Deploy do **infra-k8s** |
| RDS MySQL | Deploy do **infra-db** |
| Lambda `autenticacao` + rota `POST /autenticacao` | Deploy do **lambda** |
| Contrato SSM `/tech-challenge/*` | publicado por cada Deploy, apagado pelo Destroy correspondente |
| API Gateway (HTTP API) + VPC Link + NLB interno | Deploy do **infra-k8s** (URL no summary do job) |
| Integração AWS do New Relic (role de polling, vínculo, integrações e Firehose de logs) | Deploy do **infra-k8s** (`terraform/newrelic.tf`), inclusive o vínculo da conta e as integrações no New Relic; ver [New Relic](../observability/newrelic.md#integração-aws) |
| Dashboard, synthetic do `/health`, policy de alertas e notificação por e-mail no New Relic | Deploy do **infra-k8s** (`terraform/newrelic_dashboard.tf`, `terraform/newrelic_alerts.tf`; e-mails na variable `NEW_RELIC_ALERT_EMAILS`); ver [New Relic](../observability/newrelic.md#dashboard-alertas-e-synthetic) |

> **Role `tech-challenge-gha-apply` já criada antes da divisão em quatro repositórios?** Rode de novo o `cat > /tmp/gha-apply-trust.json` + `aws iam update-assume-role-policy` (trust com os quatro repositórios) e o `cat > /tmp/gha-apply-inline.json` + `aws iam put-role-policy` (statements novos/alterados: `TerraformStateObjects` com `tech-challenge/*` e `SsmContract`). O `put-role-policy` substitui a policy inline inteira, então exporte as variáveis do início da seção antes. A role `tech-challenge-gha-plan` e a variable `AWS_ROLE_ARN_PLAN` não são mais usadas e podem ser removidas.

O NodePort `30080` **não** é acessível pela internet: o SG dos nodes só aceita o SG do NLB interno, que só aceita o SG do VPC Link. O NLB cobra por hora enquanto existir (~US$ 0,0225/h); some no Destroy do **infra-k8s**.

---

## State remoto (dependência para AWS)

Pré-requisito: AWS CLI autenticado **na sua máquina**. Nomes de bucket S3 são globais.

```bash
export ACCOUNT_ID="$(aws sts get-caller-identity --query Account --output text)"
export STATE_BUCKET="tech-challenge-${ACCOUNT_ID}-tfstate"

aws s3api create-bucket --bucket "$STATE_BUCKET" --region us-east-1

aws s3api put-bucket-versioning \
  --bucket "$STATE_BUCKET" \
  --versioning-configuration Status=Enabled

aws s3api put-bucket-encryption \
  --bucket "$STATE_BUCKET" \
  --server-side-encryption-configuration \
  '{"Rules":[{"ApplyServerSideEncryptionByDefault":{"SSEAlgorithm":"AES256"}}]}'

aws s3api put-public-access-block \
  --bucket "$STATE_BUCKET" \
  --public-access-block-configuration \
  BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true

aws dynamodb create-table \
  --table-name tech-challenge-tfstate-lock \
  --attribute-definitions AttributeName=LockID,AttributeType=S \
  --key-schema AttributeName=LockID,KeyType=HASH \
  --billing-mode PAY_PER_REQUEST \
  --region us-east-1
```

O Terraform espera a coluna **`LockID`** (é o nome padrão). Não use outro nome.

---

## OIDC GitHub Actions (role única)

Pré-requisito: AWS CLI autenticado **na sua máquina** com permissão de IAM. Ajuste `GH_OWNER` e os nomes dos repositórios se forem outros.

```bash
export ACCOUNT_ID="$(aws sts get-caller-identity --query Account --output text)"
export GH_OWNER=oesleik
export GH_REPOS="tech-challenge-infra-k8s tech-challenge-infra-db tech-chalange tech-challenge-lambda"
export STATE_BUCKET="tech-challenge-${ACCOUNT_ID}-tfstate"
export OIDC_PROVIDER_ARN="arn:aws:iam::${ACCOUNT_ID}:oidc-provider/token.actions.githubusercontent.com"
```

### Identity Provider (uma vez por conta)

Thumbprints oficiais do GitHub Actions. Se o IdP já existir (`EntityAlreadyExists`), siga em frente.

```bash
aws iam create-open-id-connect-provider \
  --url https://token.actions.githubusercontent.com \
  --client-id-list sts.amazonaws.com \
  --thumbprint-list 6938fd4d98bab03faadb97b34396831e3780aea1 1c58a3a8518e8759bf075cb0be186b4c3af420f2
```

### Role apply (deploy e destroy na `master`)

Trust: só `refs/heads/master` **e** só `deploy.yml` / `destroy.yml` dos quatro repositórios. Sem isso, qualquer workflow novo na `master` poderia assumir a role. Os CIs de PR não assumem role nenhuma.

Uma role só para os quatro repositórios é uma simplificação deliberada (projeto de estudos): o raio de impacto de um repositório comprometido é a conta inteira do projeto. O próximo passo natural seria uma role por repositório, com a policy restrita aos recursos dele ([ADR-013](../adr/013-divisao-repositorios.md)).

A policy cobre o HCL atual (VPC, IAM de EKS/Lambda, EKS, ECR, API Gateway/NLB, RDS, Lambda, New Relic), o state dos três repositórios Terraform e o contrato SSM. O `Deny` no final impede o apply de apagar o próprio setup de OIDC.

```bash
WORKFLOW_REFS="$(for r in $GH_REPOS; do for w in deploy destroy; do
  printf '"%s/%s/.github/workflows/%s.yml@refs/heads/master",' "$GH_OWNER" "$r" "$w"
done; done | sed 's/,$//')"
SUBS="$(for r in $GH_REPOS; do
  printf '"repo:%s/%s:ref:refs/heads/master",' "$GH_OWNER" "$r"
done | sed 's/,$//')"

cat > /tmp/gha-apply-trust.json <<EOF
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": {
        "Federated": "${OIDC_PROVIDER_ARN}"
      },
      "Action": "sts:AssumeRoleWithWebIdentity",
      "Condition": {
        "StringEquals": {
          "token.actions.githubusercontent.com:aud": "sts.amazonaws.com",
          "token.actions.githubusercontent.com:job_workflow_ref": [${WORKFLOW_REFS}],
          "token.actions.githubusercontent.com:sub": [${SUBS}]
        }
      }
    }
  ]
}
EOF

cat > /tmp/gha-apply-inline.json <<EOF
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "TerraformState",
      "Effect": "Allow",
      "Action": [
        "s3:ListBucket",
        "s3:GetBucketVersioning",
        "s3:GetEncryptionConfiguration",
        "s3:GetBucketPublicAccessBlock"
      ],
      "Resource": "arn:aws:s3:::${STATE_BUCKET}"
    },
    {
      "Sid": "TerraformStateObjects",
      "Effect": "Allow",
      "Action": ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
      "Resource": "arn:aws:s3:::${STATE_BUCKET}/tech-challenge/*"
    },
    {
      "Sid": "TerraformLock",
      "Effect": "Allow",
      "Action": [
        "dynamodb:DescribeTable",
        "dynamodb:GetItem",
        "dynamodb:PutItem",
        "dynamodb:DeleteItem"
      ],
      "Resource": "arn:aws:dynamodb:us-east-1:${ACCOUNT_ID}:table/tech-challenge-tfstate-lock"
    },
    {
      "Sid": "Networking",
      "Effect": "Allow",
      "Action": [
        "ec2:CreateVpc", "ec2:DeleteVpc", "ec2:DescribeVpcs",
        "ec2:CreateSubnet", "ec2:DeleteSubnet", "ec2:DescribeSubnets",
        "ec2:CreateInternetGateway", "ec2:DeleteInternetGateway",
        "ec2:AttachInternetGateway", "ec2:DetachInternetGateway",
        "ec2:DescribeInternetGateways",
        "ec2:CreateRouteTable", "ec2:DeleteRouteTable", "ec2:DescribeRouteTables",
        "ec2:CreateRoute", "ec2:DeleteRoute",
        "ec2:AssociateRouteTable", "ec2:DisassociateRouteTable",
        "ec2:ModifyVpcAttribute", "ec2:ModifySubnetAttribute",
        "ec2:CreateTags", "ec2:DeleteTags", "ec2:DescribeTags",
        "ec2:DescribeAvailabilityZones", "ec2:DescribeAccountAttributes",
        "ec2:DescribeSecurityGroups", "ec2:CreateSecurityGroup", "ec2:DeleteSecurityGroup",
        "ec2:AuthorizeSecurityGroupIngress", "ec2:AuthorizeSecurityGroupEgress",
        "ec2:RevokeSecurityGroupIngress", "ec2:RevokeSecurityGroupEgress",
        "ec2:DescribeSecurityGroupRules", "ec2:ModifySecurityGroupRules",
        "ec2:DescribeNetworkInterfaces", "ec2:CreateNetworkInterface",
        "ec2:DeleteNetworkInterface", "ec2:DescribeVpcAttribute",
        "ec2:DescribePrefixLists", "ec2:DescribeImages", "ec2:DescribeLaunchTemplates",
        "ec2:RunInstances", "ec2:TerminateInstances", "ec2:DescribeInstances",
        "ec2:DescribeInstanceTypes", "ec2:DescribeKeyPairs"
      ],
      "Resource": "*"
    },
    {
      "Sid": "IamForEksRoles",
      "Effect": "Allow",
      "Action": [
        "iam:CreateRole", "iam:GetRole", "iam:DeleteRole", "iam:TagRole", "iam:UntagRole",
        "iam:ListRolePolicies", "iam:ListAttachedRolePolicies", "iam:ListInstanceProfilesForRole",
        "iam:AttachRolePolicy", "iam:DetachRolePolicy",
        "iam:CreateInstanceProfile", "iam:GetInstanceProfile",
        "iam:DeleteInstanceProfile", "iam:AddRoleToInstanceProfile",
        "iam:RemoveRoleFromInstanceProfile",
        "iam:CreateOpenIDConnectProvider", "iam:GetOpenIDConnectProvider",
        "iam:DeleteOpenIDConnectProvider", "iam:TagOpenIDConnectProvider",
        "iam:ListOpenIDConnectProviders",
        "iam:PassRole"
      ],
      "Resource": "*"
    },
    {
      "Sid": "EksServiceLinkedRoles",
      "Effect": "Allow",
      "Action": "iam:CreateServiceLinkedRole",
      "Resource": "*",
      "Condition": {
        "StringEquals": {
          "iam:AWSServiceName": [
            "eks.amazonaws.com",
            "eks-nodegroup.amazonaws.com",
            "elasticloadbalancing.amazonaws.com",
            "ops.apigateway.amazonaws.com"
          ]
        }
      }
    },
    {
      "Sid": "Eks",
      "Effect": "Allow",
      "Action": ["eks:*"],
      "Resource": "*"
    },
    {
      "Sid": "EcrRepo",
      "Effect": "Allow",
      "Action": [
        "ecr:CreateRepository", "ecr:DeleteRepository", "ecr:DescribeRepositories",
        "ecr:ListTagsForResource", "ecr:TagResource", "ecr:UntagResource",
        "ecr:PutImageScanningConfiguration", "ecr:GetRepositoryPolicy",
        "ecr:SetRepositoryPolicy", "ecr:DeleteRepositoryPolicy",
        "ecr:GetAuthorizationToken", "ecr:BatchCheckLayerAvailability",
        "ecr:GetDownloadUrlForLayer", "ecr:BatchGetImage", "ecr:PutImage",
        "ecr:InitiateLayerUpload", "ecr:UploadLayerPart", "ecr:CompleteLayerUpload"
      ],
      "Resource": "*"
    },
    {
      "Sid": "ApiGatewayNlb",
      "Effect": "Allow",
      "Action": [
        "apigateway:GET", "apigateway:POST", "apigateway:PUT",
        "apigateway:PATCH", "apigateway:DELETE",
        "elasticloadbalancing:*",
        "ec2:GetSecurityGroupsForVpc",
        "autoscaling:AttachLoadBalancerTargetGroups",
        "autoscaling:DetachLoadBalancerTargetGroups",
        "autoscaling:DescribeAutoScalingGroups",
        "autoscaling:DescribeLoadBalancerTargetGroups",
        "logs:CreateLogGroup", "logs:DeleteLogGroup", "logs:DescribeLogGroups",
        "logs:PutRetentionPolicy", "logs:DeleteRetentionPolicy",
        "logs:TagResource", "logs:UntagResource", "logs:ListTagsForResource",
        "logs:CreateLogDelivery", "logs:GetLogDelivery", "logs:UpdateLogDelivery",
        "logs:DeleteLogDelivery", "logs:ListLogDeliveries",
        "logs:PutResourcePolicy", "logs:DescribeResourcePolicies"
      ],
      "Resource": "*"
    },
    {
      "Sid": "RdsMysql",
      "Effect": "Allow",
      "Action": [
        "rds:CreateDBSubnetGroup", "rds:ModifyDBSubnetGroup", "rds:DeleteDBSubnetGroup",
        "rds:CreateDBInstance", "rds:ModifyDBInstance", "rds:DeleteDBInstance",
        "rds:AddTagsToResource", "rds:RemoveTagsFromResource", "rds:ListTagsForResource"
      ],
      "Resource": [
        "arn:aws:rds:us-east-1:${ACCOUNT_ID}:db:tech-challenge-mysql",
        "arn:aws:rds:us-east-1:${ACCOUNT_ID}:subgrp:tech-challenge-rds-subnets"
      ]
    },
    {
      "Sid": "RdsDescribe",
      "Effect": "Allow",
      "Action": ["rds:DescribeDBInstances", "rds:DescribeDBSubnetGroups"],
      "Resource": [
        "arn:aws:rds:us-east-1:${ACCOUNT_ID}:db:*",
        "arn:aws:rds:us-east-1:${ACCOUNT_ID}:subgrp:*"
      ]
    },
    {
      "Sid": "RdsMysqlDefaultGroups",
      "Effect": "Allow",
      "Action": ["rds:CreateDBInstance", "rds:ModifyDBInstance"],
      "Resource": [
        "arn:aws:rds:us-east-1:${ACCOUNT_ID}:og:default:mysql-*",
        "arn:aws:rds:us-east-1:${ACCOUNT_ID}:pg:default.mysql*"
      ]
    },
    {
      "Sid": "RdsServiceLinkedRole",
      "Effect": "Allow",
      "Action": "iam:CreateServiceLinkedRole",
      "Resource": "arn:aws:iam::${ACCOUNT_ID}:role/aws-service-role/rds.amazonaws.com/AWSServiceRoleForRDS",
      "Condition": {
        "StringEquals": {
          "iam:AWSServiceName": "rds.amazonaws.com"
        }
      }
    },
    {
      "Sid": "LambdaAutenticacao",
      "Effect": "Allow",
      "Action": [
        "lambda:CreateFunction", "lambda:DeleteFunction",
        "lambda:GetFunction", "lambda:GetFunctionConfiguration",
        "lambda:GetFunctionCodeSigningConfig", "lambda:ListVersionsByFunction",
        "lambda:UpdateFunctionCode", "lambda:UpdateFunctionConfiguration",
        "lambda:AddPermission", "lambda:RemovePermission", "lambda:GetPolicy",
        "lambda:TagResource", "lambda:UntagResource", "lambda:ListTags",
        "lambda:PublishVersion", "lambda:GetFunctionConcurrency",
        "lambda:PutFunctionConcurrency", "lambda:DeleteFunctionConcurrency",
        "lambda:GetFunctionRecursionConfig", "lambda:GetRuntimeManagementConfig",
        "lambda:GetFunctionEventInvokeConfig"
      ],
      "Resource": [
        "arn:aws:lambda:us-east-1:${ACCOUNT_ID}:function:tech-challenge-autenticacao",
        "arn:aws:lambda:us-east-1:${ACCOUNT_ID}:function:tech-challenge-autenticacao:*"
      ]
    },
    {
      "Sid": "LambdaVpcEniCleanup",
      "Effect": "Allow",
      "Action": "ec2:DetachNetworkInterface",
      "Resource": [
        "arn:aws:ec2:us-east-1:${ACCOUNT_ID}:network-interface/*",
        "arn:aws:ec2:us-east-1:${ACCOUNT_ID}:instance/*"
      ]
    },
    {
      "Sid": "NewRelicFirehose",
      "Effect": "Allow",
      "Action": [
        "firehose:CreateDeliveryStream", "firehose:DeleteDeliveryStream",
        "firehose:DescribeDeliveryStream", "firehose:UpdateDestination",
        "firehose:ListTagsForDeliveryStream", "firehose:TagDeliveryStream",
        "firehose:UntagDeliveryStream"
      ],
      "Resource": "arn:aws:firehose:us-east-1:${ACCOUNT_ID}:deliverystream/tech-challenge-newrelic-logs"
    },
    {
      "Sid": "NewRelicFirehoseBackupBucket",
      "Effect": "Allow",
      "Action": "s3:*",
      "Resource": [
        "arn:aws:s3:::tech-challenge-${ACCOUNT_ID}-newrelic-firehose",
        "arn:aws:s3:::tech-challenge-${ACCOUNT_ID}-newrelic-firehose/*"
      ]
    },
    {
      "Sid": "NewRelicLogSubscriptions",
      "Effect": "Allow",
      "Action": [
        "logs:PutSubscriptionFilter", "logs:DeleteSubscriptionFilter",
        "logs:DescribeSubscriptionFilters"
      ],
      "Resource": "*"
    },
    {
      "Sid": "NewRelicInlineRolePolicies",
      "Effect": "Allow",
      "Action": ["iam:PutRolePolicy", "iam:GetRolePolicy", "iam:DeleteRolePolicy"],
      "Resource": [
        "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-newrelic-firehose",
        "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-newrelic-logs-to-firehose"
      ]
    },
    {
      "Sid": "SsmContract",
      "Effect": "Allow",
      "Action": [
        "ssm:PutParameter", "ssm:GetParameter", "ssm:GetParameters",
        "ssm:GetParametersByPath", "ssm:DeleteParameter", "ssm:DeleteParameters",
        "ssm:AddTagsToResource", "ssm:RemoveTagsFromResource", "ssm:ListTagsForResource"
      ],
      "Resource": [
        "arn:aws:ssm:us-east-1:${ACCOUNT_ID}:parameter/tech-challenge",
        "arn:aws:ssm:us-east-1:${ACCOUNT_ID}:parameter/tech-challenge/*"
      ]
    },
    {
      "Sid": "SsmDescribe",
      "Effect": "Allow",
      "Action": "ssm:DescribeParameters",
      "Resource": "*"
    },
    {
      "Sid": "DenyTouchGithubOidcBootstrap",
      "Effect": "Deny",
      "Action": [
        "iam:DeleteOpenIDConnectProvider",
        "iam:DeleteRole",
        "iam:DetachRolePolicy",
        "iam:DeleteRolePolicy",
        "iam:UpdateAssumeRolePolicy"
      ],
      "Resource": [
        "${OIDC_PROVIDER_ARN}",
        "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-gha-apply"
      ]
    }
  ]
}
EOF

aws iam create-role \
  --role-name tech-challenge-gha-apply \
  --max-session-duration 3600 \
  --assume-role-policy-document file:///tmp/gha-apply-trust.json
# Role já existente: aws iam update-assume-role-policy --role-name tech-challenge-gha-apply --policy-document file:///tmp/gha-apply-trust.json

aws iam put-role-policy \
  --role-name tech-challenge-gha-apply \
  --policy-name tech-challenge-gha-apply \
  --policy-document file:///tmp/gha-apply-inline.json
```

PR de **fork** não assume estas roles (`sub` é outro repositório).

---

## GitHub: variables

Os ARNs não são secretos (a proteção é o **trust** da role). Comuns a todos os repositórios:

```bash
for r in $GH_REPOS; do
  gh variable set AWS_ROLE_ARN_APPLY --repo "${GH_OWNER}/${r}" --body "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-gha-apply"
done
for r in tech-challenge-infra-k8s tech-challenge-infra-db tech-challenge-lambda; do
  gh variable set TF_STATE_BUCKET --repo "${GH_OWNER}/${r}" --body "${STATE_BUCKET}"
  gh variable set TF_STATE_LOCK_TABLE --repo "${GH_OWNER}/${r}" --body "tech-challenge-tfstate-lock"
done
```

A tabela completa (opcionais incluídos) está em [Variables e secrets por repositório](#variables-e-secrets-por-repositório).

---

## Acesso dos devs (IAM + EKS)

São duas camadas independentes, e o dev precisa das duas:

| Camada | Libera | Onde |
|---|---|---|
| IAM (grupo `tech-challenge-devs`) | API/console da AWS para os serviços do projeto | CLI, uma vez por conta |
| Access entry do EKS | Recursos do Kubernetes (pods, deployments etc.) no console e no `kubectl` | Terraform, via variable `EKS_ADMIN_PRINCIPAL_ARNS` |

Pré-requisitos: usuários IAM já criados (senha de console/MFA/access keys são manuais) e as variáveis do início da seção [OIDC](#oidc-github-actions-role-única) exportadas.

### Grupo IAM

Acesso total aos recursos que o Terraform gerencia, restrito pelo prefixo `tech-challenge` onde o serviço permite (EKS, ECR, RDS, Lambda, CloudWatch Logs, ELB). Leitura (`Describe*`/`List*`) vale para a conta inteira, para o console conseguir listar.

Ficam **sem restrição** (conta inteira): EC2/VPC, Auto Scaling, API Gateway e métricas do CloudWatch. Os ARNs desses recursos usam IDs gerados (`vpc-…`, `/apis/abc123`), não o nome, e o HCL não aplica uma tag comum para filtrar por condição.

IAM é só leitura. A exceção é `iam:PassRole` nas roles do projeto, necessário para editar a configuração da Lambda ou do node group pelo console; não permite alterar as roles.

Criar recursos que geram custo fica só com o Terraform (role `gha-apply`, nos workflows de Deploy). Os `Deny` no final bloqueiam instâncias EC2, volumes/snapshots/AMIs, Elastic IPs, NAT Gateway, VPC endpoints, VPN, ASGs, clusters/node groups EKS, instâncias/restores/snapshots RDS, Lambdas, load balancers, APIs do API Gateway, log groups, dashboards/alarmes/métricas customizadas. Alterar e apagar os recursos existentes continua liberado, inclusive mudanças que aumentam custo (escalar o node group, trocar a classe do RDS). Um `Deny` vale mesmo que o usuário tenha outra policy permissiva (ex.: `AdministratorAccess`): quem precisar criar recursos manualmente não pode estar no grupo.

```bash
cat > /tmp/devs-inline.json <<EOF
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "NoResourceScope",
      "Effect": "Allow",
      "Action": ["ec2:*", "autoscaling:*", "apigateway:*", "cloudwatch:*"],
      "Resource": "*"
    },
    {
      "Sid": "ReadAll",
      "Effect": "Allow",
      "Action": [
        "eks:Describe*", "eks:List*",
        "ecr:Describe*", "ecr:List*", "ecr:GetAuthorizationToken",
        "rds:Describe*", "rds:List*",
        "lambda:List*", "lambda:GetAccountSettings",
        "logs:Describe*", "logs:GetQueryResults", "logs:StopQuery",
        "elasticloadbalancing:Describe*"
      ],
      "Resource": "*"
    },
    {
      "Sid": "Eks",
      "Effect": "Allow",
      "Action": "eks:*",
      "Resource": [
        "arn:aws:eks:us-east-1:${ACCOUNT_ID}:cluster/tech-challenge",
        "arn:aws:eks:us-east-1:${ACCOUNT_ID}:*/tech-challenge/*"
      ]
    },
    {
      "Sid": "Ecr",
      "Effect": "Allow",
      "Action": "ecr:*",
      "Resource": "arn:aws:ecr:us-east-1:${ACCOUNT_ID}:repository/tech-challenge-*"
    },
    {
      "Sid": "Rds",
      "Effect": "Allow",
      "Action": "rds:*",
      "Resource": "arn:aws:rds:us-east-1:${ACCOUNT_ID}:*:tech-challenge-*"
    },
    {
      "Sid": "RdsMysqlDefaultGroups",
      "Effect": "Allow",
      "Action": ["rds:CreateDBInstance", "rds:ModifyDBInstance"],
      "Resource": [
        "arn:aws:rds:us-east-1:${ACCOUNT_ID}:og:default:mysql-*",
        "arn:aws:rds:us-east-1:${ACCOUNT_ID}:pg:default.mysql*"
      ]
    },
    {
      "Sid": "Lambda",
      "Effect": "Allow",
      "Action": "lambda:*",
      "Resource": "arn:aws:lambda:us-east-1:${ACCOUNT_ID}:function:tech-challenge-*"
    },
    {
      "Sid": "Logs",
      "Effect": "Allow",
      "Action": "logs:*",
      "Resource": [
        "arn:aws:logs:us-east-1:${ACCOUNT_ID}:log-group:/aws/lambda/tech-challenge-*",
        "arn:aws:logs:us-east-1:${ACCOUNT_ID}:log-group:/aws/apigateway/tech-challenge-*",
        "arn:aws:logs:us-east-1:${ACCOUNT_ID}:log-group:/aws/eks/tech-challenge/*"
      ]
    },
    {
      "Sid": "Elb",
      "Effect": "Allow",
      "Action": "elasticloadbalancing:*",
      "Resource": "arn:aws:elasticloadbalancing:us-east-1:${ACCOUNT_ID}:*/tech-challenge-*"
    },
    {
      "Sid": "TerraformState",
      "Effect": "Allow",
      "Action": ["s3:ListBucket", "s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
      "Resource": [
        "arn:aws:s3:::${STATE_BUCKET}",
        "arn:aws:s3:::${STATE_BUCKET}/*"
      ]
    },
    {
      "Sid": "TerraformLock",
      "Effect": "Allow",
      "Action": ["dynamodb:DescribeTable", "dynamodb:GetItem", "dynamodb:PutItem", "dynamodb:DeleteItem"],
      "Resource": "arn:aws:dynamodb:us-east-1:${ACCOUNT_ID}:table/tech-challenge-tfstate-lock"
    },
    {
      "Sid": "SsmContractRead",
      "Effect": "Allow",
      "Action": ["ssm:GetParameter", "ssm:GetParameters", "ssm:GetParametersByPath"],
      "Resource": [
        "arn:aws:ssm:us-east-1:${ACCOUNT_ID}:parameter/tech-challenge",
        "arn:aws:ssm:us-east-1:${ACCOUNT_ID}:parameter/tech-challenge/*"
      ]
    },
    {
      "Sid": "SsmDescribe",
      "Effect": "Allow",
      "Action": "ssm:DescribeParameters",
      "Resource": "*"
    },
    {
      "Sid": "IamRead",
      "Effect": "Allow",
      "Action": ["iam:Get*", "iam:List*"],
      "Resource": "*"
    },
    {
      "Sid": "PassProjectRoles",
      "Effect": "Allow",
      "Action": "iam:PassRole",
      "Resource": [
        "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-cluster",
        "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-nodes",
        "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-autenticacao"
      ],
      "Condition": {
        "StringEquals": {
          "iam:PassedToService": ["eks.amazonaws.com", "ec2.amazonaws.com", "lambda.amazonaws.com"]
        }
      }
    },
    {
      "Sid": "DenyCreateBillable",
      "Effect": "Deny",
      "Action": [
        "ec2:RunInstances", "ec2:CreateFleet", "ec2:Request*", "ec2:Purchase*", "ec2:Allocate*",
        "ec2:CreateVolume", "ec2:CreateSnapshot", "ec2:CreateSnapshots", "ec2:CreateImage",
        "ec2:RegisterImage", "ec2:Copy*", "ec2:Import*",
        "ec2:CreateNatGateway", "ec2:CreateVpcEndpoint", "ec2:CreateTransitGateway",
        "ec2:CreateVpnConnection", "ec2:CreateVpnGateway", "ec2:CreateClientVpnEndpoint",
        "ec2:CreateCapacityReservation", "ec2:CreateCapacityReservationFleet",
        "ec2:CreateTrafficMirrorSession", "ec2:CreateIpam",
        "ec2:CreateVerifiedAccessInstance", "ec2:CreateVerifiedAccessEndpoint",
        "autoscaling:CreateAutoScalingGroup", "autoscaling:CreateLaunchConfiguration",
        "eks:CreateCluster", "eks:CreateNodegroup", "eks:CreateFargateProfile",
        "ecr:CreateRepository",
        "rds:CreateDB*", "rds:RestoreDB*", "rds:CopyDB*", "rds:CreateBlueGreenDeployment",
        "rds:CreateGlobalCluster", "rds:StartExportTask",
        "lambda:CreateFunction", "lambda:PutProvisionedConcurrencyConfig",
        "elasticloadbalancing:CreateLoadBalancer",
        "logs:CreateLogGroup",
        "cloudwatch:PutDashboard", "cloudwatch:PutMetricStream", "cloudwatch:PutMetricAlarm",
        "cloudwatch:PutCompositeAlarm", "cloudwatch:PutAnomalyDetector",
        "cloudwatch:PutInsightRule", "cloudwatch:PutMetricData"
      ],
      "Resource": "*"
    },
    {
      "Sid": "DenyCreateApiGateway",
      "Effect": "Deny",
      "Action": "apigateway:POST",
      "Resource": [
        "arn:aws:apigateway:us-east-1::/apis",
        "arn:aws:apigateway:us-east-1::/restapis",
        "arn:aws:apigateway:us-east-1::/domainnames",
        "arn:aws:apigateway:us-east-1::/vpclinks"
      ]
    }
  ]
}
EOF

aws iam create-group --group-name tech-challenge-devs

aws iam put-group-policy \
  --group-name tech-challenge-devs \
  --policy-name tech-challenge-devs \
  --policy-document file:///tmp/devs-inline.json
```

### Adicionar devs

```bash
aws iam add-user-to-group --group-name tech-challenge-devs --user-name fulano
```

### Acesso ao cluster EKS

Lista JSON com o ARN de **todos** os devs.

```bash
gh variable set EKS_ADMIN_PRINCIPAL_ARNS --repo "${GH_OWNER}/tech-challenge-infra-k8s" \
  --body "[\"arn:aws:iam::${ACCOUNT_ID}:user/fulano\", \"arn:aws:iam::${ACCOUNT_ID}:user/beltrano\"]"
```

Cada ARN ganha `AmazonEKSClusterAdminPolicy` no cluster inteiro (`terraform/eks.tf` do infra-k8s). Access entry criada antes manualmente no console dá `ResourceInUseException` no apply: apague-a no console (EKS > cluster > Access) antes do deploy.

`kubectl` local: `aws eks update-kubeconfig --name tech-challenge --region us-east-1` com as credenciais do próprio usuário (não de uma role assumida, senão o ARN não bate com o da access entry).

---

## GitHub: secrets

```bash
# Valores novos para a AWS (não reutilize o .env local).
# gh secret set sem --body pede o valor no terminal; para os compartilhados,
# gere o valor uma vez e passe com --body para não digitar diferente.
APP=tech-chalange; DB=tech-challenge-infra-db; LAMBDA=tech-challenge-lambda; K8S=tech-challenge-infra-k8s

DB_ROOT_PASSWORD="$(openssl rand -base64 36 | tr -d '/+=')"
for r in $DB $APP; do gh secret set DB_ROOT_PASSWORD --repo "${GH_OWNER}/${r}" --body "$DB_ROOT_PASSWORD"; done

JWT_SECRET="$(openssl rand -base64 48)"
LAMBDA_DB_PASSWORD="$(openssl rand -base64 24 | tr -d '/+=')"
for r in $APP $LAMBDA; do
  gh secret set JWT_SECRET --repo "${GH_OWNER}/${r}" --body "$JWT_SECRET"
  gh secret set LAMBDA_DB_PASSWORD --repo "${GH_OWNER}/${r}" --body "$LAMBDA_DB_PASSWORD"
done
unset DB_ROOT_PASSWORD JWT_SECRET LAMBDA_DB_PASSWORD

for s in DB_PASSWORD OS_EMAIL_ACTION_TOKEN_SECRET MAIL_USERNAME MAIL_PASSWORD; do
  gh secret set "$s" --repo "${GH_OWNER}/${APP}"
done
for s in NEW_RELIC_ACCOUNT_ID NEW_RELIC_API_KEY NEW_RELIC_LICENSE_KEY; do
  gh secret set "$s" --repo "${GH_OWNER}/${K8S}"
done
gh secret set NEW_RELIC_LICENSE_KEY --repo "${GH_OWNER}/${APP}"
```

---

## Variables e secrets por repositório

| Nome | Tipo | infra-k8s | infra-db | app | lambda |
|---|---|:-:|:-:|:-:|:-:|
| `AWS_ROLE_ARN_APPLY` | variable | ✓ | ✓ | ✓ | ✓ |
| `TF_STATE_BUCKET`, `TF_STATE_LOCK_TABLE` | variable | ✓ | ✓ | | ✓ |
| `EKS_ADMIN_PRINCIPAL_ARNS` | variable (opcional) | ✓ | | | |
| `NEW_RELIC_REGION` | variable (opcional) | ✓ | | ✓ | |
| `NEW_RELIC_ALERT_EMAILS` | variable (opcional) | ✓ | | | |
| `NEW_RELIC_ENTITY_GUID` | variable (opcional) | | | ✓ | |
| `NEW_RELIC_ACCOUNT_ID` | secret | ✓ | | | |
| `NEW_RELIC_API_KEY` | secret | ✓ | | opcional (marker) | |
| `NEW_RELIC_LICENSE_KEY` | secret | ✓ | | ✓ | |
| `DB_ROOT_PASSWORD` | secret, **compartilhado** | | ✓ | ✓ | |
| `JWT_SECRET` | secret, **compartilhado** | | | ✓ | ✓ |
| `LAMBDA_DB_PASSWORD` | secret, **compartilhado** | | | ✓ | ✓ |
| `DB_PASSWORD` | secret | | | ✓ | |
| `OS_EMAIL_ACTION_TOKEN_SECRET` | secret | | | ✓ | |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | secret (opcional) | | | ✓ | |

Os **compartilhados** precisam ter o mesmo valor nos repositórios marcados. Divergência só aparece em runtime: job `rds-user-setup` falha (`DB_ROOT_PASSWORD`), token emitido pela Lambda rejeitado pelo PHP com 401 (`JWT_SECRET`) ou Lambda sem acesso ao banco, 503 (`LAMBDA_DB_PASSWORD`).

---

## Derrubar tudo (evitar custos)

O item caro é o **control plane EKS** enquanto o cluster existir. Rode **Actions -> Destroy** (confirm = `destroy`) em cada repositório, na ordem **lambda → app → infra-db → infra-k8s**. Cada um recusa rodar enquanto um dependente ainda estiver de pé (parâmetros SSM em `/tech-challenge/<escopo>`). Detalhes no [guia de deploy](./deploy_aws.md#6-parar-a-infraestrutura-e-evitar-custos).

Conferir sobras: EKS, RDS, NLB, ENIs da Lambda (levam alguns minutos para sumir) e parâmetros SSM:

```bash
aws ssm get-parameters-by-path --path /tech-challenge --recursive --query 'Parameters[].Name'
```

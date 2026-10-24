# Runbook: bootstrap AWS real + GitHub Actions (Terraform)

Passos **manuais via CLI** que o Terraform **não** cria (e o **AWS destroy** também não apaga): state remoto + OIDC do GitHub Actions.

CI em PR: [`.github/workflows/iac.yml`](../../.github/workflows/iac.yml) (só `init -backend=false`, `validate` e lint do Helm, **sem** assumir role na AWS). Subir o ambiente: **Actions -> AWS deploy** (role **apply**, branch `master`). Parar o custo: **Actions -> AWS destroy** (mesma role apply; digite `destroy`).

---

## Resumo dos recursos

| Recurso | Onde é configurado |
|---|---|
| Bucket S3 + tabela DynamoDB (state/lock) | CLI (setup) |
| IdP `token.actions.githubusercontent.com` + roles `gha-plan` / `gha-apply` | CLI (setup) |
| Grupo IAM `tech-challenge-devs` (acesso AWS dos devs) | CLI (setup) |
| Access entries EKS dos devs | workflow **AWS deploy** (variable `EKS_ADMIN_PRINCIPAL_ARNS`) |
| Variables do Actions (`TF_STATE_*`, `AWS_ROLE_ARN_*`, `EKS_ADMIN_PRINCIPAL_ARNS`) | `gh variable set` ou via interface no GitHub |
| Secrets do Actions (`DB_*`, `JWT_*`, `MAIL_*`) | `gh secret set` ou via interface no GitHub |
| Cluster EKS + node group + ECR | workflow **AWS deploy** (trigger manual) |
| API Gateway (HTTP API) + VPC Link + NLB interno | workflow **AWS deploy** (URL no summary do job / `terraform output api_gateway_url`) |

> Role `tech-challenge-gha-apply` já criada antes do API Gateway, do RDS ou da Lambda? Rode de novo só o `cat > /tmp/gha-apply-inline.json` e o `aws iam put-role-policy` da seção abaixo (statements `ApiGatewayNlb`, `RdsMysql`, `RdsDescribe`, `RdsMysqlDefaultGroups`, `RdsServiceLinkedRole`, `LambdaAutenticacao`, `LambdaVpcEniCleanup`). O `put-role-policy` substitui a policy inline inteira, então exporte as variáveis do início da seção antes.

O NodePort `30080` **não** é acessível pela internet: o SG dos nodes só aceita o SG do NLB interno, que só aceita o SG do VPC Link. O NLB cobra por hora enquanto existir (~US$ 0,0225/h); some no **AWS destroy**.

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

## OIDC GitHub Actions (duas roles)

Pré-requisito: AWS CLI autenticado **na sua máquina** com permissão de IAM. Ajuste `GH_OWNER` / `GH_REPO` se o remote não for `oesleik/tech-chalange`.

```bash
export ACCOUNT_ID="$(aws sts get-caller-identity --query Account --output text)"
export GH_OWNER=oesleik
export GH_REPO=tech-chalange
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

### Role plan (PR > `terraform plan`)

Trust: só `pull_request` neste repo **e** só o workflow `iac.yml`. Permissão: `ReadOnlyAccess` + lock do state (plan precisa do lock; não precisa gravar o tfstate).

Um PR malicioso que altere o workflow ainda só assume esta role — não consegue `apply`/`destroy`.

```bash
cat > /tmp/gha-plan-trust.json <<EOF
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
          "token.actions.githubusercontent.com:aud": "sts.amazonaws.com"
        },
        "StringLike": {
          "token.actions.githubusercontent.com:sub": "repo:${GH_OWNER}/${GH_REPO}:pull_request",
          "token.actions.githubusercontent.com:job_workflow_ref": "${GH_OWNER}/${GH_REPO}/.github/workflows/iac.yml@*"
        }
      }
    }
  ]
}
EOF

cat > /tmp/gha-plan-inline.json <<EOF
{
  "Version": "2012-10-17",
  "Statement": [
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
    }
  ]
}
EOF

aws iam create-role \
  --role-name tech-challenge-gha-plan \
  --assume-role-policy-document file:///tmp/gha-plan-trust.json

aws iam attach-role-policy \
  --role-name tech-challenge-gha-plan \
  --policy-arn arn:aws:iam::aws:policy/ReadOnlyAccess

aws iam put-role-policy \
  --role-name tech-challenge-gha-plan \
  --policy-name terraform-state-lock \
  --policy-document file:///tmp/gha-plan-inline.json
```

### Role apply (deploy e destroy na `master`)

Trust: só `refs/heads/master` **e** só `aws-deploy.yml` / `aws-destroy.yml`. Sem isso, qualquer workflow novo na `master` poderia assumir a role.

A policy cobre o HCL atual (VPC, IAM de EKS/Lambda, EKS, ECR, API Gateway/NLB, RDS, Lambda) + state. O `Deny` no final impede o apply de apagar o próprio setup de OIDC.

```bash
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
          "token.actions.githubusercontent.com:job_workflow_ref": [
            "${GH_OWNER}/${GH_REPO}/.github/workflows/aws-deploy.yml@refs/heads/master",
            "${GH_OWNER}/${GH_REPO}/.github/workflows/aws-destroy.yml@refs/heads/master"
          ]
        },
        "StringLike": {
          "token.actions.githubusercontent.com:sub": "repo:${GH_OWNER}/${GH_REPO}:ref:refs/heads/master"
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
      "Resource": "arn:aws:s3:::${STATE_BUCKET}/tech-challenge/terraform.tfstate"
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
        "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-gha-plan",
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

aws iam put-role-policy \
  --role-name tech-challenge-gha-apply \
  --policy-name tech-challenge-gha-apply \
  --policy-document file:///tmp/gha-apply-inline.json
```

PR de **fork** não assume estas roles (`sub` é outro repositório).

---

## GitHub: variables

Os ARNs não são secretos (a proteção é o **trust** da role).

```bash
gh variable set TF_STATE_BUCKET --repo "${GH_OWNER}/${GH_REPO}" --body "${STATE_BUCKET}"
gh variable set TF_STATE_LOCK_TABLE --repo "${GH_OWNER}/${GH_REPO}" --body "tech-challenge-tfstate-lock"
gh variable set AWS_ROLE_ARN_PLAN --repo "${GH_OWNER}/${GH_REPO}" --body "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-gha-plan"
gh variable set AWS_ROLE_ARN_APPLY --repo "${GH_OWNER}/${GH_REPO}" --body "arn:aws:iam::${ACCOUNT_ID}:role/tech-challenge-gha-apply"
```

| Variable | Uso |
|---|---|
| `TF_STATE_BUCKET` | backend S3 |
| `TF_STATE_LOCK_TABLE` | lock DynamoDB |
| `AWS_ROLE_ARN_PLAN` | sem uso hoje (role plan; ver seção da role) |
| `AWS_ROLE_ARN_APPLY` | **AWS deploy** e **AWS destroy** |
| `EKS_ADMIN_PRINCIPAL_ARNS` | **AWS deploy** (opcional; ver [Acesso dos devs](#acesso-dos-devs-iam--eks)) |

---

## Acesso dos devs (IAM + EKS)

São duas camadas independentes, e o dev precisa das duas:

| Camada | Libera | Onde |
|---|---|---|
| IAM (grupo `tech-challenge-devs`) | API/console da AWS para os serviços do projeto | CLI, uma vez por conta |
| Access entry do EKS | Recursos do Kubernetes (pods, deployments etc.) no console e no `kubectl` | Terraform, via variable `EKS_ADMIN_PRINCIPAL_ARNS` |

Pré-requisitos: usuários IAM já criados (senha de console/MFA/access keys são manuais) e as variáveis do início da seção [OIDC](#oidc-github-actions-duas-roles) exportadas.

### Grupo IAM

Acesso total aos recursos que o Terraform gerencia, restrito pelo prefixo `tech-challenge` onde o serviço permite (EKS, ECR, RDS, Lambda, CloudWatch Logs, ELB). Leitura (`Describe*`/`List*`) vale para a conta inteira, para o console conseguir listar.

Ficam **sem restrição** (conta inteira): EC2/VPC, Auto Scaling, API Gateway e métricas do CloudWatch. Os ARNs desses recursos usam IDs gerados (`vpc-…`, `/apis/abc123`), não o nome, e o HCL não aplica uma tag comum para filtrar por condição.

IAM é só leitura. A exceção é `iam:PassRole` nas roles do projeto, necessário para editar a configuração da Lambda ou do node group pelo console; não permite alterar as roles.

Criar recursos que geram custo fica só com o Terraform (role `gha-apply`, no **AWS deploy**). Os `Deny` no final bloqueiam instâncias EC2, volumes/snapshots/AMIs, Elastic IPs, NAT Gateway, VPC endpoints, VPN, ASGs, clusters/node groups EKS, instâncias/restores/snapshots RDS, Lambdas, load balancers, APIs do API Gateway, log groups, dashboards/alarmes/métricas customizadas. Alterar e apagar os recursos existentes continua liberado, inclusive mudanças que aumentam custo (escalar o node group, trocar a classe do RDS). Um `Deny` vale mesmo que o usuário tenha outra policy permissiva (ex.: `AdministratorAccess`): quem precisar criar recursos manualmente não pode estar no grupo.

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
gh variable set EKS_ADMIN_PRINCIPAL_ARNS --repo "${GH_OWNER}/${GH_REPO}" \
  --body "[\"arn:aws:iam::${ACCOUNT_ID}:user/fulano\", \"arn:aws:iam::${ACCOUNT_ID}:user/beltrano\"]"
```

Cada ARN ganha `AmazonEKSClusterAdminPolicy` no cluster inteiro (`infra/eks.tf`). Access entry criada antes manualmente no console dá `ResourceInUseException` no apply: apague-a no console (EKS > cluster > Access) antes do deploy.

`kubectl` local: `aws eks update-kubeconfig --name tech-challenge --region us-east-1` com as credenciais do próprio usuário (não de uma role assumida, senão o ARN não bate com o da access entry).

---

## GitHub: secrets (aplicação)

```bash
# Valores novos para o cluster (não reutilize o .env local).
# gh secret set sem --body pede o valor no terminal.
gh secret set DB_PASSWORD --repo "${GH_OWNER}/${GH_REPO}"
gh secret set DB_ROOT_PASSWORD --repo "${GH_OWNER}/${GH_REPO}"
gh secret set JWT_SECRET --repo "${GH_OWNER}/${GH_REPO}"
gh secret set LAMBDA_DB_PASSWORD --repo "${GH_OWNER}/${GH_REPO}"
gh secret set OS_EMAIL_ACTION_TOKEN_SECRET --repo "${GH_OWNER}/${GH_REPO}"
gh secret set MAIL_USERNAME --repo "${GH_OWNER}/${GH_REPO}"
gh secret set MAIL_PASSWORD --repo "${GH_OWNER}/${GH_REPO}"
```

| Secret | Uso |
|---|---|
| `DB_PASSWORD` | Usuário `app_user` (PHP, `init-file` MySQL) |
| `DB_ROOT_PASSWORD` | Root do MySQL |
| `JWT_SECRET` | JWT administrativo |
| `LAMBDA_DB_PASSWORD` | Usuário MySQL somente leitura `autenticacao` (Lambda) |
| `OS_EMAIL_ACTION_TOKEN_SECRET` | JWT dos links de e-mail da OS |
| `MAIL_USERNAME` | SMTP |
| `MAIL_PASSWORD` | SMTP |

Minikube/MiniStack continuam com os placeholders de `values.yaml`.

---

## MiniStack vs AWS no Terraform

| | MiniStack (`make aws-local-up`) | AWS / CI |
|---|---|---|
| tfvars | `env/ministack.tfvars` | `env/aws.tfvars` |
| Backend | `backend.tf` local gerado no `make aws-local-up` | S3 gerado no workflow |
| ECR | não criado (`use_ministack = true`) | criado |
| EBS CSI | não utilizado | removido da arquitetura AWS com RDS |
| Instance type | default `t3.medium` | `aws.tfvars` + override no **AWS deploy** |

Não rode `apply -var-file=env/aws.tfvars` apontando para o MiniStack, nem o inverso com o state S3.

---

## Derrubar tudo (evitar custos)

O item caro é o **control plane EKS** enquanto o cluster existir. Workflow **AWS destroy** (confirm = `destroy`) é o caminho do CI.

Equivalente local, com backend S3 já configurado. Em instalações antigas, apague o namespace antes do destroy: versões anteriores executavam MySQL em PVC do EBS, e volumes remanescentes podem continuar gerando custos se ficarem órfãos. O workflow AWS destroy mantém uma etapa de limpeza para esse caso legado.

```bash
kubectl delete namespace tech-challenge --wait --timeout=5m
kubectl wait --for=delete pv --all --timeout=5m
terraform -chdir=infra destroy -var-file=env/aws.tfvars
```

Conferir sobras: EC2 > Volumes, estado **available**, tag `kubernetes.io/created-for/pvc/name = mysql-pvc`.

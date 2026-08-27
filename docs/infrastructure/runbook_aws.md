# Runbook: bootstrap AWS real + GitHub Actions (Terraform)

Passos **manuais via CLI** que o Terraform **não** cria (e o **AWS destroy** também não apaga): state remoto + OIDC do GitHub Actions.

CI em PR: [`.github/workflows/iac.yml`](../../.github/workflows/iac.yml) (role **plan**). Subir o ambiente: **Actions -> AWS deploy** (role **apply**, branch `master`). Parar o custo: **Actions -> AWS destroy** (mesma role apply; digite `destroy`).

---

## Resumo dos recursos

| Recurso | Onde é configurado |
|---|---|
| Bucket S3 + tabela DynamoDB (state/lock) | CLI (setup) |
| IdP `token.actions.githubusercontent.com` + roles `gha-plan` / `gha-apply` | CLI (setup) |
| Variables do Actions (`TF_STATE_*`, `AWS_ROLE_ARN_*`) | `gh variable set` ou via interface no GitHub |
| Cluster EKS + node group + ECR + EBS CSI | workflow **AWS deploy** (trigger manual) |

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

A policy cobre o HCL atual (VPC, IAM de EKS, EKS, EBS CSI, ECR) + state. O `Deny` no final impede o apply de apagar o próprio setup de OIDC.

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
            "eks-nodegroup.amazonaws.com"
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
| `AWS_ROLE_ARN_PLAN` | workflow **IaC** |
| `AWS_ROLE_ARN_APPLY` | **AWS deploy** e **AWS destroy** |

---

## MiniStack vs AWS no Terraform

| | MiniStack (`make aws-local-up`) | AWS / CI |
|---|---|---|
| tfvars | `env/ministack.tfvars` | `env/aws.tfvars` |
| Backend | `backend.tf` local gerado no `make aws-local-up` | S3 gerado no workflow |
| ECR, EBS CSI | não criados (`use_ministack = true`) | criados |
| Instance type | default `t3.small` | `aws.tfvars` + override no **AWS deploy** |

Não rode `apply -var-file=env/aws.tfvars` apontando para o MiniStack, nem o inverso com o state S3.

---

## Derrubar tudo (evitar custos)

O item caro é o **control plane EKS** enquanto o cluster existir. Workflow **AWS destroy** (confirm = `destroy`) é o caminho do CI.

Equivalente local, com backend S3 já configurado:

```bash
terraform -chdir=infra destroy -var-file=env/aws.tfvars
```

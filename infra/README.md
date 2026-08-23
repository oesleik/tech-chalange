# Terraform (`infra/`)

Código único. O alvo muda só pelo tfvars.

```bash
# AWS local (MiniStack precisa estar rodando)
terraform -chdir=infra init
terraform -chdir=infra apply -var-file=env/ministack.tfvars

# AWS real (state remoto S3)
terraform -chdir=infra init -backend-config=env/backend-aws.hcl
terraform -chdir=infra plan  -var-file=env/aws.tfvars
terraform -chdir=infra apply -var-file=env/aws.tfvars
```

O kubeconfig do MiniStack **não** é `aws eks update-kubeconfig`. Use `make aws-local-kubeconfig`.

Setup da conta AWS (S3/Dynamo + OIDC do GitHub Actions) e workflows manuais de deploy/destroy: [`docs/infrastructure/runbook_aws.md`](../docs/infrastructure/runbook_aws.md).

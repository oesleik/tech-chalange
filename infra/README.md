```bash
# AWS local (MiniStack precisa estar rodando)
terraform -chdir=infra init
terraform -chdir=infra apply -var-file=env/ministack.tfvars

# AWS
terraform -chdir=infra apply -var-file=env/aws.tfvars
```

O kubeconfig do MiniStack **não** é `aws eks update-kubeconfig`. Use `make aws-local-kubeconfig`.

# Infraestrutura Kubernetes

> Tech Challenge Fase 2

Este documento cobre três fluxos:

| Alvo | Cluster | Como sobe | Values Helm |
|---|---|---|---|
| Dia a dia | Minikube | `make k8s-up` | `values-minikube.yaml` |
| AWS local (sem custo) | k3s via MiniStack | `make aws-local-up` (Terraform `env/ministack.tfvars` + Helm) | `values-aws-local.yaml` |
| AWS | EKS | Github Actions (Terraform `env/aws.tfvars` + Helm) | `values-aws.yaml` |

Para Docker Compose, consulte o `README.md` principal. Decisões em [ADR-004](../adr/004-minikube-ministack-eks.md).

---

## Sumário

1. [Stack de infraestrutura](#1-stack-de-infraestrutura)
2. [Chart Helm](#2-chart-helm)
3. [Pré-requisitos](#3-pré-requisitos)
4. [Secrets](#4-secrets)
5. [Deploy local com Minikube](#5-deploy-local-com-minikube)
6. [AWS local com MiniStack](#6-aws-local-com-ministack)
7. [Comandos](#7-comandos)
8. [HPA](#8-hpa-auto-scaling)
9. [Persistência](#9-persistência-dos-dados)
10. [AWS real (EKS)](#10-aws-real-eks)
11. [O que o MiniStack valida e o que não](#11-o-que-o-ministack-valida-e-o-que-nao)
12. [Runbook AWS](./runbook_aws.md)

---

## 1. Stack de infraestrutura

| Componente | Tecnologia | Detalhes |
|---|---|---|
| Servidor web | Nginx Alpine | Proxy reverso + arquivos estáticos via initContainer |
| Aplicação | PHP 8.4-FPM | Slim Framework 4 |
| Banco de dados | MySQL 9 | Dentro do cluster via PVC |
| Interface do banco | phpMyAdmin | **Só no Minikube** |
| Workload | Helm chart `charts/tech-challenge` | Um pacote |
| Cluster local (app) | Minikube | Driver Docker |
| AWS local | MiniStack + k3s | Terraform em `infra/` |
| Cluster AWS | AWS EKS | Mesmo Terraform, `env/aws.tfvars` |

A imagem Docker é a mesma usada no Docker Compose. No Minikube e no MiniStack ela é **importada** no cluster (`imagePullPolicy: Never`). Push para ECR fica para a AWS real.

---

## 2. Chart Helm

```
charts/tech-challenge/
├── Chart.yaml
├── values.yaml                 # defaults
├── values-minikube.yaml
├── values-aws-local.yaml
├── values-aws.yaml
├── files/nginx-default.conf
└── templates/                  # Deployments, Services, HPA, Secret, ConfigMaps
```

**Por que o initContainer no Nginx?**
No Docker Compose, Nginx e PHP compartilhavam o mesmo volume com o código. No K8s, cada pod tem seu próprio filesystem isolado. O initContainer copia a pasta `public/` da imagem PHP para um volume compartilhado com o Nginx antes de ele subir, resolvendo esse isolamento.

---

## 3. Pré-requisitos

- Docker (driver do Minikube e socket do MiniStack)
- Minikube 1.30+
- kubectl 1.27+
- Helm 3.14+
- Terraform 1.5+ (só o fluxo MiniStack)

---

## 4. Secrets

O chart cria o Secret `app-secrets` a partir de `.Values.secrets`. Os values locais usam placeholders de desenvolvimento.

No EKS o workflow **AWS deploy** sobrescreve o bloco inteiro (`DB_*`, `JWT_*`, `MAIL_*`) com GitHub Secrets. Setup: [runbook_aws.md](./runbook_aws.md).

Não commite senhas reais.

---

## 5. Deploy local com Minikube

```bash
make k8s-up
make k8s-migrate
make k8s-url
```

| Serviço | URL | Alvo Make |
|---|---|---|
| Aplicação (API) | `http://localhost:30080` | `make k8s-url` |
| Swagger | `http://localhost:30080/docs/index.html` | `make k8s-url` |
| phpMyAdmin | `http://localhost:30081` | `make k8s-phpmyadmin-url` |

`make k8s-url` e `make k8s-phpmyadmin-url` fazem `kubectl port-forward`. O NodePort no chart continua; o IP do nó (`minikube ip`) não é usado pois no WSL2 esse IP não é acessível a partir do host.

```bash
make k8s-jwt-token
```

O `make k8s-up` para o Docker Compose, sobe o Minikube (2 CPUs / 3072MB), habilita metrics-server, builda a imagem **dentro** do Minikube e faz `helm upgrade --install` com `values-minikube.yaml`.

---

## 6. AWS local com MiniStack

O MiniStack emula a API AWS em `localhost:4566`. O Terraform (`infra/` + `env/ministack.tfvars`) cria VPC, IAM, EKS e node group. O `CreateCluster` do MiniStack sobe um **k3s**. O mesmo chart Helm é instalado com `values-aws-local.yaml`.

```bash
make aws-local-up
make aws-local-migrate
make aws-local-url    # port-forward localhost:30080
```

phpMyAdmin **não** sobe neste fluxo.

### Kubeconfig é um adapter

Na AWS real: `aws eks update-kubeconfig`.

No MiniStack: `make aws-local-kubeconfig` extrai `/etc/rancher/k3s/k3s.yaml` do container `ministack-eks-us-east-1-tech-challenge`. Isso **não** é o comando da AWS. O script está em `scripts/ministack-kubeconfig.sh`.

### Derrubar

```bash
make aws-local-down
```

Destroy do Terraform (remove o k3s) e `docker compose` do MiniStack. O ambiente é **efêmero**.

Não misture Minikube e MiniStack ao mesmo tempo: `aws-local-up` tenta parar os dois ambientes da app.

---

## 7. Comandos

### Minikube

| Comando | O que faz |
|---|---|
| `make k8s-up` | Minikube + build da imagem + Helm (values-minikube) |
| `make k8s-down` | Para o Minikube preservando o cluster |
| `make k8s-destroy` | Helm uninstall + namespace + stop |
| `make k8s-reset` | `minikube delete` |
| `make k8s-status` | Recursos no namespace |
| `make k8s-url` | Port-forward Nginx :30080 |
| `make k8s-phpmyadmin-url` | Port-forward phpMyAdmin :30081 |
| `make k8s-migrate` | Migrations no pod PHP |
| `make k8s-jwt-token` | Token JWT |
| `make k8s-logs-php` / `k8s-logs-nginx` | Logs |
| `make k8s-shell` | Shell no pod PHP |
| `make k8s-security-scan` | OWASP ZAP contra o Minikube |

### MiniStack

| Comando | O que faz |
|---|---|
| `make aws-local-up` | MiniStack + Terraform apply + import da imagem + Helm |
| `make aws-local-kubeconfig` | Adapter k3s → `.kube/ministack.yaml` |
| `make aws-local-down` | Terraform destroy + para MiniStack |
| `make aws-local-status` | Recursos no k3s |
| `make aws-local-url` | Port-forward Nginx :30080 |
| `make aws-local-migrate` | Migrations no k3s |
| `make aws-local-logs-php` / `aws-local-logs-nginx` | Logs |

`make help` lista todos.

---

## 8. HPA - Auto-scaling

| Alvo | PHP | Nginx |
|---|---|---|
| Minikube / defaults | 2–10 | 2–5 |
| AWS (`values-aws`) | 1–4 | 1–3 |

Métrica: CPU 70% (PHP também memória 80%).

HPA só reage com **metrics-server**. Minikube: addon. MiniStack: depende do k3s. EKS: o CI instala o chart em `kube-system` (`--kubelet-insecure-tls`).

```bash
kubectl get hpa -n tech-challenge
```

`TARGETS` tem que mostrar `%`, não `<unknown>`. Sem métrica o HPA não escala.

HPA muda **réplicas do Deployment**, não cria EC2. Com node pequeno demais, scale-up pode deixar pods **Pending**. No **AWS deploy**, o input `node_instance_type` (`t3.medium` / `t3.large`) é o jeito de ver pods extras *Running* sem desligar o HPA.

Load test no Minikube:

```bash
TOKEN=$(make k8s-jwt-token 2>/dev/null | tail -1)
# em outro terminal: make k8s-url
ab -n 10000 -c 100 -H "Authorization: Bearer $TOKEN" http://localhost:30080/clientes
```

---

## 9. Persistência dos dados

MySQL via PVC.

| Comando | Pods | Dados do banco |
|---|---|---|
| `make k8s-down` | parados | preservados no Minikube |
| `make k8s-destroy` | removidos | volume do Minikube pode persistir |
| `make k8s-reset` | `minikube delete` | perdidos |
| `make aws-local-down` | k3s destruído | **perdidos** (ambiente efêmero) |

---

## 10. AWS real (EKS)

Setup inicial manual (OIDC do GitHub Actions, bucket de state): [runbook_aws.md](./runbook_aws.md).

CI:

- **PR** (`infra/**`): `terraform plan` - não aplica
- **AWS deploy** (trigger manual): apply + ECR + Helm + migrations
- **AWS destroy** (trigger manual): derruba o EKS e para o custo


Resumo:

| Aspecto | Minikube | MiniStack | AWS EKS |
|---|---|---|---|
| Cluster | Minikube | k3s via MiniStack | EKS gerenciado |
| IaC | - | Terraform `ministack.tfvars` | Mesmo HCL, `aws.tfvars` |
| Workload | Helm values-minikube | Helm values-aws-local | Helm values-aws |
| Imagem | docker-env + build | build host + import k3s | push ECR |
| Nginx | NodePort + port-forward | NodePort + port-forward | NodePort 30080 no IP do node |
| phpMyAdmin | sim | não | não |
| Secrets | placeholders no chart | placeholders no chart | GitHub Secrets |
| kubeconfig | minikube | adapter `docker exec` | `aws eks update-kubeconfig` |

---

## 11. O que o MiniStack valida e o que não

**Valida:** `terraform apply`/`destroy` contra API AWS emulada; Helm no k3s; MySQL dentro do cluster; probes; artefato de deploy.

**Não valida:** IRSA e enforcement de IAM; security groups reais; pull ECR; ALB; EBS CSI; autenticação EKS (`aws-iam-authenticator`); custo e quotas.

Detalhe em [ADR-004](../adr/004-minikube-ministack-eks.md).

---

*FIAP PosTech Software Architecture — Tech Challenge Fase 2*

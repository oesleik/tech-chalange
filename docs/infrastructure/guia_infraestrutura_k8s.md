# Infraestrutura Kubernetes

A aplicação roda em Kubernetes só na AWS (EKS). O desenvolvimento local é Docker Compose (ver `README.md` principal). Os fluxos locais com Minikube e MiniStack foram removidos na divisão em quatro repositórios ([ADR-013](../adr/013-divisao-repositorios.md), que substitui o [ADR-004](../adr/004-minikube-ministack-eks.md)).

| Camada | Repositório |
|---|---|
| Cluster EKS, VPC, ECR, NLB, API Gateway, metrics-server, New Relic | [tech-challenge-infra-k8s](https://github.com/oesleik/tech-challenge-infra-k8s) |
| RDS MySQL | [tech-challenge-infra-db](https://github.com/oesleik/tech-challenge-infra-db) |
| Workload (este chart) | tech-chalange |
| Lambda de autenticação | [tech-challenge-lambda](https://github.com/oesleik/tech-challenge-lambda) |

---

## 1. Stack

| Componente | Tecnologia | Detalhes |
|---|---|---|
| Servidor web | Nginx Alpine | Proxy reverso + arquivos estáticos via initContainer |
| Aplicação | PHP 8.4-FPM | Slim Framework 4 |
| Banco de dados | Amazon RDS MySQL | Fora do cluster (infra-db) |
| Workload | Helm chart `charts/tech-challenge` | Um pacote |
| Cluster | AWS EKS | infra-k8s |

A imagem Docker é a mesma do Docker Compose, publicada no ECR pelo workflow de Deploy com a tag do commit.

---

## 2. Chart Helm

```
charts/tech-challenge/
├── Chart.yaml
├── values.yaml                 # defaults
├── values-aws.yaml             # overrides do EKS (réplicas, recursos, New Relic)
├── files/nginx-default.conf
└── templates/                  # Deployments, Services, HPA, Secret, ConfigMaps, Job rds-user-setup
```

Valores que só existem em tempo de deploy entram por `--set` no [`deploy.yml`](../../.github/workflows/deploy.yml): `php.image` (ECR), `config.DB_HOST` e `config.DB_DATABASE` (lidos do SSM publicado pelo infra-db).

**Por que o initContainer no Nginx?**
No Docker Compose, Nginx e PHP compartilhavam o mesmo volume com o código. No K8s, cada pod tem seu próprio filesystem isolado. O initContainer copia a pasta `public/` da imagem PHP para um volume compartilhado com o Nginx antes de ele subir, resolvendo esse isolamento.

**Job `rds-user-setup`:** cria (ou atualiza a senha de) `app_user` e `autenticacao` no RDS usando a senha master. O Secret `rds-admin-secrets` com essa senha é criado pelo workflow antes do Helm e apagado no fim, mesmo em caso de falha.

Validação no CI: `helm lint` (defaults e `values-aws.yaml`) e `helm template` conferindo que o Job é renderizado.

---

## 3. Secrets

O chart cria o Secret `app-secrets` a partir de `.Values.secrets`. O `values.yaml` tem placeholders de desenvolvimento; o workflow de Deploy sobrescreve o bloco inteiro (`DB_PASSWORD`, `JWT_SECRET`, `OS_EMAIL_ACTION_TOKEN_SECRET`, `MAIL_*`) com GitHub Secrets. Setup: [runbook_aws.md](./runbook_aws.md#variables-e-secrets-por-repositório).

Não commite senhas reais.

---

## 4. HPA - Auto-scaling

| Alvo | PHP | Nginx |
|---|---|---|
| defaults (`values.yaml`) | 2–10 | 2–5 |
| AWS (`values-aws.yaml`) | 1–4 | 1–3 |

Métrica: CPU 70% (PHP também memória 80%).

HPA só reage com **metrics-server**, instalado em `kube-system` pelo Deploy do infra-k8s (`--kubelet-insecure-tls`).

```bash
kubectl get hpa -n tech-challenge
```

`TARGETS` tem que mostrar `%`, não `<unknown>`. Sem métrica o HPA não escala.

HPA muda **réplicas do Deployment**, não cria EC2. Com node pequeno demais, scale-up pode deixar pods **Pending**. O input `node_instance_type` do Deploy do infra-k8s (`t3.small` / `t3.medium` / `t3.large`) é o jeito de ver pods extras *Running* sem desligar o HPA.

Load test (token gerado pela Lambda):

```bash
TOKEN=$(curl -s -X POST "$API_URL/autenticacao" -H 'content-type: application/json' \
  -d '{"cpf_cnpj": "<cpf de um cliente cadastrado>"}' | jq -r .access_token)
ab -n 10000 -c 100 -H "Authorization: Bearer $TOKEN" "$API_URL/clientes"
```

---

## 5. Entrada e rede

```
Cliente → API Gateway (HTTP API) → VPC Link → NLB interno → NodePort 30080 (nginx) → php → RDS
                 └── POST /autenticacao → Lambda → RDS
```

O SG dos nodes só aceita o NodePort a partir do NLB, e o NLB só aceita o VPC Link. O acesso do EKS ao RDS é uma regra criada pelo infra-db (ver [README do infra-db](https://github.com/oesleik/tech-challenge-infra-db#quem-cria-cada-regra-de-acesso-ao-banco)).

---

## 6. Operação

Deploy, validação, diagnóstico e destroy: [deploy_aws.md](./deploy_aws.md). Setup inicial (OIDC, state, variables e secrets): [runbook_aws.md](./runbook_aws.md).

# Infraestrutura Kubernetes

> Tech Challenge Fase 2

Este documento cobre o deploy em Kubernetes local (Minikube) e os ajustes necessários para produção na AWS. Para execução local via Docker Compose, consulte o `README.md` principal.

---

## Sumário

1. [Stack de infraestrutura](#1-stack-de-infraestrutura)
2. [Estrutura dos manifestos K8s](#2-estrutura-dos-manifestos-k8s)
3. [Pré-requisitos](#3-pré-requisitos)
4. [Configuração dos Secrets](#4-configuração-dos-secrets)
5. [Deploy local com Minikube](#5-deploy-local-com-minikube)
6. [Comandos Kubernetes](#6-comandos-kubernetes)
7. [HPA — Auto-scaling](#7-hpa--auto-scaling)
8. [Persistência dos dados](#8-persistência-dos-dados)
9. [Deploy na AWS (EKS)](#9-deploy-na-aws-eks)

---

## 1. Stack de infraestrutura

| Componente | Tecnologia | Detalhes |
|---|---|---|
| Servidor web | Nginx Alpine | Proxy reverso + arquivos estáticos via initContainer |
| Aplicação | PHP 8.4-FPM | Slim Framework 4, todas as dependências incluídas |
| Banco de dados | MySQL 9 | 1 replica, dados persistidos via PVC |
| Interface do banco | phpMyAdmin | Para desenvolvimento |
| Orquestração | Kubernetes | Deployments, Services, ConfigMap, Secret, HPA |
| Cluster local | Minikube | Driver Docker |
| Cluster cloud | AWS EKS | Via Terraform (ver `/infra`) |

A imagem Docker é a mesma usada no Docker Compose. O K8s consome a imagem buildada localmente no Minikube ou publicada no ECR (AWS).

---

## 2. Estrutura dos manifestos K8s

```
k8s/
├── namespace.yaml           # Namespace "tech-challenge" — isola os recursos
├── configmap.yaml           # Variáveis não sensíveis (host do banco, JWT issuer, etc.)
├── secret.yaml              # Variáveis sensíveis com placeholders (editar antes de aplicar)
├── nginx-configmap.yaml     # Configuração do Nginx injetada como ConfigMap
├── mysql/
│   ├── pvc.yaml             # Disco persistente para o banco (5Gi)
│   ├── deployment.yaml      # MySQL — 1 replica, strategy Recreate
│   └── service.yaml         # ClusterIP — DNS interno mysql:3306
├── php/
│   ├── deployment.yaml      # PHP-FPM — 2 replicas iniciais, imagePullPolicy Never
│   ├── service.yaml         # ClusterIP — DNS interno php:9000
│   └── hpa.yaml             # HPA — escala de 2 até 10 pods por CPU e memória
├── nginx/
│   ├── deployment.yaml      # Nginx — 2 replicas + initContainer copia arquivos do PHP
│   ├── service.yaml         # NodePort 30080 (local) — trocar para LoadBalancer na AWS
│   └── hpa.yaml             # HPA — escala de 2 até 5 pods por CPU
└── phpmyadmin/
    └── deployment.yaml      # phpMyAdmin + Service NodePort 30081
```

**Por que o initContainer no Nginx?**
No Docker Compose, Nginx e PHP compartilhavam o mesmo volume com o código. No K8s, cada pod tem seu próprio filesystem isolado. O initContainer copia a pasta `public/` da imagem PHP para um volume compartilhado com o Nginx antes de ele subir, resolvendo esse isolamento.

---

## 3. Pré-requisitos

- Docker (driver do Minikube)
- Minikube 1.30+
- kubectl 1.27+

---

## 4. Configuração dos Secrets

Para o arquivo `k8s/secret.yaml`. Edite antes de subir o ambiente:

```bash
nano k8s/secret.yaml
```

| Campo | Descrição |
|---|---|
| `DB_PASSWORD` | Senha do usuário da aplicação no MySQL |
| `DB_ROOT_PASSWORD` | Senha do root do MySQL |
| `JWT_SECRET` | Chave secreta para assinar os tokens JWT |
| `OS_EMAIL_ACTION_TOKEN_SECRET` | Chave para tokens de ação por email |
| `MAIL_USERNAME` | Usuário do servidor SMTP |
| `MAIL_PASSWORD` | Senha do servidor SMTP |

> Para desenvolvimento local pode usar os mesmos valores do `.env`.

---

## 5. Deploy local com Minikube

### Primeira vez

```bash
# sobe tudo: para o Docker Compose, inicia o Minikube,
# builda a imagem e aplica todos os manifestos
make k8s-up

# roda as migrations para criar as tabelas
make k8s-migrate

# exibe as URLs de acesso
make k8s-url
```

**URLs disponíveis:**

| Serviço | URL |
|---|---|
| Aplicação (API) | `http://<minikube-ip>:30080` |
| Swagger | `http://<minikube-ip>:30080/docs/index.html` |
| phpMyAdmin | `http://<minikube-ip>:30081` |

```bash
# para obter o IP do Minikube
minikube ip
```

### Autenticando no Swagger

```bash
make k8s-jwt-token
```

Cole o token no botão **Authorize** do Swagger UI.

### O que o `make k8s-up` faz

1. Para o Docker Compose (`docker compose down`)
2. Inicia o Minikube com 2 CPUs e 3072MB
3. Habilita o metrics-server (necessário para o HPA)
4. Builda a imagem PHP dentro do Minikube (`eval $(minikube docker-env)`)
5. Aplica os manifestos na ordem: namespace, configmap, secret, nginx-config, mysql, php, nginx, phpmyadmin

---

## 6. Comandos Kubernetes

| Comando | O que faz |
|---|---|
| `make k8s-up` | Sobe todo o ambiente Kubernetes |
| `make k8s-down` | Para o Minikube preservando pods e dados |
| `make k8s-destroy` | Remove o namespace e para o Minikube (dados persistem no volume) |
| `make k8s-reset` | `minikube delete` — destroi tudo inclusive dados do banco |
| `make k8s-status` | Status de todos os recursos no cluster |
| `make k8s-url` | URLs da aplicação, Swagger e phpMyAdmin |
| `make k8s-migrate` | Roda migrations no pod PHP |
| `make k8s-new-migration` | Cria nova migration no pod PHP |
| `make k8s-jwt-token` | Gera token JWT |
| `make k8s-jwt-token-email` | Gera token JWT de email (interativo) |
| `make k8s-test` | Roda testes unitários no pod PHP |
| `make k8s-lint` | Roda PHPStan com 512MB de memória |
| `make k8s-format` | Roda PHP CS Fixer no pod PHP |
| `make k8s-api-docs` | Rebuilda imagem com novo openapi.json e reinicia o Nginx |
| `make k8s-logs-php` | Logs do PHP em tempo real |
| `make k8s-logs-nginx` | Logs do Nginx em tempo real |
| `make k8s-shell` | Terminal dentro do pod PHP |
| `make k8s-security-scan` | OWASP ZAP contra a URL do Minikube |

---

## 7. HPA — Auto-scaling

### Configuração

| Componente | Min pods | Max pods | Escala por CPU | Escala por memória |
|---|---|---|---|---|
| PHP-FPM | 2 | 10 | > 70% de 250m | > 80% de 256Mi |
| Nginx | 2 | 5 | > 70% de 100m | não configurado |

### Verificando o status do HPA

```bash
kubectl get hpa -n tech-challenge
```

Saída esperada com o ambiente em repouso:

```
NAME        REFERENCE       TARGETS                         MINPODS  MAXPODS  REPLICAS
nginx-hpa   Deployment/nginx  cpu: 1%/70%                   2        5        2
php-hpa     Deployment/php    cpu: 1%/70%, memory: 21%/80%  2        10       2
```

### Testando o auto-scaling

Simule carga no PHP com o `kubectl run` para disparar o HPA:

```bash
# instala o ab (Apache Benchmark)
sudo apt install apache2-utils -y

TOKEN=$(make k8s-jwt-token 2>/dev/null | tail -1)
MINIKUBE_IP=$(minikube ip)

# 10000 requisicoes, 100 conexoes simultaneas
ab -n 10000 -c 100 \
  -H "Authorization: Bearer $TOKEN" \
  http://$MINIKUBE_IP:30080/clientes
```

Em outro terminal, observe o HPA escalando:

```bash
# atualiza a cada 5 segundos
watch -n 5 kubectl get hpa -n tech-challenge

# acompanha os pods sendo criados
watch -n 5 kubectl get pods -n tech-challenge
```

Quando a CPU médio dos pods PHP ultrapassar 70%, o HPA cria novos pods automaticamente até o máximo de 10. Quando parar o load test (Ctrl+C), o HPA remove os pods extras após alguns minutos, mas nunca abaixo de 2.

---

## 8. Persistência dos dados

O banco de dados é salvo em um PersistentVolumeClaim (PVC) no Minikube. O comportamento de cada comando:

| Comando | Pods | Dados do banco |
|---|---|---|
| `make k8s-down` | parados (Minikube pausado) | preservados |
| `make k8s-destroy` | removidos (namespace apagado) | preservados no volume físico |
| `make k8s-reset` | removidos (`minikube delete`) | perdidos permanentemente |

> Após `make k8s-destroy`, ao rodar `make k8s-up` novamente o PVC é recriado apontando para o mesmo volume físico, os dados voltam sem precisar rodar migrations novamente.

---

## 9. Deploy na AWS (EKS)

Os manifestos YAML são idênticos ao ambiente local. Apenas 3 ajustes são necessários.

### 9.1 Imagem Docker — ECR em vez de local

```bash
# autentique no ECR
aws ecr get-login-password --region us-east-1 | \
  docker login --username AWS --password-stdin \
  <conta>.dkr.ecr.us-east-1.amazonaws.com

# crie o repositorio (primeira vez)
aws ecr create-repository --repository-name tech-challenge-php --region us-east-1

# builde e envie a imagem
docker build -t tech-challenge-php:latest .
docker tag tech-challenge-php:latest \
  <conta>.dkr.ecr.us-east-1.amazonaws.com/tech-challenge-php:latest
docker push \
  <conta>.dkr.ecr.us-east-1.amazonaws.com/tech-challenge-php:latest
```

Nos arquivos `k8s/php/deployment.yaml` e `k8s/nginx/deployment.yaml` (initContainer):

```yaml
# DE (local):
image: tech-challenge-php:latest
imagePullPolicy: Never

# PARA (AWS):
image: <conta>.dkr.ecr.us-east-1.amazonaws.com/tech-challenge-php:latest
imagePullPolicy: Always
```

### 9.2 Service do Nginx — LoadBalancer

Em `k8s/nginx/service.yaml`:

```yaml
# DE (local):
type: NodePort
ports:
  - port: 80
    targetPort: 80
    nodePort: 30080

# PARA (AWS):
type: LoadBalancer
ports:
  - port: 80
    targetPort: 80
```

A AWS cria automaticamente um Application Load Balancer com DNS público.

### 9.3 Conectar ao cluster EKS e aplicar

```bash
# configura o kubeconfig para o cluster EKS
aws eks update-kubeconfig --name tech-challenge --region us-east-1

# aplica os manifestos
kubectl apply -f k8s/

# roda as migrations
kubectl exec -n tech-challenge \
  $(kubectl get pod -n tech-challenge -l app=php -o jsonpath='{.items[0].metadata.name}') \
  -- php src/cmd/migrations/migrate.php
```

### 9.4 Resumo local vs AWS

| Aspecto | Minikube (local) | AWS EKS |
|---|---|---|
| Cluster | VM local do Minikube | EKS via Terraform (`/infra`) |
| Imagem Docker | Buildada localmente | Publicada no ECR |
| `imagePullPolicy` | `Never` | `Always` |
| Service do Nginx | `NodePort :30080` | `LoadBalancer` (ALB automático) |
| Storage (PVC) | Disco local do Minikube | EBS provisionado automaticamente |
| Secrets | `secret.yaml` local | AWS Secrets Manager ou secret.yaml via CI/CD |
| Acesso ao cluster | `minikube kubectl` | `aws eks update-kubeconfig` |

---

*FIAP PosTech Software Architecture — Tech Challenge Fase 2*
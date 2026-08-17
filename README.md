# FIAP Software Architecture Tech Challenge

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=oesleik_tech-chalange&metric=alert_status&token=2c46cc11128f8457c02888b5bc7957e75c88d680)](https://sonarcloud.io/summary/new_code?id=oesleik_tech-chalange)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=oesleik_tech-chalange&metric=coverage&token=2c46cc11128f8457c02888b5bc7957e75c88d680)](https://sonarcloud.io/summary/new_code?id=oesleik_tech-chalange)

Stack completa containerizada pronta para desenvolvimento com:

- PHP 8.4 FPM
- Slim Framework 4.x
- MySQL 9
- Nginx
- phpMyAdmin

## 📋 Pré-requisitos

Ter o **Docker** e o **Docker Compose** instalados na máquina.

Para execução de comandos, também é necessário o **make**. No Windows, instale o projeto dentro do **WSL**. É possível rodar os comandos manualmente para não depender do make.

## 🚀 Como rodar

### 1. Configure o ambiente
```bash
# Configure o ambiente
cp .env.example .env
## Edite o .env se quiser alterar senhas ou nome do banco.

# Suba os containers
make up

# Execute as migrations
make migrate
```

#### Para desenvolvimento

Para ter o vendor local, funcionando com o intelisense da IDE:
```bash
mkdir -p vendor
docker compose cp php:/var/www/html/vendor/. ./vendor/

# Se erro de permissões
sudo chown -R "$(id -u):$(id -g)" vendor
```

Gerenciando dependências do composer:
```bash
# Entre no container
make shell

# Para instalar
composer require ...

# Para atualizar
composer update ...
```

## 🛠️ Comandos úteis

Vários comandos estão embutidos no Makefile, como rodar testes, lint, gerar token de autenticação, etc. Para visualizar todos os disponíveis, basta rodar:

```bash
make help
```

## 🌐 Acessos

| Serviço | URL |
|---|---|
| Aplicação | http://localhost |
| Health check | http://localhost/health |
| Swagger | http://localhost/docs/index.html |
| phpMyAdmin | http://localhost:8080 |
| MySQL (externo) | localhost:3306 |

**Credenciais do phpMyAdmin:**
- Usuário: `app_user`
- Senha: `secret`

## 🗂️ Estrutura do projeto

```
tech-chalange/
├── Dockerfile                  # Imagem PHP 8.4 FPM customizada
├── docker-compose.yml          # Orquestração dos containers
├── composer.json               # Dependências PHP (Slim 4)
├── .env                        # Variáveis de ambiente (não versionar!)
├── .env.example                # Modelo do .env
├── docker/
│   ├── nginx/default.conf      # Configuração do Nginx
│   ├── php/php.ini             # Configurações customizadas do PHP
│   └── mysql/init/             # Scripts SQL executados na criação do banco
├── charts/tech-challenge/      # Helm (Minikube, MiniStack, AWS)
├── .github/workflows/          # Fluxos de CI/CD
├── public/index.php            # Entry point da aplicação (Slim 4)
└── src/                        # Classes da aplicação (namespace App\)
```

## 🔐 Autenticação (JWT)

As rotas administrativas são protegidas por JWT. Gere um token manualmente:

```bash
make jwt-token
```

No [Swagger UI](http://localhost/docs/index.html), clique em **Authorize** 🔓 no topo da página e cole o token.

As rotas de aprovação e rejeição da ordem de serviço são protegidas por outro JWT, que é enviado via email. Para gerar manualmente:

```bash
make jwt-token-email
```
> Este token contem o id da ordem de serviço nos claims, o comando irá solicitar o id para a geração

## 🛡️ Análise de vulnerabilidades (OWASP ZAP)

O projeto utiliza o [OWASP ZAP](https://www.zaproxy.org/) para análise de segurança via scan de baseline. Não requer instalação, roda direto via Docker.

```bash
make security-scan
```

O scan roda o spider por ~1 minuto e faz análise passiva (sem ataques ativos), reportando problemas de headers de segurança, vazamento de informações, cookies, etc.

Os relatórios são gerados em `docs/security/`:
- `zap-baseline-report.html` — relatório visual (abra no navegador)
- `zap-baseline-report.json` — relatório em formato JSON

Para visualizar:

```bash
xdg-open docs/security/zap-baseline-report.html
```

## 📐 Documentação DDD

Os artefatos de modelagem do domínio (Domain Storytelling, Event Storming,
Domínios e Contextos Delimitados) estão em [`docs/ddd/`](./docs/ddd/README.md).

## 📐 Documentação sobre arquitetura

- **ADRs:** [`docs/adr/`](./docs/adr/README.md)
- **DAS:** [`docs/arch/DAS.md`](./docs/arch/DAS.md)

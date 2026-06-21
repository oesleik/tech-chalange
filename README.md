# tech-chalange
## FIAP Software Architecture Tech Challenge
 
---

# 🐘 PHP + Slim 4 + MySQL + Docker

Stack completa containerizada com **Nginx**, **PHP 8.4 FPM**, **MySQL 9** e **phpMyAdmin**, pronta para desenvolvimento.

---

## 📋 Pré-requisitos

Ter o **Docker** e o **Docker Compose** instalados na máquina.

### Windows / Mac
Instale o [Docker Desktop](https://www.docker.com/products/docker-desktop) — o Compose já vem incluído.

### Linux (Ubuntu/Debian)
Instale o [Docker Engine](https://docs.docker.com/engine/install/ubuntu/)
```bash
sudo usermod -aG docker $USER
newgrp docker
```

---

## 🚀 Como rodar

### 1. Clone o repositório
```bash
git clone <url-do-repositorio>
cd tech-chalange
```

### 2. Configure o ambiente
```bash
cp .env.example .env
```
> Edite o `.env` se quiser alterar senhas ou nome do banco.

### 3. Suba os containers
```bash
make up
```

Na primeira execução o Docker irá:
- Baixar as imagens (Nginx, MySQL, phpMyAdmin)
- Buildar a imagem PHP com todas as extensões
- Instalar as dependências via Composer automaticamente

### 4. Execute as migrations
```bash
make migrate
```

---

## 🌐 Acessos

| Serviço | URL |
|---|---|
| Aplicação | http://localhost |
| Health check | http://localhost/health |
| phpMyAdmin | http://localhost:8080 |
| MySQL (externo) | localhost:3306 |

**Credenciais do phpMyAdmin:**
- Usuário: `app_user`
- Senha: `secret`

---

## 🗂️ Estrutura do projeto

```
tech-chalange/
├── Dockerfile                  # Imagem PHP 8.4 FPM customizada
├── docker-compose.yml          # Orquestração dos containers
├── composer.json               # Dependências PHP (Slim 4)
├── .env                        # Variáveis de ambiente (não versionar!)
├── .env.example                # Modelo do .env
├── README.md
├── docker/
│   ├── nginx/
│   │   └── default.conf        # Configuração do Nginx
│   ├── php/
│   │   └── php.ini             # Configurações customizadas do PHP
│   └── mysql/
│       └── init/               # Scripts SQL executados na criação do banco
├── public/
│   └── index.php               # Entry point da aplicação (Slim 4)
└── src/                        # Classes da aplicação (namespace App\)
```

---

## 🛠️ Comandos úteis

Vários comandos estão embutidos no Makefile. Para visualizar todos os disponíveis, basta rodar:

```bash
make help
```

---

## ⚙️ Variáveis de ambiente

| Variável | Padrão | Descrição |
|---|---|---|
| `DB_HOST` | `mysql` | Host do banco (nome do container) |
| `DB_PORT` | `3306` | Porta do MySQL |
| `DB_DATABASE` | `app_db` | Nome do banco de dados |
| `DB_USERNAME` | `app_user` | Usuário do banco |
| `DB_PASSWORD` | `secret` | Senha do usuário |
| `DB_ROOT_PASSWORD` | `rootsecret` | Senha do root do MySQL |
| `APP_ENV` | `development` | Ambiente da aplicação |
| `APP_DEBUG` | `true` | Exibir erros detalhados |

---

## 📦 Stack

| Tecnologia | Versão |
|---|---|
| PHP | 8.4 FPM |
| Slim Framework | 4.x |
| MySQL | 9 |
| Nginx | latest |
| phpMyAdmin | latest |

---

## ⚠️ Observações

- **Nunca suba o `.env` para o Git.** Certifique-se que ele está no `.gitignore`.
- Em **produção**, altere todas as senhas do `.env` e defina `APP_DEBUG=false`.
- O diretório `vendor/` é gerenciado automaticamente pelo container — não é necessário rodar `composer install` manualmente.
- Scripts `.sql` colocados em `docker/mysql/init/` são executados automaticamente na **primeira criação** do banco.

## 🔐 Autenticação (JWT)

As rotas administrativas são protegidas por JWT. Gere um token manualmente:

```bash
make generate-jwt-secret
```

No Swagger UI (`/docs`), clique em **Authorize** 🔓 no topo da página e cole o token.

> ⚠️ O token expira conforme `JWT_TTL` no `.env` (padrão: 3600 segundos / 1 hora). Gere um novo quando expirar.

## 🛡️ Análise de Vulnerabilidades (OWASP ZAP)

O projeto utiliza o [OWASP ZAP](https://www.zaproxy.org/) para análise de segurança via scan de baseline. Não requer instalação, roda direto via Docker.

Com o ambiente no ar (`make up`), rode:

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

> ℹ️ Esse scan é uma atividade pontual para gerar o relatório de vulnerabilidades. Rode novamente apenas se quiser comparar resultados após correções de segurança.
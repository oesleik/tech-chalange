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
docker compose up -d --build
```

Na primeira execução o Docker irá:
- Baixar as imagens (Nginx, MySQL, phpMyAdmin)
- Buildar a imagem PHP com todas as extensões
- Instalar as dependências via Composer automaticamente

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

### Ver status dos containers
```bash
docker compose ps
```

### Ver logs em tempo real
```bash
docker compose logs -f

# Apenas de um serviço específico
docker compose logs -f php
docker compose logs -f nginx
```

### Entrar no container PHP
```bash
docker compose exec php bash
```

### Rodar testes unitários
```bash
docker compose exec php vendor/bin/phpunit src
```

### Rodar fixer
```bash
docker compose exec php vendor/bin/php-cs-fixer fix
```

### Rodar linter
```bash
docker compose exec php vendor/bin/phpstan analyse src
```

### Instalar uma nova dependência
```bash
docker compose exec php composer require nome/pacote
```

### Derrubar o ambiente
```bash
docker compose down

# Derrubar e apagar volumes (reseta o banco de dados)
docker compose down -v
```

### Rebuildar após mudanças no Dockerfile
```bash
docker compose up -d --build
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

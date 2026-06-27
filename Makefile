help:
	@awk 'BEGIN {FS=":.*##"} /^##@/ {printf "\n%s\n", substr($$0,5); next} /^[a-zA-Z0-9_.-]+:.*##/ {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

##@ Docker

up: ## Inicia / reinicia os containers
	docker compose up -d --build

shell: ## Abrir terminal dentro do container
	docker compose exec --user "$(shell id -u):$(shell id -g)" php bash

status: ## Visualizar o status dos containers
	docker compose ps

down: ## Interrompe os containers
	docker compose down

destroy: ## Interrompe os containers e remove os volumes (inclusive o banco de dados)
	docker compose down -v

##@ JWT auth

jwt-token: ## Gerar chave JWT
	docker compose exec --user "$(shell id -u):$(shell id -g)" php php src/cmd/generate-token.php

jwt-token-email: ## Gerar chave JWT para aprovação da Ordem de Serviço
	docker compose exec php php src/cmd/generate-token-ordem-servico.php

##@ Development

format: ## Rodar o formatter (php-cs-fixer)
	docker compose exec --user "$(shell id -u):$(shell id -g)" php vendor/bin/php-cs-fixer fix

lint: ## Rodar o linter (phpstan)
	docker compose exec --user "$(shell id -u):$(shell id -g)" php vendor/bin/phpstan analyse src

test: ## Rodar testes unitários
	docker compose exec --user "$(shell id -u):$(shell id -g)" php vendor/bin/phpunit src && \
	echo "Open coverage/index.html on your browser to see coverage results"

api-docs: ## Gerar documentação swagger da API
	docker compose exec --user "$(shell id -u):$(shell id -g)" php vendor/bin/openapi src -o public/openapi.json

##@ Migrations

migrate: ## Rodar migrations do banco de dados
	docker compose exec --user "$(shell id -u):$(shell id -g)" php php src/cmd/migrations/migrate.php

new-migration: ## Criar uma nova migration do banco de dados
	docker compose exec --user "$(shell id -u):$(shell id -g)" php php src/cmd/migrations/create.php

##@ Logs

logs: ## Visualizar todos os logs do container
	docker compose logs -f

logs-php: ## Visualizar os logs de PHP
	docker compose logs -f php

logs-nginx: ## Visualizar os logs do nginx
	docker compose logs -f nginx

##@ Tools

require: ## Instalar uma nova dependência no composer
	printf "Package name: "; \
	read pkg; \
	if [ -z "$$pkg" ]; then \
		echo "package is required"; exit 1; \
	fi; \
	docker compose exec --user "$(shell id -u):$(shell id -g)" php composer require "$$pkg"

security-scan: ## Rodar scan de vulnerabilidades (OWASP ZAP)
	@mkdir -p docs/security
	docker run --rm \
		--network host \
		-v "$(PWD)/docs/security:/zap/wrk/:rw" \
		-t zaproxy/zap-stable \
		zap-baseline.py \
		-t http://localhost/ \
		-r zap-baseline-report.html \
		-J zap-baseline-report.json

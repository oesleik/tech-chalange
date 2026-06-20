help:
	@grep -E '^[a-zA-Z_-]+:.*##' Makefile | awk -F'##' '{printf "  %-15s %s\n", $$1, $$2}'

up: ## Inicia / reinicia os containers
	docker compose up -d --build

new-migration: ## Criar uma nova migration do banco de dados
	docker compose exec --user "$(shell id -u):$(shell id -g)" php php src/cmd/migrations/create.php

migrate: ## Rodar migrations do banco de dados
	docker compose exec php php src/cmd/migrations/migrate.php

require: ## Instalar uma nova dependência no composer
	printf "Package name: "; \
	read pkg; \
	if [ -z "$$pkg" ]; then \
		echo "package is required"; exit 1; \
	fi; \
	docker compose exec php composer require "$$pkg"

shell: ## Abrir terminal dentro do container
	docker compose exec php bash

test: ## Rodar testes unitários
	docker compose exec php vendor/bin/phpunit src

format: ## Rodar o formatter (php-cs-fixer)
	docker compose exec --user "$(shell id -u):$(shell id -g)" php vendor/bin/php-cs-fixer fix

lint: ## Rodar o linter (phpstan)
	docker compose exec php vendor/bin/phpstan analyse src

api-docs: ## Gerar documentação swagger da API
	docker compose exec --user "$(shell id -u):$(shell id -g)" php vendor/bin/openapi src -o public/openapi.json

logs: ## Visualizar todos os logs do container
	docker compose logs -f

logs-php: ## Visualizar os logs de PHP
	docker compose logs -f php

logs-nginx: ## Visualizar os logs do nginx
	docker compose logs -f nginx

status: ## Visualizar o status dos containers
	docker compose ps

down: ## Interrompe os containers
	docker compose down

destroy: ## Interrompe os containers e remove os volumes (inclusive o banco de dados)
	docker compose down -v

generate-jwt-secret: ## Gerar chave JWT
	docker compose exec php php src/cmd/generate-token.php

generate-jwt-secret-ordem-servico: ## Gerar chave JWT para Ordem de Serviço
	docker compose exec php php src/cmd/generate-token-ordem-servico.php

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
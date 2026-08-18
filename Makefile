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

# pega o nome do primeiro pod PHP rodando no K8s
K8S_NS = tech-challenge
HELM_RELEASE = tech-challenge
HELM_CHART = charts/tech-challenge
K8S_PHP_POD = $(shell kubectl get pod -n $(K8S_NS) -l app=php -o jsonpath='{.items[0].metadata.name}')

MINISTACK_COMPOSE = docker compose -f docker-compose.ministack.yml -p ministack
AWS_LOCAL_KUBECONFIG = $(PWD)/.kube/ministack.yaml
AWS_LOCAL_HELM = helm --kubeconfig $(AWS_LOCAL_KUBECONFIG)
AWS_LOCAL_KUBECTL = kubectl --kubeconfig $(AWS_LOCAL_KUBECONFIG)
AWS_LOCAL_PHP_POD = $(shell kubectl --kubeconfig $(AWS_LOCAL_KUBECONFIG) get pod -n $(K8S_NS) -l app=php -o jsonpath='{.items[0].metadata.name}' 2>/dev/null)

##@ Kubernetes (Minikube)

k8s-up: ## Subir o ambiente no Minikube via Helm
	@echo "Parando Docker Compose antes de subir o K8s..."
	docker compose down
	@echo "Iniciando Minikube..."
	minikube start --cpus=2 --memory=3072 --driver=docker
	minikube addons enable metrics-server
	@echo "Buildando imagem PHP no Minikube..."
	eval $$(minikube docker-env) && docker build -t tech-challenge-php:latest .
	@echo "Instalando chart Helm (values-minikube)..."
	helm upgrade --install $(HELM_RELEASE) $(HELM_CHART) \
		-n $(K8S_NS) --create-namespace \
		-f $(HELM_CHART)/values-minikube.yaml
	@echo "Pronto. Use make k8s-url (port-forward). phpMyAdmin: make k8s-phpmyadmin-url."

k8s-down: ## Parar o Minikube preservando pods e dados (retoma com k8s-up)
	minikube stop

k8s-destroy: ## Remover o release Helm e parar Minikube, dados do PVC podem persistir
	-helm uninstall $(HELM_RELEASE) -n $(K8S_NS)
	kubectl delete namespace $(K8S_NS) --ignore-not-found
	minikube stop

k8s-reset: ## Destruir TUDO incluindo dados do banco, usa minikube delete
	minikube delete

k8s-status: ## Ver status de todos os recursos no Kubernetes
	kubectl get all -n $(K8S_NS)

k8s-url: ## Port-forward do Nginx (localhost; NodePort não chega ao host pelo WSL)
	@echo "Aplicacao:  http://localhost:30080"
	@echo "Swagger:    http://localhost:30080/docs/index.html"
	@echo "phpMyAdmin: make k8s-phpmyadmin-url  -> http://localhost:30081"
	@echo "Encaminhando localhost:30080 -> svc/nginx. Ctrl+C para encerrar."
	kubectl port-forward -n $(K8S_NS) svc/nginx 30080:80

k8s-phpmyadmin-url: ## Port-forward do phpMyAdmin (localhost:30081)
	@echo "Encaminhando localhost:30081 -> svc/phpmyadmin. Ctrl+C para encerrar."
	kubectl port-forward -n $(K8S_NS) svc/phpmyadmin 30081:80

k8s-shell: ## Abrir terminal dentro do pod PHP no Kubernetes
	kubectl exec -n $(K8S_NS) -it $(K8S_PHP_POD) -- bash

k8s-jwt-token: ## Gerar chave JWT no Kubernetes
	kubectl exec -n $(K8S_NS) $(K8S_PHP_POD) -- php src/cmd/generate-token.php

k8s-jwt-token-email: ## Gerar chave JWT de email no Kubernetes
	kubectl exec -n $(K8S_NS) -it $(K8S_PHP_POD) -- \
		php src/cmd/generate-token-ordem-servico.php

k8s-migrate: ## Rodar migrations no Kubernetes
	kubectl exec -n $(K8S_NS) $(K8S_PHP_POD) -- php src/cmd/migrations/migrate.php

k8s-new-migration: ## Criar nova migration no Kubernetes
	kubectl exec -n $(K8S_NS) $(K8S_PHP_POD) -- php src/cmd/migrations/create.php

k8s-format: ## Rodar o formatter php-cs-fixer no Kubernetes
	kubectl exec -n $(K8S_NS) $(K8S_PHP_POD) -- vendor/bin/php-cs-fixer fix

k8s-lint: ## Rodar o linter phpstan no Kubernetes
	kubectl exec -n $(K8S_NS) $(K8S_PHP_POD) -- \
		php -d memory_limit=512M vendor/bin/phpstan analyse src

k8s-test: ## Rodar testes unitarios no Kubernetes
	kubectl exec -n $(K8S_NS) $(K8S_PHP_POD) -- vendor/bin/phpunit src

k8s-api-docs: ## Gerar Swagger, rebuildar imagem e reiniciar nginx
	@echo "Rebuildando imagem com novo openapi.json..."
	eval $$(minikube docker-env) && docker build -t tech-challenge-php:latest .
	@echo "Reiniciando nginx para copiar arquivos atualizados..."
	kubectl rollout restart deployment/nginx -n $(K8S_NS)
	kubectl rollout status deployment/nginx -n $(K8S_NS)
	@echo "Swagger: http://localhost:30080/docs/index.html (make k8s-url)"

k8s-logs-php: ## Ver logs do PHP no Kubernetes
	kubectl logs -n $(K8S_NS) -l app=php -f

k8s-logs-nginx: ## Ver logs do Nginx no Kubernetes
	kubectl logs -n $(K8S_NS) -l app=nginx -f

k8s-security-scan: ## Rodar scan OWASP ZAP no Kubernetes (localhost; sobe port-forward se preciso)
	@mkdir -p docs/security
	@kubectl port-forward -n $(K8S_NS) svc/nginx 30080:80 >/dev/null 2>&1 & \
	pf=$$!; \
	trap 'kill $$pf 2>/dev/null' EXIT; \
	i=0; until curl -sf -o /dev/null http://127.0.0.1:30080/ || [ $$i -ge 15 ]; do i=$$((i+1)); sleep 1; done; \
	docker run --rm \
		--network host \
		-v "$(PWD)/docs/security:/zap/wrk/:rw" \
		-t zaproxy/zap-stable \
		zap-baseline.py \
		-t http://127.0.0.1:30080/ \
		-r zap-baseline-report.html \
		-J zap-baseline-report.json || true

open-security-report: ## Abrir relatorio OWASP ZAP no navegador
	xdg-open docs/security/zap-baseline-report.html

##@ AWS local (MiniStack)

aws-local-up: ## MiniStack + Terraform EKS (k3s) + Helm (values-aws-local)
	@echo "Parando Docker Compose da app e Minikube (se existirem)..."
	-docker compose down
	-minikube stop
	@echo "Subindo MiniStack..."
	$(MINISTACK_COMPOSE) up -d --wait
	@echo "Aplicando Terraform (env/ministack.tfvars)..."
	terraform -chdir=infra init -input=false
	terraform -chdir=infra apply -input=false -auto-approve -var-file=env/ministack.tfvars
	@$(MAKE) aws-local-kubeconfig
	@echo "Buildando imagem PHP no Docker do host..."
	docker build -t tech-challenge-php:latest .
	bash scripts/ministack-load-image.sh tech-challenge-php:latest
	@echo "Instalando chart Helm (values-aws-local)..."
	$(AWS_LOCAL_HELM) upgrade --install $(HELM_RELEASE) $(HELM_CHART) \
		-n $(K8S_NS) --create-namespace \
		-f $(HELM_CHART)/values-aws-local.yaml
	@echo "Pronto. Use make aws-local-url (port-forward)."

aws-local-kubeconfig: ## Adapter: extrai kubeconfig do k3s (NAO e aws eks update-kubeconfig)
	bash scripts/ministack-kubeconfig.sh

aws-local-down: ## Terraform destroy + para MiniStack (k3s some com o cluster)
	-terraform -chdir=infra destroy -input=false -auto-approve -var-file=env/ministack.tfvars
	$(MINISTACK_COMPOSE) down
	rm -f $(AWS_LOCAL_KUBECONFIG)

aws-local-status: ## Status do cluster k3s do MiniStack
	$(AWS_LOCAL_KUBECTL) get all -n $(K8S_NS)

aws-local-url: ## Port-forward do Nginx (localhost; mesmo modelo do make k8s-url)
	@echo "Encaminhando localhost:30080 -> svc/nginx. Ctrl+C para encerrar."
	$(AWS_LOCAL_KUBECTL) port-forward -n $(K8S_NS) svc/nginx 30080:80

aws-local-migrate: ## Rodar migrations no cluster MiniStack
	$(AWS_LOCAL_KUBECTL) exec -n $(K8S_NS) $(AWS_LOCAL_PHP_POD) -- php src/cmd/migrations/migrate.php

aws-local-logs-php: ## Logs do PHP no MiniStack
	$(AWS_LOCAL_KUBECTL) logs -n $(K8S_NS) -l app=php -f

aws-local-logs-nginx: ## Logs do Nginx no MiniStack
	$(AWS_LOCAL_KUBECTL) logs -n $(K8S_NS) -l app=nginx -f

# ADR-004 - Dois fluxos locais: Minikube e MiniStack (AWS local)

**Status:** Aceita  
**Data:** 15/08/2026  
**Participantes:** Oeslei  

## Contexto

O desenvolvimento local da aplicação é Docker Compose. Falta uma implementação em Kubernetes e validação do caminho até a AWS (EKS) sem custo de nuvem.

Três camadas se confundem com facilidade:

1. Cluster Kubernetes (control plane)
2. Plataforma AWS (VPC, IAM, EKS, ECR)
3. Workload da aplicação (Deployments, Services, HPA)

Nenhum emulador cobre as três. Um cluster local cobre (1) e (3) e não emula a API da AWS. Um emulador de AWS cobre (2) e, no `CreateCluster`, sobe um Kubernetes que não é EKS.

## Decisão

- **Dois fluxos locais, introduzidos juntos.** Minikube (`make k8s-up`) é o dia a dia da aplicação no Kubernetes. MiniStack (`make aws-local-up`) é só o teste local de Terraform + Helm, sem conta AWS.
- **Um chart Helm** em `charts/tech-challenge/` com três values: `values-minikube.yaml`, `values-aws-local.yaml`, `values-aws.yaml`.
- **MySQL dentro do cluster** nos três ambientes (sem RDS por enquanto)
- **Terraform** em `infra/`: VPC, IAM, EKS, node group. Código único, MiniStack vs AWS só muda o tfvars (`env/ministack.tfvars` / `env/aws.tfvars`).
- **Imagem fora do Terraform.** Minikube: `eval $(minikube docker-env) && docker build`. MiniStack: `docker build` no host + import no containerd do k3s.
- **NodePort** no Minikube e no MiniStack. **LoadBalancer** só em `values-aws.yaml`.
- **Secret Kubernetes** nos três.
- **phpMyAdmin só no Minikube.**

## Motivação

O MiniStack permite `terraform apply` contra um endpoint AWS sem custos. O cluster resultante é k3s, não EKS: isso é suficiente para validar o IaC e o chart Helm.

O Minikube é o cluster local leve: não usa Docker socket privilegiado nem o hop extra de kubeconfig do k3s. Usar só o MiniStack no dia a dia tornaria o desenvolvimento pesado demais para o que a aplicação precisa.

Helm com três values evita editar YAML à mão ao mudar de alvo (local, AWS local, AWS).

## Consequências

### Positivas

- Mesmo chart nos três alvos; CI só muda values e kubecontext.
- Teste de `terraform apply`/`destroy` sem custo AWS.
- Fronteira clara: Terraform cria plataforma; Helm instala a aplicação.
- Adapter de kubeconfig MiniStack é explícito (`scripts/ministack-kubeconfig.sh`), não finge ser `aws eks update-kubeconfig`.

### Negativas / o que o emulador ignora no ambiente local

- IAM: roles existem, mas policies **não são avaliadas**.
- VPC/SG são apenas metadados, não há isolamento de rede real.
- Addons EKS (`vpc-cni`, CSI): o MiniStack marca `ACTIVE` sem instalar o software da AWS. k3s usa o próprio CNI/storage.
- Node group Terraform não são nós EC2; o worker é o próprio k3s.
- Kubeconfig MiniStack é `docker exec` no container k3s. Na AWS será `aws eks update-kubeconfig`.
- NodePort no cluster não implica URL no host. No MiniStack o k3s não publica a porta; no Minikube com driver Docker o `minikube ip` só existe na rede do nó (quebra no WSL2). Acesso local unificado: `kubectl port-forward` (`make k8s-url` / `make aws-local-url`) em `localhost:30080`. phpMyAdmin no Minikube: `make k8s-phpmyadmin-url`.
- k3s sobe privilegiado com Docker socket. Aceitável no local.
- MiniStack é efêmero: restart não garante o container k3s.

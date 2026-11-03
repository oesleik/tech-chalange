# RFC-001 - Plataforma de nuvem AWS

- **Status:** Aceito e adotado na infraestrutura como código
- **Decisão registrada em:** ADR-011
**Escopo:** Plataforma AWS para API, execução de containers, autenticação, banco e observabilidade.

## Contexto

A solução precisa publicar a API PHP, expor uma rota de autenticação serverless e manter o banco fora do acesso público. A plataforma também deve integrar CI/CD, rede privada e monitoramento sem operar manualmente cada VM.

## Proposta

Adotar AWS com:

- API Gateway HTTP API como entrada pública.
- EKS para Nginx e PHP, com NLB interno e VPC Link para encaminhar a rota `$default`.
- Lambda Node.js para `POST /autenticacao`.
- Amazon RDS MySQL em sub-redes privadas.
- ECR para imagens e Parameter Store para contratos de infraestrutura entre repositórios.
- CloudWatch e New Relic para logs, métricas, dashboards e alertas.

## Alternativas consideradas

1. Hospedar serviços em VMs e operar manualmente proxy, runtime e atualizações.
2. Usar outro provedor de nuvem, com serviços equivalentes de Kubernetes, função serverless, banco gerenciado e gateway.
3. Executar aplicação, autenticação e banco no mesmo cluster Kubernetes.

AWS foi mantida por integrar os serviços usados pelo projeto e por atender aos requisitos didáticos de infraestrutura como código e serviços gerenciados. A decisão não representa uma comparação comercial exaustiva entre provedores.

## Consequências e ressalvas

- O desenho atual combina serviços gerenciados e workloads em EKS, aumentando o número de componentes e contratos entre repositórios.
- O RDS atualmente é Single-AZ e não tem retenção de backup automatizado nem snapshot final automático. A arquitetura não oferece, portanto, alta disponibilidade do banco ou recuperação point-in-time configurada.
- O node group tem limites próprios no Terraform. HPA de pods não escala nós EKS.
- Custos, capacidade e metas de recuperação devem ser revistos antes de uso produtivo.

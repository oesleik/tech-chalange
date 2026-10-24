# Guia operacional: deploy, validação e destroy na AWS

Este documento descreve o procedimento recorrente para publicar e validar o Tech Challenge na AWS real. Ele complementa o [Runbook AWS](./runbook_aws.md), que contém o bootstrap inicial da infraestrutura, do Terraform remoto e da integração OIDC com o GitHub Actions.

## 1. Arquitetura implantada

O workflow `AWS deploy` provisiona ou atualiza os recursos gerenciados pelo Terraform e publica a aplicação:

- **Amazon EKS**: cluster Kubernetes e node group.
- **Amazon ECR**: armazenamento da imagem Docker da aplicação PHP.
- **Amazon RDS for MySQL**: banco `app_db` em sub-redes privadas, sem acesso público.
- **Helm**: Deployments PHP-FPM e Nginx, Services, ConfigMaps, Secrets e HPA.
- **API Gateway HTTP API + VPC Link + NLB interno**: entrada pública da aplicação. O NodePort `30080` dos nodes não é o endpoint público.
- **S3 + DynamoDB**: state remoto e lock do Terraform; são recursos de bootstrap e devem permanecer após o `AWS destroy`.

A arquitetura real da AWS usa RDS. Os ambientes locais (Minikube/MiniStack) podem continuar utilizando a configuração de banco própria deles.

## 2. Pré-requisitos antes do primeiro deploy

Antes de executar qualquer workflow, confira o [Runbook AWS](./runbook_aws.md) e confirme que o bootstrap foi feito uma única vez:

- Bucket S3 de state com versionamento, criptografia e bloqueio de acesso público.
- Tabela DynamoDB `tech-challenge-tfstate-lock` com a chave `LockID`.
- Provider OIDC do GitHub Actions e roles IAM `tech-challenge-gha-plan` / `tech-challenge-gha-apply` com trust policies corretas.
- Variables e secrets obrigatórios cadastrados nas configurações de Actions do repositório.

### GitHub Actions variables

| Variable | Finalidade |
|---|---|
| `TF_STATE_BUCKET` | Bucket S3 do state Terraform |
| `TF_STATE_LOCK_TABLE` | Tabela DynamoDB de lock |
| `AWS_ROLE_ARN_PLAN` | Role usada pelo workflow IaC/plan, quando aplicável |
| `AWS_ROLE_ARN_APPLY` | Role usada pelos workflows `AWS deploy` e `AWS destroy` |

### GitHub Actions secrets

| Secret | Finalidade |
|---|---|
| `DB_PASSWORD` | Senha do usuário de aplicação `app_user` |
| `DB_ROOT_PASSWORD` | Senha administrativa do RDS (usuário configurado como `dbadmin`) e do Job temporário de configuração |
| `JWT_SECRET` | Assinatura dos JWTs administrativos |
| `LAMBDA_DB_PASSWORD` | Senha do usuário somente leitura `autenticacao`, usado pela Lambda para consultar `clientes` (Terraform e Job de configuração) |
| `OS_EMAIL_ACTION_TOKEN_SECRET` | Assinatura dos tokens dos links de e-mail da ordem de serviço |
| `MAIL_USERNAME` | Usuário SMTP |
| `MAIL_PASSWORD` | Senha SMTP |

`DB_ROOT_PASSWORD` deve ter pelo menos 32 caracteres para passar a precondição do Terraform. Use uma senha forte e armazene-a apenas nos GitHub Actions Secrets; não a coloque em arquivos versionados, comandos registrados no histórico ou documentação.

**Não recrie o bucket, a tabela nem as roles a cada deploy.** São recursos de bootstrap necessários para que os workflows consigam acessar o state e assumir a role de deploy.

## 3. Fazer o deploy manual

### 3.1 Preparar o código

1. Use a branch `master`.
2. Confirme que os workflows e arquivos Terraform/chart estão commitados e enviados ao GitHub.
3. Não execute `AWS deploy` e `AWS destroy` ao mesmo tempo. Os workflows usam o mesmo grupo de concorrência e o mesmo state remoto.

> Durante testes em uma branch de feature, tanto a condição de branch do workflow quanto a trust policy da role OIDC precisam autorizar explicitamente essa branch. O procedimento operacional padrão deve ser realizado a partir de `master`. Não mantenha uma exceção temporária de branch mais permissiva do que o necessário.

### 3.2 Iniciar o workflow

No GitHub:

1. Abra o repositório `oesleik/tech-chalange`.
2. Acesse **Actions → AWS deploy → Run workflow**.
3. Selecione a branch `master` (procedimento padrão).
4. Selecione o tipo de instância dos nodes:
   - `t3.medium`: padrão e geralmente mais econômico para validações funcionais.
   - `t3.large`: opção maior para testes que precisam de mais recursos, inclusive observação do HPA; terá custo maior.
5. Clique em **Run workflow** e abra a execução iniciada.

O workflow executa, em sequência, autenticação AWS via OIDC, inicialização e plan/apply do Terraform, obtenção do endpoint RDS, build/push da imagem PHP para ECR, configuração do kubeconfig, instalação do metrics-server, preparação temporária das credenciais administrativas do RDS, instalação/atualização do chart Helm, configuração do usuário da aplicação, migrations e exibição da URL do API Gateway.

A URL pública dinâmica da aplicação aparece na etapa **Show API Gateway URL** e no **Job Summary**. Não fixe no código a URL, o ID do API Gateway, o IP público de uma instância ou o endpoint do RDS: esses valores podem mudar depois de um destroy/redeploy.

## 4. Validar o deploy

Só considere o deploy validado quando o workflow terminar com sucesso e os testes abaixo forem aprovados.

### 4.1 Testar a entrada pública

Copie a URL do API Gateway que apareceu no resumo do workflow. Em uma máquina com acesso à internet, execute:

```bash
export API_URL="COLE_AQUI_A_URL_DO_API_GATEWAY"
curl -i --connect-timeout 10 --max-time 20 "$API_URL/"
```

Resultado esperado: resposta HTTP `200` e retorno da aplicação. Use o endpoint do API Gateway; não teste o endereço IP público de um worker ou `http://IP_DO_NODE:30080`. O NodePort é protegido para aceitar tráfego do NLB interno, não acesso direto pela internet.

### 4.2 Conferir EKS e workloads

Autentique a AWS CLI, caso a sessão tenha expirado, e atualize o kubeconfig:

```bash
aws login --region us-east-1
aws eks update-kubeconfig --name tech-challenge --region us-east-1
```

Liste os nodes e os recursos da aplicação:

```bash
kubectl get nodes -o wide
kubectl get pods,jobs,deployments,svc,hpa -n tech-challenge -o wide
```

Confirme que o node está `Ready`, os Deployments PHP e Nginx têm seus pods prontos (`1/1` para uma réplica), e o Job `rds-user-setup` terminou como `Completed`.

Os comandos abaixo permitem validar diretamente os principais recursos:

```bash
kubectl wait --for=condition=complete job/rds-user-setup \
  --namespace tech-challenge --timeout=300s
kubectl rollout status deployment/php \
  --namespace tech-challenge --timeout=180s
kubectl rollout status deployment/nginx \
  --namespace tech-challenge --timeout=180s
```

### 4.3 Conferir o RDS

```bash
aws rds describe-db-instances \
  --region us-east-1 \
  --query 'DBInstances[].{Instancia:DBInstanceIdentifier,Estado:DBInstanceStatus,Publico:PubliclyAccessible,Endpoint:Endpoint.Address,Porta:Endpoint.Port}' \
  --output table
```

O esperado para `tech-challenge-mysql` é `available` e `Publico=False`. O banco deve permanecer em sub-redes privadas, acessível pela aplicação através das regras de security group, não abra a porta 3306 para a internet.

### 4.4 Validar a conexão PHP com o banco e listar as tabelas

Este teste usa as variáveis já injetadas no pod. Ele não imprime senha nem token:

```bash
PHP_POD="$(kubectl get pods -n tech-challenge -l app=php \
  -o jsonpath='{.items[0].metadata.name}')"

kubectl exec -n tech-challenge "$PHP_POD" -- php -r '
try {
    $pdo = new PDO(
        "mysql:host=".getenv("DB_HOST").
        ";port=".(getenv("DB_PORT") ?: "3306").
        ";dbname=".getenv("DB_DATABASE").
        ";charset=utf8mb4",
        getenv("DB_USERNAME"),
        getenv("DB_PASSWORD"),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Conexao com o banco: OK\n";
    echo "Banco: ".$pdo->query("SELECT DATABASE()")->fetchColumn()."\n";
    echo "Tabelas:\n";
    foreach ($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $table) {
        echo "- ".$table."\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Falha na conexao: ".$e->getMessage()."\n");
    exit(1);
}
'
```

Confirme que as tabelas da aplicação foram criadas pelas migrations. O workflow já executa as migrations; execute-as manualmente apenas quando estiver investigando ou recuperando uma falha:

```bash
kubectl exec --namespace tech-challenge deploy/php -- \
  php src/cmd/migrations/migrate.php
```

### Checklist pós-deploy

- [ ] Workflow `AWS deploy` terminou com sucesso.
- [ ] URL do API Gateway respondeu HTTP `200`.
- [ ] Node do EKS está `Ready`.
- [ ] PHP e Nginx estão prontos.
- [ ] Job `rds-user-setup` está `Completed`.
- [ ] RDS está `available` e não é público.
- [ ] Conexão PHP/PDO com `app_db` está OK.
- [ ] Tabelas da aplicação existem após as migrations.

## 5. Diagnóstico de falha

Se o Helm falhar com `context deadline exceeded`, não rode vários deploys às cegas. Consulte primeiro o estado real do cluster:

```bash
helm status tech-challenge --namespace tech-challenge --show-resources
kubectl get pods,jobs,deployments,svc -n tech-challenge -o wide
kubectl get events -n tech-challenge --sort-by=.metadata.creationTimestamp | tail -40
kubectl logs deployment/nginx -n tech-challenge --all-containers=true --tail=100
kubectl logs deployment/php -n tech-challenge --all-containers=true --tail=100
kubectl logs job/rds-user-setup -n tech-challenge --all-containers=true --tail=100
```

Se o Job ainda não existir, o último comando pode responder `NotFound`; isso é uma pista de que o hook de configuração do banco não foi criado/executado. Verifique também a saída da etapa `Helm upgrade` no GitHub Actions. O workflow deve permitir que o Job `rds-user-setup` seja criado antes de aguardar a prontidão final do Nginx, e deve verificar o Nginx depois das migrations.

Erros comuns:

- **API responde, mas o acesso ao IP/NodePort do worker expira:** use a URL do API Gateway. O NodePort não foi projetado como endpoint público.
- **Job RDS falha:** confira o estado do RDS, a existência do Secret temporário `rds-admin-secrets`, os eventos do namespace e os logs do Job. Não copie senhas para os logs/documentos.
- **PHP pronto, Nginx não pronto:** veja os eventos e logs do Nginx e confirme que o Job de setup e as migrations concluíram.
- **AWS CLI informa token expirado:** autentique novamente com `aws login --region us-east-1` e refaça a consulta.
- **Workflow diz que a branch não é permitida:** selecione `master` para o procedimento padrão; confira também a trust policy OIDC da role `tech-challenge-gha-apply`.

## 6. Parar a infraestrutura e evitar custos

O `AWS destroy` remove a infraestrutura gerenciada pelo Terraform, incluindo EKS, node group, rede associada, API Gateway/VPC Link/NLB, RDS e outros recursos incluídos no state. A exclusão do RDS é destrutiva: a configuração atual usa `skip_final_snapshot = true`, portanto não cria snapshot final automaticamente e os dados serão perdidos.

Para destruir pelo GitHub:

1. Abra **Actions → AWS destroy → Run workflow**.
2. Selecione obrigatoriamente a branch `master`.
3. No campo de confirmação, digite exatamente `destroy`.
4. Inicie o workflow e aguarde a conclusão. Se falhar, leia o log e não presuma que todos os recursos foram removidos.

O workflow não apaga o bucket S3 do state, a tabela DynamoDB de lock nem o bootstrap OIDC/roles. Esses recursos são necessários para futuras execuções e devem ser mantidos, salvo uma desativação deliberada e separada do projeto.

Depois que o destroy concluir, confira o RDS:

```bash
aws rds describe-db-instances \
  --region us-east-1 \
  --query 'DBInstances[].DBInstanceIdentifier' --output table
```

E confirme que o EKS não existe mais:

```bash
aws eks describe-cluster --name tech-challenge --region us-east-1
```

Depois do destroy, a lista do RDS deve estar vazia e o EKS deve retornar `ResourceNotFoundException`. Se continuarem existindo recursos, investigue antes de considerar a limpeza completa. Confira também o console de faturamento e os recursos de EC2, EBS, Elastic Load Balancing, RDS e CloudWatch na região `us-east-1`.

> Faça deploy apenas quando necessário para desenvolvimento ou demonstração. O cluster EKS, o node group, o RDS e o load balancer podem gerar custos enquanto permanecem ativos.

## 7. Resultado de validação registrado

Esse registro documenta o resultado do teste, não garante o estado atual da AWS. URLs, IDs, IPs e endpoints devem ser consultados novamente após cada deploy.

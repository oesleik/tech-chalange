# Guia operacional: deploy, validação e destroy na AWS

Este documento descreve o procedimento recorrente para publicar e validar o Tech Challenge na AWS real. Ele complementa o [Runbook AWS](./runbook_aws.md), que contém o bootstrap inicial da infraestrutura, do Terraform remoto e da integração OIDC com o GitHub Actions.

## 1. Arquitetura implantada

![Arquitetura AWS](../arch/AWS-Architecture.png)

A solução está dividida em quatro repositórios, cada um com seus workflows de **Deploy** e **Destroy** ([ADR-013](../adr/013-divisao-repositorios.md)):

| Repositório | O que cria | State |
|---|---|---|
| [tech-challenge-infra-k8s](https://github.com/oesleik/tech-challenge-infra-k8s) | VPC, EKS, ECR, NLB interno, API Gateway + VPC Link, metrics-server, New Relic | `tech-challenge/infra-k8s.tfstate` |
| [tech-challenge-infra-db](https://github.com/oesleik/tech-challenge-infra-db) | RDS MySQL `tech-challenge-mysql` em sub-redes privadas | `tech-challenge/infra-db.tfstate` |
| tech-chalange (este) | Imagem no ECR, release Helm (PHP-FPM, Nginx, HPA, job `rds-user-setup`), migrations | — (Helm) |
| [tech-challenge-lambda](https://github.com/oesleik/tech-challenge-lambda) | Lambda `autenticacao` e rota `POST /autenticacao` | `tech-challenge/lambda.tfstate` |

Os repositórios trocam valores pelo **SSM Parameter Store** em `/tech-challenge/*`: quem cria publica (`network/*`, `eks/*`, `db/*`...), quem depende lê. O bucket S3 e a tabela DynamoDB do state são bootstrap e permanecem após os destroys.

O NodePort `30080` dos nodes não é o endpoint público: a entrada é o API Gateway.

## 2. Pré-requisitos antes do primeiro deploy

Antes de executar qualquer workflow, confira o [Runbook AWS](./runbook_aws.md) e confirme que o bootstrap foi feito uma única vez:

- Bucket S3 de state com versionamento, criptografia e bloqueio de acesso público.
- Tabela DynamoDB `tech-challenge-tfstate-lock` com a chave `LockID`.
- Provider OIDC do GitHub Actions e role `tech-challenge-gha-apply`, com trust policy liberando os quatro repositórios.
- Variables e secrets cadastrados em cada repositório. A lista de cada um está no README dele; o resumo está no [Runbook AWS](./runbook_aws.md#variables-e-secrets-por-repositório).

`DB_ROOT_PASSWORD` deve ter pelo menos 32 caracteres para passar a precondição do Terraform (infra-db). Ele, `JWT_SECRET` e `LAMBDA_DB_PASSWORD` precisam ter **o mesmo valor** nos repositórios que os usam.

**Não recrie o bucket, a tabela nem a role a cada deploy.** São recursos de bootstrap necessários para que os workflows consigam acessar o state e assumir a role de deploy.

## 3. Fazer o deploy manual

### 3.1 Ordem

```
Deploy:  infra-k8s → infra-db → app → lambda
Destroy: lambda → app → infra-db → infra-k8s
```

Cada workflow confere o contrato SSM antes de começar. Fora de ordem, ele aborta com uma mensagem dizendo qual repositório rodar antes (por exemplo, `/tech-challenge/db/address não existe. Rode o Deploy do tech-challenge-infra-db antes.`).

Use sempre a branch `master`. A condição de branch dos workflows e a trust policy da role exigem isso.

### 3.2 Executar

Em cada repositório, na ordem: **Actions → Deploy → Run workflow** → branch `master`.

| Repositório | Input | Duração aproximada | Saída |
|---|---|---|---|
| infra-k8s | tipo de instância dos nodes (`t3.small` padrão) | 15-20 min | URL do API Gateway |
| infra-db | classe do RDS (`db.t3.micro` padrão) | 5-10 min | endpoint do RDS |
| tech-chalange | — | 5-10 min | URL do API Gateway e do Swagger |
| lambda | — | 2-3 min | URL de `POST /autenticacao` |

O deploy da aplicação faz build/push da imagem, cria o Secret temporário `rds-admin-secrets`, roda o `helm upgrade`, espera o job `rds-user-setup` (que cria os usuários `app_user` e `autenticacao` no RDS), roda as migrations e publica `/tech-challenge/app/image_tag`. A Lambda só sobe depois disso, porque depende do usuário `autenticacao`.

A URL pública aparece no **Job Summary**. Não fixe no código a URL, o ID do API Gateway ou o endpoint do RDS: esses valores mudam depois de um destroy/redeploy.

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

- [ ] Workflows de Deploy dos quatro repositórios terminaram com sucesso.
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

Se o Job ainda não existir, o último comando pode responder `NotFound`; isso é uma pista de que o hook de configuração do banco não foi criado/executado. Verifique também a saída da etapa `Helm upgrade` no workflow de Deploy do tech-chalange.

Erros comuns:

- **API responde, mas o acesso ao IP/NodePort do worker expira:** use a URL do API Gateway. O NodePort não foi projetado como endpoint público.
- **Job RDS falha:** confira o estado do RDS, a existência do Secret temporário `rds-admin-secrets`, os eventos do namespace e os logs do Job. Não copie senhas para os logs/documentos.
- **PHP pronto, Nginx não pronto:** veja os eventos e logs do Nginx e confirme que o Job de setup e as migrations concluíram.
- **AWS CLI informa token expirado:** autentique novamente com `aws login --region us-east-1` e refaça a consulta.
- **Workflow diz que a branch não é permitida:** selecione `master` para o procedimento padrão; confira também a trust policy OIDC da role `tech-challenge-gha-apply`.
- **Workflow aborta dizendo que um parâmetro `/tech-challenge/...` não existe:** o repositório anterior da cadeia não foi aplicado (ou foi destruído). Rode o Deploy indicado na mensagem.
- **Destroy aborta citando parâmetros de outro escopo:** algum dependente ainda está de pé. Rode o Destroy dele antes.

## 6. Parar a infraestrutura e evitar custos

O destroy é feito repositório a repositório, na ordem **inversa** do deploy. Em cada um: **Actions → Destroy → Run workflow** → branch `master` → digite exatamente `destroy` no campo de confirmação.

1. **tech-challenge-lambda**: remove a função, a rota e a regra no SG do RDS.
2. **tech-chalange**: `helm uninstall` e remoção do namespace `tech-challenge`.
3. **tech-challenge-infra-db**: remove o RDS. A configuração usa `skip_final_snapshot = true`: **os dados são perdidos**.
4. **tech-challenge-infra-k8s**: remove EKS, node group, VPC, API Gateway, NLB, ECR (com as imagens) e os recursos do New Relic.

Cada destroy se recusa a rodar enquanto existirem parâmetros SSM dos dependentes (por exemplo, o infra-db exige `/tech-challenge/app` e `/tech-challenge/lambda` vazios). Se um workflow falhar, leia o log e não presuma que os recursos foram removidos.

Nenhum workflow apaga o bucket S3 do state, a tabela DynamoDB de lock nem o bootstrap OIDC/role.

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

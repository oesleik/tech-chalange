# Lambda `autenticacao`

Autentica pelo CPF: valida o CPF, confirma que o cliente está cadastrado na tabela `clientes` do RDS e emite o JWT usado nas rotas protegidas do PHP (`Authorization: Bearer <token>`). Node.js 24 (LTS), com `mysql2` como única dependência ([ADR-008](../../docs/adr/008-lambda-acesso-rds.md)).

Acessada **só via API Gateway** (`POST /autenticacao`). Provisionada em [`infra/lambda.tf`](../../infra/lambda.tf); a URL sai em `terraform output autenticacao_url`. Não existe no MiniStack (mesmo toggle do API Gateway).

```bash
curl -X POST "$(terraform -chdir=infra output -raw autenticacao_url)" \
  -H 'content-type: application/json' -d '{"cpf": "529.982.247-25"}'
```

| Caso | Status | Body |
| --- | --- | --- |
| CPF válido e cliente cadastrado | 200 | `{"access_token": "<jwt>", "token_type": "Bearer", "expires_in": 3600}` |
| CPF inválido | 401 | `{"error": "Unauthorized", "message": "CPF inválido."}` |
| CPF válido sem cliente cadastrado | 404 | `{"error": "Not Found", "message": "Cliente não encontrado."}` |
| Sem `cpf` (string) ou JSON inválido | 400 | `{"error": "Bad Request", "message": "..."}` |
| `JWT_SECRET` ou `DB_*` ausentes | 500 | `{"error": "Internal Server Error", ...}` |
| Falha ao consultar o banco | 503 | `{"error": "Service Unavailable", ...}` |

## Token

HS256 assinado com `JWT_SECRET`, compatível com `src/Core/Auth/AbstractJwtService.php`. Claims: `iss` (`JWT_ISSUER`), `iat`, `exp` (`iat + JWT_TTL`), `sub` (CPF só com dígitos) e `role` (`cliente`).

`JWT_SECRET`, `JWT_ISSUER` e `JWT_TTL` precisam ser os mesmos do PHP. No Terraform vêm de `jwt_secret` (obrigatória na AWS; os workflows passam `secrets.JWT_SECRET` como `TF_VAR_jwt_secret`), `jwt_issuer` e `jwt_ttl`.

## Banco

Consulta `SELECT 1 FROM clientes WHERE cpf_cnpj = ?` com o CPF só com dígitos (mesmo formato gravado pelo `Cpf.php`). A função roda nas sub-redes privadas, com SG próprio liberado no SG do RDS.

| Variável | Origem no Terraform |
| --- | --- |
| `DB_HOST`, `DB_PORT` | `aws_db_instance.mysql` |
| `DB_DATABASE` | `rds_db_name` |
| `DB_USERNAME` | `lambda_db_username` (padrão `autenticacao`) |
| `DB_PASSWORD` | `lambda_db_password` (`TF_VAR_lambda_db_password`, secret `LAMBDA_DB_PASSWORD`) |
| `DB_SSL` | não definida: TLS com o bundle do RDS. `false` desliga, só para MySQL local |

O usuário `autenticacao` (só `SELECT`, `REQUIRE SSL`) é criado pelo Job `rds-user-setup` do Helm com o mesmo `LAMBDA_DB_PASSWORD`.

## CPF

Aceita com ou sem `.` e `-`. Diferente do `Cpf.php`, rejeita caracteres não numéricos e dígitos repetidos (`111.111.111-11`).

## Testes

```bash
cd lambda/autenticacao && npm ci && npm test
```

O handler recebe o acesso ao banco por injeção (`criarHandler`), então os testes não precisam de MySQL.

## Empacotamento

O `archive_file` do `lambda.tf` zipa a pasta inteira (sem `test/`) incluindo o `node_modules`. Rode `npm ci --omit=dev` antes do `terraform plan`; o workflow de deploy já faz isso.

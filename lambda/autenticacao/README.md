# Lambda `autenticacao`

Autentica pelo CPF: valida o CPF e emite o JWT usado nas rotas protegidas do PHP (`Authorization: Bearer <token>`). Node.js 24 (LTS), sem dependências.

Acessada **só via API Gateway** (`POST /autenticacao`). Provisionada em [`infra/lambda.tf`](../../infra/lambda.tf); a URL sai em `terraform output autenticacao_url`. Não existe no MiniStack (mesmo toggle do API Gateway).

```bash
curl -X POST "$(terraform -chdir=infra output -raw autenticacao_url)" \
  -H 'content-type: application/json' -d '{"cpf": "529.982.247-25"}'
```

| Caso | Status | Body |
| --- | --- | --- |
| CPF válido | 200 | `{"access_token": "<jwt>", "token_type": "Bearer", "expires_in": 3600}` |
| CPF inválido | 401 | `{"error": "Unauthorized", "message": "CPF inválido."}` |
| Sem `cpf` (string) ou JSON inválido | 400 | `{"error": "Bad Request", "message": "..."}` |
| `JWT_SECRET` ausente | 500 | `{"error": "Internal Server Error", ...}` |

## Token

HS256 assinado com `JWT_SECRET`, compatível com `src/Core/Auth/AbstractJwtService.php`. Claims: `iss` (`JWT_ISSUER`), `iat`, `exp` (`iat + JWT_TTL`), `sub` (CPF só com dígitos) e `role` (`cliente`).

`JWT_SECRET`, `JWT_ISSUER` e `JWT_TTL` precisam ser os mesmos do PHP. No Terraform vêm de `jwt_secret` (obrigatória na AWS; os workflows passam `secrets.JWT_SECRET` como `TF_VAR_jwt_secret`), `jwt_issuer` e `jwt_ttl`.

## CPF

Aceita com ou sem `.` e `-`. Diferente do `Cpf.php`, rejeita caracteres não numéricos e dígitos repetidos (`111.111.111-11`).

## Testes

```bash
cd lambda/autenticacao && npm test
```

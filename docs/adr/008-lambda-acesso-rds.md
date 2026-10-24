# ADR-008 - Lambda de autenticação consulta o RDS diretamente

**Status:** Aceita  
**Data:** 22/10/2026  
**Participantes:** Augusto, Claudio, Daniel, Fernando, Oeslei  

## Contexto

A autenticação de clientes (`POST /autenticacao`, via API Gateway) é feita por uma Lambda que valida o CPF/CNPJ e emite o JWT. Para emitir o token só para quem é cliente da oficina, a função precisa saber se o CPF/CNPJ está cadastrado na tabela `clientes`, que fica no MySQL do Amazon RDS ([ADR-007](007-rds-mysql.md)). Esse banco está em sub-redes privadas, sem NAT, e só é acessível a partir do EKS.

Não existe um status de cliente separado: no nosso sistema, ter o CPF/CNPJ cadastrado significa que o cliente está ativo. Por isso, a consulta só verifica a existência do registro.

## Decisão

A Lambda consulta o RDS diretamente, com:

- **Rede:** a função roda nas sub-redes privadas, com security group próprio. A saída é só para a porta 3306 do SG do RDS, que aceita entrada desse SG.
- **Credenciais:** usuário MySQL `autenticacao`, com somente `SELECT` no banco e `REQUIRE SSL`, criado pelo Job `rds-user-setup` do Helm. A senha vem do secret `LAMBDA_DB_PASSWORD` e chega à Lambda por variável de ambiente via Terraform, como o `JWT_SECRET`.
- **Conexão:** driver `mysql2`, com TLS validado pelo bundle de certificados do RDS. O pool tem uma conexão e fica fora do handler, para ser reaproveitado entre invocações.
- **Respostas:** CPF/CNPJ válido e não cadastrado retorna `404`, e falha ao consultar o banco retorna `503`.

## Motivação

- Menor latência e nenhuma dependência do EKS para autenticar.
- Variável de ambiente evita custo e VPC Endpoint, que seriam necessários para usar o Secrets Manager sem NAT.

## Consequências

### Positivas

- Só clientes cadastrados recebem token.
- Usuário somente leitura e com TLS limita o impacto de um vazamento da senha.

### Negativas

- **Banco compartilhado:** a Lambda depende do schema de `clientes` e do formato do `cpf_cnpj` definidos pelo PHP: sem separadores e, no CNPJ alfanumérico, em maiúsculas.
- A senha do banco fica em texto claro no state do Terraform (bucket S3 criptografado) e na configuração da função. A rotação exige um novo deploy.
- O `SELECT` é concedido no banco inteiro, não só em `clientes`: o Job roda antes das migrations, e o MySQL não aceita `GRANT SELECT` em uma tabela que ainda não existe.
- A função passa a ter uma dependência externa (`mysql2`): o deploy precisa rodar `npm ci` antes do Terraform.
- **Enumeração de CPF/CNPJ:** o `404` revela se um documento está cadastrado. É um risco aceito por enquanto, já que o endpoint não tem rate limit.
- A Lambda na VPC aumenta o cold start, e o `terraform destroy` fica mais lento por causa da liberação das ENIs.

## Alternativas consideradas

- **Lambda chama a API PHP:** mantém o contexto Clientes como dono dos dados, mas acopla a autenticação à disponibilidade do EKS.
- **Secrets Manager ou IAM Database Authentication:** mais seguros, mas exigem VPC Endpoint ou configuração extra no RDS e no usuário MySQL.
- **`401` para cliente não cadastrado:** evita a enumeração, mas não diferencia o caso para o front-end.

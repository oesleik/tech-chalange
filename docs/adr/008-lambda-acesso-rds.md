# ADR-008 - Lambda de autenticação consulta o RDS diretamente

**Status:** Aceita  
**Data:** 22/10/2026  
**Participantes:** Augusto, Claudio, Daniel, Fernando, Oeslei  

## Contexto

A Lambda de autenticação ([ADR-006](006-node-lambda.md)) emitia o JWT para qualquer CPF válido. Ela precisa passar a emitir o token só para clientes cadastrados na tabela `clientes`, que fica no MySQL do Amazon RDS ([ADR-007](007-rds-mysql.md)). Esse banco está em sub-redes privadas, sem NAT, e só é acessível a partir do EKS.

O status do cliente (ativo/inativo) ficou fora do escopo: a tabela não tem esse conceito ainda.

## Decisão

A Lambda consulta o RDS diretamente, com:

- **Rede:** a função roda nas sub-redes privadas, com security group próprio. A saída é só para a porta 3306 do SG do RDS, que aceita entrada desse SG.
- **Credenciais:** usuário MySQL `autenticacao`, com somente `SELECT` no banco e `REQUIRE SSL`, criado pelo Job `rds-user-setup` do Helm. A senha vem do secret `LAMBDA_DB_PASSWORD` e chega à Lambda por variável de ambiente via Terraform, como o `JWT_SECRET`.
- **Conexão:** driver `mysql2`, com TLS validado pelo bundle de certificados do RDS. O pool tem uma conexão e fica fora do handler, para ser reaproveitado entre invocações.
- **Respostas:** CPF válido e não cadastrado retorna `404`, e falha ao consultar o banco retorna `503`.

## Motivação

- Menor latência e nenhuma dependência do EKS para autenticar.
- Variável de ambiente evita custo e VPC Endpoint, que seriam necessários para usar o Secrets Manager sem NAT.

## Consequências

### Positivas

- Só clientes cadastrados recebem token.
- Usuário somente leitura e com TLS limita o impacto de um vazamento da senha.

### Negativas

- **Banco compartilhado:** a Lambda depende do schema de `clientes` e do formato do `cpf_cnpj` (só dígitos) definidos pelo PHP.
- A senha do banco fica em texto claro no state do Terraform (bucket S3 criptografado) e na configuração da função. A rotação exige um novo deploy.
- O `SELECT` é concedido no banco inteiro, não só em `clientes`: o Job roda antes das migrations, e o MySQL não aceita `GRANT SELECT` em uma tabela que ainda não existe.
- Fim do "sem dependências" do ADR-006: o deploy precisa rodar `npm ci` antes do Terraform.
- **Enumeração de CPFs:** o `404` revela se um CPF está cadastrado. É um risco aceito por enquanto, já que o endpoint não tem rate limit.
- A Lambda na VPC aumenta o cold start, e o `terraform destroy` fica mais lento por causa da liberação das ENIs.

## Alternativas consideradas

- **Lambda chama a API PHP:** mantém o contexto Clientes como dono dos dados, mas acopla a autenticação à disponibilidade do EKS.
- **Secrets Manager ou IAM Database Authentication:** mais seguros, mas exigem VPC Endpoint ou configuração extra no RDS e no usuário MySQL.
- **`401` para cliente não cadastrado:** evita a enumeração, mas não diferencia o caso para o front-end.

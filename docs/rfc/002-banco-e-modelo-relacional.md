# RFC-002 - Banco relacional e modelo de dados

**Status:** Aceito e adotado no projeto  
**Decisões registradas em:** ADR-001 e ADR-007  
**Escopo:** Banco local, serviço de produção e modelo relacional da oficina.

## Contexto

O domínio inclui clientes, veículos, ordens de serviço, peças, serviços e movimentos de estoque. Uma OS relaciona esses cadastros e agrega itens cuja integridade referencial e consistência transacional são importantes para o fluxo da oficina.

## Proposta

Usar MySQL local em Docker Compose e Amazon RDS for MySQL na AWS. Manter um schema relacional com PKs, FKs, tabelas de associação para itens de OS, enum de situação e tipos decimais para valores monetários. Executar alterações do schema por migrations versionadas na aplicação.

## Alternativas consideradas

- Banco orientado a documentos: reduz o uso de joins, mas não é a forma mais direta de impor as relações e integridade referencial usadas pelo domínio.
- PostgreSQL: atende aos requisitos relacionais, mas não foi selecionado pela familiaridade e integração já adotadas no projeto.
- MySQL dentro do EKS em produção: evita serviço externo, mas acopla persistência ao ciclo de vida e capacidade do cluster.

## Justificativa

MySQL fornece transações, constraints e consultas SQL adequadas ao fluxo da oficina. A mesma família de banco em desenvolvimento e produção diminui divergências de comportamento. O RDS gerenciado reduz o trabalho de operar armazenamento do banco junto aos pods, sem eliminar a responsabilidade por backup, segurança e recuperação.

## Modelo atual e pontos de atenção

O modelo de dados e os relacionamentos estão documentados em [dbml.png](../arch/dbml.png). O modelo atual tem FKs de OS para cliente e veículo, mas não implementa uma FK de propriedade veículo-cliente. Também não mantém snapshot de preços nos itens da OS nem timestamp/referência da OS nos movimentos de estoque.

Esses três pontos não devem ser presumidos pelo diagrama como funcionalidades presentes. Se forem requisitos, devem ser decididos, migrados e testados separadamente.

## Riscos e trade-offs em produção

- O RDS atual está em Single-AZ.
- `backup_retention_period = 0` desabilita backups automáticos e recuperação point-in-time.
- `skip_final_snapshot = true` significa que a exclusão não cria automaticamente snapshot final.
- Esses defaults reduzem custo de um ambiente de estudos, mas permitem perda permanente de dados. A configuração deve ser revista antes de armazenar dados relevantes.

# Clean Architecture

Referência para replicar o padrão em qualquer módulo.

## Camadas

De fora para dentro: **Presentation** (Router, Controller, Mapper HTTP, Response DTO) e **Infrastructure** (Gateway, Mapper de persistência) dependem de **Application** (Use Case, Input/Output DTO, interface Gateway), que depende de **Domain** (Entity, Value Object, Exception).

Dependências apontam apenas para dentro. Presentation e Infrastructure não se conhecem; Domain não conhece ninguém.

## Arquivos

| Doc | Conteúdo |
|-----|----------|
| [ESTRUTURA.md](./ESTRUTURA.md) | Pastas + checklist |
| [EXEMPLOS.md](./EXEMPLOS.md) | Exemplo de cada peça |

## Não fazer

- `Model/`, `Service/`, `Validator/` na raiz do módulo
- SQL/PDO em Application ou Domain
- Parsear query/body HTTP dentro da Application
- Duplicar regra de Value Object em Constraint Symfony

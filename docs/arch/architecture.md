# Arquitetura e modelo de dados

Este documento consolida a arquitetura configurada no projeto e os fluxos de autenticação e abertura de ordens de serviço. Os diagramas usam os contratos, as rotas, as migrations e a infraestrutura como código disponíveis neste workspace; recomendações de evolução estão identificadas como propostas, não como comportamento já implementado.

## Diagrama de componentes

O diagrama representa a entrada pública, os caminhos de autenticação e da API PHP, a persistência e a observabilidade AWS. A topologia específica de redes e recursos está detalhada no [diagrama AWS](./AWS-Architecture.png); este diagrama acrescenta os limites de aplicação e o HPA.

<!-- mermaid-checked: no \n, no em-dash/en-dash, no {} in labels, subgraphs are id["label"], arrows are -->|"label"|, all subgraphs closed by end, ids unique -->
```mermaid
flowchart TD
    User["Cliente ou funcionário"]
    Admin["Operador AWS"]
    subgraph AWS["AWS us-east-1"]
        Api["API Gateway HTTP API"]
        EksControl["EKS control plane API"]
        subgraph Vpc["VPC"]
            subgraph Public["Sub-redes públicas"]
                Link["VPC Link"]
                Nlb["NLB interno"]
                subgraph EKS["EKS"]
                    Nginx["Nginx NodePort 30080"]
                    Php["PHP Slim API"]
                        Hpa["PHP and Nginx HPAs"]
                        Metrics["metrics-server"]
                        Nri["New Relic nri-bundle"]
                    end
            end
            subgraph Private["Sub-redes privadas"]
                Lambda["Lambda Node.js autenticação"]
                Rds[("Amazon RDS MySQL")]
            end
        end
        Ecr[("Amazon ECR")]
        Cw[("CloudWatch Logs")]
        Firehose["Kinesis Data Firehose"]
    end
    Nr["New Relic"]
    Smtp["External SMTP relay"]

    User -->|"HTTPS"| Api
    Admin -->|"kubectl com IAM"| EksControl
    Api -->|"POST autenticacao"| Lambda
    Lambda -->|"consulta cliente TLS 3306"| Rds
    Api -->|"rota default"| Link
    Link -->|"HTTP"| Nlb
    Nlb -->|"NodePort 30080"| Nginx
    Nginx -->|"FastCGI"| Php
    Php -->|"SQL 3306"| Rds
    Ecr -.->|"pull das imagens"| Php
    Ecr -.->|"pull das imagens"| Nginx
    Metrics -.->|"fornece métricas"| Hpa
    Hpa -.->|"escala réplicas"| Php
    Hpa -.->|"escala réplicas"| Nginx
    Api -.->|"access logs"| Cw
    Lambda -.->|"logs da função"| Cw
    Cw -->|"subscription de logs"| Firehose
    Firehose -->|"ingestão"| Nr
    Php -.->|"APM, traces e logs"| Nr
    Nri -.->|"telemetria Kubernetes"| Nr
    Php -->|"envio de e-mail SMTP"| Smtp
```

### Fluxos e limites

- `POST /autenticacao` é uma rota específica do API Gateway integrada à Lambda. A função valida CPF/CNPJ, consulta `clientes` no RDS e emite um JWT HS256.
- As demais rotas seguem a rota `$default`: API Gateway → VPC Link → NLB interno → Nginx → PHP. O middleware PHP valida o JWT; o API Gateway não atua como autorizador.
- A aplicação PHP e a Lambda acessam o mesmo RDS. A Lambda consulta a tabela `clientes` com credencial de leitura; a aplicação executa as operações da oficina.
- Os logs do API Gateway e da Lambda passam pelo CloudWatch; o pipeline New Relic também recebe logs e telemetria do cluster conforme a configuração da plataforma.
- O HPA escala réplicas de pods, não nós EKS. O node group declarado no Terraform tem limites configurados separadamente.
- O endpoint de gerenciamento do control plane EKS está configurado como público (`endpoint_public_access = true`, `endpoint_private_access = false`), sem lista de restrição por CIDR declarada no recurso Terraform. É distinto do endpoint público da aplicação e merece revisão de acesso antes de produção.

## Sequência de autenticação

Implementação correspondente à Lambda `POST /autenticacao`, à consulta de existência de cliente e à geração do token. Requisições autenticadas subsequentes usam o fluxo de abertura de OS abaixo.

<!-- mermaid-checked: no \n, no em-dash/en-dash, no {} in labels, subgraphs are id["label"], arrows are -->|"label"|, all subgraphs closed by end, ids unique -->
```mermaid
sequenceDiagram
    actor Cliente
    participant Gateway as API Gateway
    participant Lambda as Lambda autenticacao
    participant RDS as RDS MySQL

    Cliente->>Gateway: POST /autenticacao com CPF ou CNPJ
    Gateway->>Lambda: Invoca integração da rota
    alt Segredo JWT ou config do banco ausente
        Lambda-->>Gateway: 500 Internal Server Error
    else Configuração disponível
        Lambda->>Lambda: Faz parse e valida CPF ou CNPJ
        alt JSON inválido ou campo ausente
            Lambda-->>Gateway: 400 Bad Request
        else Documento inválido
            Lambda-->>Gateway: 401 Unauthorized
        else Documento válido
            Lambda->>RDS: SELECT cliente por cpf_cnpj via TLS
            alt Cliente não cadastrado
                RDS-->>Lambda: Nenhum registro
                Lambda-->>Gateway: 404 Not Found
            else Falha de banco
                RDS-->>Lambda: Erro de consulta
                Lambda-->>Gateway: 503 Service Unavailable
            else Cliente encontrado
                RDS-->>Lambda: Cliente existe
                Lambda->>Lambda: Assina JWT HS256
                Lambda-->>Gateway: 200 access_token Bearer
            end
        end
    end
    Gateway-->>Cliente: Resposta HTTP
```

## Sequência de abertura de ordem de serviço

O projeto já possuía o diagrama detalhado [LLD de criação de OS](./LLD_criacao_ordem_servico.png). Esta versão compacta explicita também a entrada AWS e corresponde ao `POST /ordens-servico/` protegido pelo middleware JWT. O controller devolve `201 Created`; a gravação da OS e dos itens é realizada pelos gateways MySQL.

<!-- mermaid-checked: no \n, no em-dash/en-dash, no {} in labels, subgraphs are id["label"], arrows are -->|"label"|, all subgraphs closed by end, ids unique -->
```mermaid
sequenceDiagram
    actor Usuario as Funcionário
    participant Gateway as API Gateway
    participant VpcRoute as VPC Link e NLB
    participant Nginx
    participant PHP as PHP Slim API
    participant JWT as JwtMiddleware
    participant Controller as CriarOrdemServicoController
    participant UseCase as CriarOrdemServicoUseCase
    participant GatewayOS as Gateways de OS e itens
    participant RDS as RDS MySQL

    Usuario->>Gateway: POST /ordens-servico/ com Bearer JWT
    Gateway->>VpcRoute: Rota default
    VpcRoute->>Nginx: HTTP NodePort 30080
    Nginx->>PHP: FastCGI
    PHP->>JWT: Valida assinatura, emissor e expiração
    alt Token ausente ou inválido
        JWT-->>Usuario: 401 Unauthorized
    else Token válido
        JWT->>Controller: Encaminha requisição autenticada
        Controller->>Controller: Valida e mapeia payload
        Controller->>UseCase: Executa criação da OS
        UseCase->>GatewayOS: Insere OS e itens solicitados
        GatewayOS->>RDS: INSERT ordem_servico
        RDS-->>GatewayOS: ID da ordem
        opt Há peças ou serviços
            GatewayOS->>RDS: INSERT itens e atualiza valor total
            RDS-->>GatewayOS: Confirma gravação
        end
        GatewayOS-->>UseCase: OS criada
        UseCase-->>Controller: Entidade da OS
        Controller-->>Usuario: 201 Created
    end
```

O registro da OS e a persistência de itens são chamadas separadas no caso de uso. Os gateways de itens usam transações para as operações de adição/substituição de cada tipo de item; o caso de uso não envolve toda a criação em uma única transação global.

## Modelo relacional e justificativa do banco

O ER abaixo espelha as migrations presentes. Ele substitui, para fins de referência do schema atual, o desenho antigo em [`dbml.png`](./dbml.png), que contém a tabela `veiculo_cliente` ausente das migrations.

<!-- mermaid-checked: no \n, no em-dash/en-dash, no {} in labels, subgraphs are id["label"], arrows are -->|"label"|, all subgraphs closed by end, ids unique -->
```mermaid
erDiagram
    CLIENTES {
        int_unsigned id PK
        varchar_255 nome
        varchar_14 cpf_cnpj UK
        varchar_255 email
        varchar_50 telefone
    }
    VEICULOS {
        int_unsigned id PK
        varchar_8 placa UK
        varchar_100 marca
        varchar_100 modelo
    }
    ORDENS_SERVICO {
        int_unsigned id PK
        int_unsigned id_cliente FK
        int_unsigned id_veiculo FK
        enum situacao
        decimal_10_2 valor_total
        datetime data_solicitacao
        datetime data_aprovacao
        datetime data_situacao
    }
    PECAS {
        int_unsigned id PK
        varchar_255 descricao
        decimal_10_2 valor_unitario
    }
    SERVICOS {
        int_unsigned id PK
        varchar_255 descricao
        decimal_10_2 valor_unitario
    }
    PECAS_ORDEM_SERVICO {
        int_unsigned id PK
        int_unsigned id_ordem_servico FK
        int_unsigned id_peca FK
        int_unsigned quantidade
    }
    SERVICOS_ORDEM_SERVICO {
        int_unsigned id PK
        int_unsigned id_ordem_servico FK
        int_unsigned id_servico FK
        int_unsigned quantidade
    }
    ESTOQUE {
        int_unsigned id PK
        int_unsigned id_peca FK
        int_unsigned quantidade
        enum tipo_lancamento
    }

    CLIENTES ||--o{ ORDENS_SERVICO : "referencia"
    VEICULOS ||--o{ ORDENS_SERVICO : "referencia"
    ORDENS_SERVICO ||--o{ PECAS_ORDEM_SERVICO : "inclui"
    PECAS ||--o{ PECAS_ORDEM_SERVICO : "item"
    ORDENS_SERVICO ||--o{ SERVICOS_ORDEM_SERVICO : "inclui"
    SERVICOS ||--o{ SERVICOS_ORDEM_SERVICO : "item"
    PECAS ||--o{ ESTOQUE : "movimentos"
```

### Relacionamentos e observações do schema atual

- No diagrama, `int_unsigned` representa SQL `INT UNSIGNED` e `decimal_10_2` representa `DECIMAL(10,2)`.
- Um cliente pode estar associado a várias OS; cada OS referencia um cliente e um veículo.
- Um veículo pode aparecer em várias OS. As migrations não criam um relacionamento direto de propriedade entre veículo e cliente; a OS guarda ambos os IDs, sem constraint que garanta que o cliente seja proprietário do veículo.
- `PECAS_ORDEM_SERVICO` e `SERVICOS_ORDEM_SERVICO` resolvem os relacionamentos muitos-para-muitos entre OS e catálogo, com quantidade em cada linha.
- `ESTOQUE` referencia uma peça e registra quantidade e tipo (`entrada`/`baixa`). O schema atual não registra data do movimento nem associa a baixa a uma OS.
- Os itens de OS não armazenam o preço unitário aplicado no momento da inclusão. O total é calculado a partir dos preços atuais de catálogo; alterações futuras de preço podem afetar a reprodução histórica do orçamento.
- `valor_unitario` e `valor_total` são `DECIMAL(10,2)` nas migrations; não devem ser representados como `FLOAT` ou `DOUBLE` no ER.

### Por que MySQL

MySQL atende ao modelo transacional e relacional existente: chaves estrangeiras protegem vínculos entre clientes, veículos, ordens, catálogo e itens; transações são usadas nas operações de persistência de itens; e consultas SQL suportam os filtros e relatórios da aplicação. Também mantém uma tecnologia consistente entre desenvolvimento local em Docker e produção no Amazon RDS.

A escolha do RDS desacopla o ciclo de vida do banco dos pods e fornece armazenamento gerenciado e criptografado. **A implantação atual é Single-AZ, com backups automatizados desabilitados e sem snapshot final automático**, portanto não deve ser descrita como configuração de alta disponibilidade ou recuperação garantida. Essa escolha e seus trade-offs estão detalhados nos [ADRs 001 e 007](../adr/README.md).

### Ajustes de modelo a decidir antes de alterar migrations

1. **Propriedade de veículo:** decidir se é necessária. Se sim, substituir a tabela de associação desatualizada por uma FK em `veiculos` (propriedade única) ou uma tabela de histórico/associação com vigência (propriedade múltipla), e validar que a OS só aceite veículo associado ao cliente informado.
2. **Histórico de preços:** se o orçamento aprovado precisar permanecer imutável, salvar o preço unitário aplicado e descrição relevante em cada item da OS; calcular o total a partir desses snapshots.
3. **Auditoria do estoque:** se for necessário rastrear quando e por que cada movimento ocorreu, incluir data e referência opcional à OS, mantendo a peça como FK.

Esses três pontos são recomendações de evolução do modelo, não mudanças implementadas nas migrations atuais.

## RFCs e ADRs

- [RFC-001 - Plataforma de nuvem](../rfc/001-plataforma-nuvem.md) → [ADR-011 - AWS](../adr/011-aws.md)
- [RFC-002 - Banco relacional e modelo de dados](../rfc/002-banco-e-modelo-relacional.md) → [ADRs 001 e 007 - MySQL e RDS](../adr/README.md)
- [RFC-003 - Autenticação e comunicação das APIs](../rfc/003-autenticacao-e-comunicacao.md) → [ADRs 008 e 010 - Lambda e API Gateway](../adr/README.md)
- Outros [ADRs aceitos](../adr/README.md), incluindo HPA, observabilidade e divisão dos repositórios.

# ADR-007 - MySQL gerenciado no Amazon RDS

## Contexto

A aplicação do Tech Challenge utiliza PHP e MySQL. Na implantação AWS
anterior, o MySQL era executado dentro do Kubernetes, junto aos workloads
da aplicação, e dependia de armazenamento persistente e do driver EBS CSI.

Manter o banco no cluster aumenta a quantidade de pods e adiciona
responsabilidades de infraestrutura relacionadas ao armazenamento,
agendamento, disponibilidade e manutenção do banco.

A capacidade de um nó EKS não depende apenas do consumo de CPU e memória:
também é limitada pela quantidade de pods suportada, pelos recursos
reservados nos requests e pela memória disponível ao sistema operacional.

Precisamos reduzir o acoplamento entre a aplicação e o banco de dados,
simplificando a implantação e permitindo avaliar nós de menor capacidade.

## Decisão proposta

Utilizar Amazon RDS for MySQL como banco de dados gerenciado no ambiente AWS.

A configuração proposta prevê:

- RDS em sub-redes privadas, sem acesso público.
- Acesso MySQL pela porta 3306 restrito ao security group do EKS.
- Armazenamento criptografado.
- PHP e Nginx executados no EKS.
- Endpoint do RDS fornecido à aplicação por configuração e secrets.
- MySQL local mantido para os ambientes de desenvolvimento que não
  utilizam o RDS, como a configuração padrão do MiniStack.

## Motivação

- Separar o ciclo de vida do banco do ciclo de vida dos pods.
- Reduzir a quantidade de workloads executados no cluster Kubernetes.
- Delegar ao serviço gerenciado parte das responsabilidades de operação
  do banco.
- Permitir ajustar o tamanho dos nós EKS sem precisar reservar recursos
  para um MySQL executado no cluster.
- Simplificar a persistência dos dados e a configuração da infraestrutura.

A redução de custos é um objetivo a ser avaliado, não uma economia
garantida. As estimativas apresentadas durante a revisão do PR precisam
ser confirmadas para a região, as classes e as condições reais de uso.

## Consequências

### Positivas

- Banco independente dos deployments da aplicação.
- Menos pods e menor acoplamento dentro do EKS.
- Possibilidade de testar tipos menores de instância dos nós.
- Armazenamento gerenciado e criptografado.
- Menor necessidade de administrar o banco dentro do Kubernetes.

### Negativas e riscos

- O RDS gera custo adicional, mesmo quando a aplicação tem pouco uso.
- A aplicação depende da conectividade entre o EKS e o RDS.
- Credenciais e regras de rede passam a ser responsabilidades
  importantes da configuração AWS.
- A configuração atual utiliza uma implantação Single-AZ, sem
  alta disponibilidade Multi-AZ.
- O workflow atual permite excluir o banco sem snapshot final.
  A exclusão pode resultar em perda definitiva dos dados.
- A classe ideal para o EKS e para o RDS deve ser validada por testes
  de implantação, capacidade e custo.

## Alternativas consideradas

### MySQL dentro do Kubernetes

Mantém o banco junto à aplicação, usando armazenamento persistente.
Foi a abordagem anterior, mas mantém o consumo de recursos e a
dependência do armazenamento EBS no cluster.

### Amazon RDS for MySQL

Opção escolhida para a implantação AWS por desacoplar o banco dos pods
e reduzir a responsabilidade operacional sobre o MySQL.

- No ambiente de estudos, a retenção de backups automatizados do RDS
  fica em zero (`backup_retention_period = 0`) para reduzir custos.
  Essa configuração desabilita os backups automatizados e a recuperação
  point-in-time. Como `skip_final_snapshot = true`, a exclusão da
  instância também não cria automaticamente um snapshot final.

### Banco local nos ambientes de desenvolvimento

Continua sendo utilizado nos ambientes locais que não dependem do RDS,
preservando o fluxo de desenvolvimento e testes existente.

# ADR-005 - Migração para Clean Architecture

**Status:** Aceita
**Data:** 30/08/2026
**Participantes:** Augusto, Claudio, Daniel, Fernando, Oeslei

## Contexto

O projeto foi iniciado sem um padrão arquitetural definido para organização interna dos módulos. Módulos como `Servicos` foram construídos em uma estrutura simples, próxima do padrão nativo do Slim: `Controller`, `Service`, `Model` e `Contract`, com o controller lidando diretamente com request/response HTTP, contrato de entrada/saída e chamando um `Service` que concentra regra de negócio e acesso a dados.

Essa estrutura funcionou nas fases iniciais, mas trouxe problemas à medida que o domínio da oficina (clientes, veículos, ordens de serviço, serviços, peças e estoque) cresceu:

- Regras de negócio, validação de contrato, mapeamento HTTP e persistência ficavam misturadas na mesma camada, sem uma fronteira clara de responsabilidade.
- Não havia uma abstração entre a regra de negócio e a forma como os dados são persistidos, o que dificulta trocar o mecanismo de persistência ou escrever testes de unidade para a regra de negócio sem subir infraestrutura.
- Entidades de domínio (ex: `Veiculo`, `Placa`) não existiam como conceito isolado; os dados trafegavam como `Model`s anêmicos, sem local natural para concentrar invariantes do domínio (ex: validação do formato da placa).
- A ausência de um padrão explícito gerava divergência entre desenvolvedores sobre onde adicionar cada nova regra, o que preocupava conforme novos módulos (`OrdemServico`, `Peca`, `Estoque`) seriam adicionados.

O desafio técnico que motivou este trabalho exige explicitamente a adoção de uma arquitetura em camadas com separação de responsabilidades e baixo acoplamento com frameworks e infraestrutura, o que motivou a reavaliação do padrão usado até então.

## Decisão

A equipe decidiu migrar a organização interna dos módulos para **Clean Architecture**, adotando as seguintes camadas dentro de cada módulo de domínio (`Veiculos`, `Clientes`, `OrdemServico`, `Peca`, `Estoque`):

- **`Domain`**: entidades e regras de negócio invariantes do domínio (ex: `Veiculo`, `Placa`), além das exceções de domínio (ex: `VeiculoJaCadastradoException`, `VeiculoNaoEncontradoException`). Não depende de nenhuma outra camada.
- **`Application`**: casos de uso (`UseCase`), um por operação de negócio (ex: `CriarVeiculoUseCase`, `ListarVeiculoUseCase`), orquestrando entidades de domínio através de interfaces de gateway (`VeiculoGatewayInterface`) definidas nesta mesma camada. Os casos de uso não conhecem detalhes de HTTP nem de persistência, apenas a abstração do gateway.
- **`Infrastructure`**: implementação concreta dos gateways definidos na camada de aplicação (ex: `VeiculoGateway`), incluindo o mapeamento entre entidade de domínio e o modelo de persistência (ex: `VeiculoMapper`).
- **`Presentation`**: camada de entrada HTTP, com `Controller`, `Router` e DTOs/`Mapper` de request e response, responsável por traduzir a requisição HTTP em chamada ao caso de uso e o resultado do caso de uso em resposta HTTP.

A regra de dependência segue o sentido único da Clean Architecture: `Presentation` e `Infrastructure` dependem de `Application`, que depende de `Domain`; nenhuma camada interna depende de camada externa. A comunicação entre `Application` e `Infrastructure` acontece sempre por meio de interfaces (gateways) definidas em `Application` e injetadas via container de DI (`configs/service-container`).

## Motivação

A Clean Architecture foi escolhida por:

- Isolar a regra de negócio (`Domain`/`Application`) de frameworks e detalhes de infraestrutura (HTTP, banco de dados), permitindo testar casos de uso e entidades de domínio isoladamente, sem subir Slim ou banco.
- Explicitar, via interfaces de gateway, o ponto de troca entre regra de negócio e persistência, facilitando eventual substituição do mecanismo de armazenamento sem alterar `Domain` ou `Application`.
- Dar um local natural para invariantes e validações de domínio (ex: `Placa`), reduzindo entidades anêmicas.
- Estabelecer um vocabulário e uma estrutura de pastas comuns entre os módulos, reduzindo a divergência de onde cada tipo de código deve morar à medida que novos módulos são adicionados.
- Atender ao requisito explícito do desafio técnico deste trabalho, que avalia a aplicação de uma arquitetura em camadas com baixo acoplamento.

## Consequências

### Positivas

- Regras de negócio testáveis isoladamente, sem dependência de HTTP ou banco de dados (casos de uso e entidades cobertos por testes unitários, ex: `CriarVeiculoUseCaseTest`, `VeiculoTest`, `PlacaTest`).
- Fronteira clara entre camadas, reduzindo o acoplamento entre a forma de entrada (HTTP) e a forma de persistência (MySQL).
- Estrutura de pastas padronizada e replicável para novos módulos de domínio.
- Facilidade para trocar a implementação de um gateway (ex: mock em teste, ou nova fonte de dados no futuro) sem alterar a regra de negócio.
- Mapeamento explícito entre entidade de domínio e modelo de persistência (`*Mapper` em `Infrastructure`) evita vazamento de detalhes de banco para o domínio.

### Negativas

- Maior quantidade de arquivos e indireção por operação (entidade, caso de uso, interface de gateway, implementação, mapper, DTO de entrada/saída, controller, router) comparado à estrutura anterior, mais direta para casos simples.
- Curva de aprendizado para quem não tem familiaridade prévia com Clean Architecture, especialmente para diferenciar responsabilidade entre `Application` e `Infrastructure`.
- Convivência temporária de dois padrões no mesmo repositório (`Servicos` no padrão antigo, demais módulos no novo), o que exige atenção até a migração completa.
- Maior esforço para alterações simples e pontuais, já que uma mudança de regra pode exigir tocar em várias camadas em vez de um único arquivo.

## Alternativas consideradas

- **Manter o padrão simples (`Controller`/`Service`/`Model`) usado em `Servicos`:** mais rápido para desenvolver, porém sem separação clara entre regra de negócio, HTTP e persistência, dificultando testes isolados e não atendendo ao requisito do desafio de demonstrar uma arquitetura em camadas.

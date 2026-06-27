# ADR-003 - PHP Slim como framework do projeto

**Status:** Aceita  
**Data:** 20/06/2026  
**Participantes:** Augusto, Claudio, Daniel, Fernando, Oeslei  

## Contexto

O projeto precisa de alguma forma de rotear e gerenciar requisições HTTP. A nível de servidor já usamos ngnix, mas precisamos de uma definição de como gerenciar a nível de código.

## Decisão

Foi decidido utilizar o [framework Slim](https://www.slimframework.com/).

## Motivação

O Slim é um framework simples que gerencia a camada de entrada e resposta das requisições, com uma abordagem simples para mapear e rotear as rotas para o controller correspondente.

A principal motivação foi sua simplicidade e como ele evita entrar em camadas mais profundas da aplicação, afetando menos a arquitetura do projeto do que outras alternativas consideradas.

A intenção deste projeto como um todo é o estudo e aplicação de técnicas sobre arquitetura de software. Quanto menos o framework entrega pronto melhor para nosso aprendizado.

## Consequências

### Positivas

- Roteamento de endpoints simples
- Compatibilidade com PSR-7
- Uso de middlewares para autenticação
- Tratamento de erros embutido
- Facilidade de uso junto com o PHP-DI

### Negativas

- Requer mais trabalho inicial, não é um framework completo como as alternativas

## Alternativas consideradas

- **Laravel:** framework muito forte na comunidade, mas que força bastante a arquitetura do projeto, o que pode prejudicar nossos estudos e aprendizados
- **Symfony:** também muito forte na comunidade, com mais possibilidades de customização, mas ainda facilita demais na arquitetura do projeto

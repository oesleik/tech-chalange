# ADR-001 - Escolha do MySQL como banco de dados

**Data:** 20/06/2026

**Status:** Aceita

## Contexto

O sistema será desenvolvido utilizando PHP 8.4, Slim 4, Nginx e Docker. Como o domínio possui entidades relacionadas, como clientes, veículos, ordens de serviço, serviços, peças e estoque, é necessário utilizar um banco de dados que garanta a integridade dos dados e facilite o relacionamento entre essas informações.

Além disso, o banco deve ser simples de configurar em ambiente Docker, possuir boa documentação e ser de fácil utilização pela equipe de desenvolvimento.

## Decisão

A equipe decidiu utilizar o **MySQL** como banco de dados da aplicação.

A escolha foi motivada por ser um banco de dados relacional consolidado, que atende às necessidades do projeto. O MySQL oferece suporte a relacionamentos entre tabelas, transações, chaves estrangeiras e consultas SQL, garantindo a consistência das informações da oficina.

Outro fator considerado foi a facilidade de integração com a stack utilizada no projeto e sua ampla documentação, facilitando o desenvolvimento e a manutenção da aplicação e também a familiaridade dos integrantes deste projeto com a tecnologia.

## Consequências

### Positivas

- Banco de dados relacional estável e amplamente utilizado.
- Fácil integração com Docker e com a stack adotada no projeto.
- A equipe já possui maturidade com o MySQL, reduzindo a curva de aprendizado durante o desenvolvimento.
- Facilidade para modelar relacionamentos entre as entidades.
- Grande quantidade de documentação e suporte da comunidade.

### Negativas

- Caso a aplicação evolua para um cenário de alta escalabilidade ou grande volume de dados, poderá ser necessário reavaliar a solução adotada.
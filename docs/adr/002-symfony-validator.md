# ADR-002 - Validação de contratos com Symfony Validator

**Status:** Aceita  
**Data:** 20/06/2026  
**Participantes:** Oeslei  

## Contexto

O sistema precisa adotar algum formato de validação de contratos da API para garantir a entrada de dados dentro dos requisitos de cada endpoint.

## Decisão

Foi decidido utilizar a [biblioteca de validação do Symfony](https://symfony.com/doc/current/validation.html) (`symfony/validator`).

Para utilização, foi decidido que será implementado através de um método `getConstraints` nos contratos.

## Motivação do uso

Symfony é um framework completo bem consolidado na comunidade, mas que também entrega cada parte como libs isoladas, facilitando o uso sem obrigar a utilização do framework completo.

Usando a lib, conseguimos validar não apenas a tipagem dos dados, mas também regras específicas, como garantia de valores positivos, strings não vazias, além da criação de validações próprias, como CPF/CNPJ.

## Motivação da forma de uso

A biblioteca permite o uso de atributos nativos do PHP para utilização, porém neste formato a validação é feita após a inicialização do objeto, ou seja, erros de tipagem (ex string informada para campo int) geram erros fatais do PHP e teriam que ser tratados previamente.

Por este motivo, optamos por listar manualmente as constraints para que possamos validar o input antes de criar o objeto.

## Consequências

### Positivas

- Reutilização de validações customizadas
- Validações básicas prontas (ex NotBlank, NotNull, Positive, etc)
- Código mais limpo, com menos validações manuais em cada endpoint
- A lista de constraints fica junto do contrato, facilitando a manutenção
- Validações de tipagem acontecem antes da criação do objeto, evitando erros fatais do PHP

### Negativas

- Curva de aprendizado sobre como utilizar os assertions

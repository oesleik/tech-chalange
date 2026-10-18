# ADR-006 - Node.js nas funções Lambda

**Status:** Aceita  
**Data:** 18/10/2026  
**Participantes:** Augusto, Claudio, Daniel, Fernando, Oeslei  

## Contexto

A autenticação por CPF (validação do CPF e emissão do JWT) passou a ser feita por uma função AWS Lambda, acessada via API Gateway. Precisamos definir a linguagem/runtime das Lambdas do projeto.

## Decisão

Foi decidido utilizar Node.js, na última versão LTS suportada pela Lambda (`nodejs24.x`).

## Motivação

- **Familiaridade:** o time já conhece JavaScript, o que reduz a curva de aprendizado e mantém o foco do estudo na arquitetura (Lambda, API Gateway, integração), não na linguagem.
- **Simplicidade com Lambda:** Node.js é um runtime nativo da AWS Lambda. O código é empacotado como um zip simples, sem build, sem runtime customizado e sem dependências externas, já que a própria biblioteca padrão (`node:crypto`, `node:test`) atende à função.

## Consequências

### Positivas

- Desenvolvimento e manutenção rápidos pelo time
- Deploy simples via Terraform
- Cold start baixo e pacote pequeno

### Negativas

- Segunda linguagem no projeto, além do PHP
- Regras duplicadas entre PHP e Node (validação de CPF, formato do JWT) precisam ser mantidas compatíveis

## Alternativas consideradas

- **PHP:** mesma linguagem da aplicação, mas não é runtime nativo da Lambda; exigiria runtime customizado.
- **Python:** runtime nativo e simples, mas com menos familiaridade do time.

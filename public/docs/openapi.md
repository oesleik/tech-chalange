# Documentação da API — Tech Challenge

> **Base URL:** `http://localhost`  
> **Autenticação:** Bearer JWT (exceto rotas públicas marcadas com 🔓)

---

## Índice

- [Health](#health)
- [Clientes](#clientes)
- [Veículos](#veículos)
- [Peças](#peças)
- [Serviços](#serviços)
- [Estoque](#estoque)
- [Ordens de Serviço](#ordens-de-serviço)
  - [Peças e Serviços](#ordens-de-serviço---peças-e-serviços)
  - [Situação](#ordens-de-serviço---situação)
  - [Relatórios](#ordens-de-serviço---relatórios)
- [Consulta Pública](#consulta-pública)

---

## Health

### `GET /health` 🔓

Verifica se o servidor está funcionando.

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Servidor está funcionando |

---

## Clientes

### `GET /clientes/`

Lista os clientes cadastrados. Permite filtrar por CPF ou CNPJ.

**Query Parameters**

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `cpfCnpj` | string | Não | CPF ou CNPJ para filtrar clientes |

**Resposta `200`**

```json
{
  "clientes": [
    {
      "id": 123,
      "nome": "Fulano de Tal",
      "cpf_cnpj": "12*.***.***-89",
      "email": "fu********@example.com",
      "telefone": "*********78"
    }
  ]
}
```

---

### `POST /clientes/`

Cadastra um novo cliente.

**Request Body**

```json
{
  "nome": "Fulano de Tal",
  "cpf_cnpj": "123.456.789-89",
  "email": "fulanodetal@example.com",
  "telefone": "5412345678"
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Cliente criado com sucesso |
| `400`  | Erro de validação |
| `409`  | Conflito — cliente já existe |

**Resposta `200`**

```json
{
  "id": 123,
  "nome": "Fulano de Tal",
  "cpf_cnpj": "12*.***.***-89",
  "email": "fu********@example.com",
  "telefone": "*********78"
}
```

---

### `GET /clientes/{id}`

Obtém os detalhes de um cliente específico.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID do cliente |

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Cliente encontrado |
| `404`  | Cliente não encontrado |

---

### `PATCH /clientes/{id}`

Edita os dados de um cliente. Todos os campos são opcionais.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID do cliente |

**Request Body**

```json
{
  "nome": "Fulano de Tal",
  "cpf_cnpj": "123.456.789-89",
  "email": "fulanodetal@example.com",
  "telefone": "5412345678"
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Cliente atualizado |
| `400`  | Erro de validação |
| `404`  | Cliente não encontrado |
| `409`  | Conflito de dados |

---

## Veículos

### `GET /veiculos/`

Lista os veículos cadastrados.

**Resposta `200`**

```json
{
  "veiculos": [
    {
      "id": 1,
      "placa": "ABC-1234",
      "marca": "Toyota",
      "modelo": "Corolla"
    }
  ]
}
```

---

### `POST /veiculos/`

Cadastra um novo veículo.

**Request Body**

```json
{
  "placa": "ABC-1234",
  "marca": "Toyota",
  "modelo": "Corolla"
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Veículo criado |
| `400`  | Erro de validação |
| `409`  | Placa já cadastrada |

---

### `GET /veiculos/{id}`

Obtém os detalhes de um veículo.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID do veículo |

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Veículo encontrado |
| `404`  | Veículo não encontrado |

---

### `PATCH /veiculos/{id}`

Edita os dados de um veículo. Todos os campos são opcionais.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID do veículo |

**Request Body**

```json
{
  "placa": "ABC-1234",
  "marca": "Toyota",
  "modelo": "Corolla"
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Veículo atualizado |
| `400`  | Erro de validação |
| `404`  | Veículo não encontrado |
| `409`  | Conflito de dados |

---

## Peças

### `GET /pecas/`

Lista as peças cadastradas.

**Resposta `200`**

```json
{
  "pecas": [
    {
      "id": 1,
      "descricao": "Filtro de óleo",
      "valor_unitario": 49.90
    }
  ]
}
```

---

### `POST /pecas/`

Cadastra uma nova peça.

**Request Body**

```json
{
  "descricao": "Filtro de óleo",
  "valor_unitario": 49.90
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Peça criada |
| `400`  | Erro de validação |

---

### `GET /pecas/{id}`

Obtém os detalhes de uma peça.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da peça |

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Peça encontrada |
| `404`  | Peça não encontrada |

---

### `PATCH /pecas/{id}`

Edita os dados de uma peça. Todos os campos são opcionais.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da peça |

**Request Body**

```json
{
  "descricao": "Filtro de óleo",
  "valor_unitario": 49.90
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Peça atualizada |
| `400`  | Erro de validação |
| `404`  | Peça não encontrada |

---

## Serviços

### `GET /servicos/`

Lista os serviços cadastrados.

**Resposta `200`**

```json
{
  "servicos": [
    {
      "id": 1,
      "descricao": "Troca de óleo",
      "valor_unitario": 49.90
    }
  ]
}
```

---

### `POST /servicos/`

Cadastra um novo serviço.

**Request Body**

```json
{
  "descricao": "Troca de óleo",
  "valor_unitario": 49.90
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Serviço criado |
| `400`  | Erro de validação |

---

### `GET /servicos/{id}`

Obtém os detalhes de um serviço.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID do serviço |

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Serviço encontrado |
| `404`  | Serviço não encontrado |

---

### `PATCH /servicos/{id}`

Edita os dados de um serviço. Todos os campos são opcionais.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID do serviço |

**Request Body**

```json
{
  "descricao": "Troca de óleo",
  "valor_unitario": 49.90
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Serviço atualizado |
| `400`  | Erro de validação |
| `404`  | Serviço não encontrado |

---

## Estoque

### `POST /estoque/entrada`

Registra a entrada de peças no estoque.

**Request Body**

```json
{
  "id_peca": 123,
  "quantidade": 10
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `201`  | Entrada registrada com sucesso |
| `404`  | Peça não encontrada |
| `422`  | Dados inválidos |

**Resposta `201`**

```json
{
  "id": 456,
  "id_peca": 123,
  "quantidade": 10,
  "tipo_lancamento": "entrada"
}
```

---

### `POST /estoque/baixa`

Registra a baixa de peças no estoque.

**Request Body**

```json
{
  "id_peca": 123,
  "quantidade": 1
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `201`  | Baixa registrada com sucesso |
| `404`  | Peça não encontrada |
| `409`  | Estoque insuficiente |
| `422`  | Dados inválidos |

**Resposta `201`**

```json
{
  "id": 456,
  "id_peca": 123,
  "quantidade": 1,
  "tipo_lancamento": "baixa"
}
```

---

### `GET /estoque/pecas/{id}`

Consulta o estoque atual de uma peça.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da peça |

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Estoque atual da peça |
| `404`  | Peça não encontrada |

**Resposta `200`**

```json
{
  "id_peca": 123,
  "estoque_atual": 10
}
```

---

## Ordens de Serviço

### Situações possíveis

```
Recebida → EmDiagnostico → AguardandoAprovacao → Aprovada → EmExecucao → Finalizada → Entregue
                                                 ↘ Rejeitada
```

---

### `GET /ordens-servico/`

Lista todas as ordens de serviço.

**Resposta `200`**

```json
[
  {
    "id": 123,
    "id_cliente": 456,
    "id_veiculo": 789,
    "situacao": "EmExecucao",
    "valor_total": 500.00,
    "data_solicitacao": "2026-06-14 10:30:00",
    "data_aprovacao": "2026-06-14 11:00:00"
  }
]
```

---

### `POST /ordens-servico/`

Cria uma nova ordem de serviço.

**Request Body**

```json
{
  "id_cliente": 456,
  "id_veiculo": 789
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `201`  | Ordem de serviço criada |
| `400`  | Erro de validação |

---

### `GET /ordens-servico/{id}`

Obtém os detalhes completos de uma ordem de serviço, incluindo peças e serviços.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da ordem de serviço |

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Detalhes da ordem |
| `404`  | Ordem não encontrada |

**Resposta `200`**

```json
{
  "id": 123,
  "id_cliente": 456,
  "id_veiculo": 789,
  "situacao": "EmExecucao",
  "valor_total": 500.00,
  "data_solicitacao": "2026-06-14 10:30:00",
  "data_aprovacao": "2026-06-14 11:00:00",
  "pecas": [
    { "id_peca": 1, "quantidade": 2 }
  ],
  "servicos": [
    { "id_servico": 3, "quantidade": 1 }
  ]
}
```

---

### `GET /ordens-servico/proxima`

Obtém a próxima ordem de serviço na fila (aguardando diagnóstico ou já aprovada).

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Próxima ordem disponível |
| `204`  | Nenhuma ordem pendente |

**Resposta `200`**

```json
{
  "tipo_servico": "realizar_diagnostico",
  "ordem_servico": { ... },
  "links": [
    {
      "rel": "self",
      "href": "http://localhost/ordens-servico/123",
      "method": "GET"
    }
  ]
}
```

> `tipo_servico` pode ser `realizar_diagnostico` ou `executar_servicos`.

---

### `POST /ordens-servico/{id}/enviar-orcamento`

Envia o orçamento da ordem de serviço por e-mail ao cliente.

**Path Parameters**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da ordem de serviço |

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | E-mail enviado com sucesso |
| `404`  | Ordem não encontrada |
| `500`  | Falha ao enviar o e-mail |

---

## Ordens de Serviço — Peças e Serviços

### `POST /ordens-servico/{id}/pecas`

Adiciona peças à ordem de serviço (mantém as peças existentes).

**Request Body**

```json
{
  "pecas": [
    { "id_peca": 1, "quantidade": 2 },
    { "id_peca": 5, "quantidade": 1 }
  ]
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Peças adicionadas |
| `400`  | Erro de validação |
| `404`  | Ordem não encontrada |
| `422`  | Não foi possível editar os itens |

---

### `PUT /ordens-servico/{id}/pecas`

Substitui **todas** as peças da ordem de serviço.

**Request Body** — mesmo formato do `POST /ordens-servico/{id}/pecas`

**Respostas** — mesmas do `POST /ordens-servico/{id}/pecas`

---

### `POST /ordens-servico/{id}/servicos`

Adiciona serviços à ordem de serviço (mantém os serviços existentes).

**Request Body**

```json
{
  "servicos": [
    { "id_servico": 3, "quantidade": 1 }
  ]
}
```

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Serviços adicionados |
| `400`  | Erro de validação |
| `404`  | Ordem não encontrada |
| `422`  | Não foi possível editar os itens |

---

### `PUT /ordens-servico/{id}/servicos`

Substitui **todos** os serviços da ordem de serviço.

**Request Body** — mesmo formato do `POST /ordens-servico/{id}/servicos`

**Respostas** — mesmas do `POST /ordens-servico/{id}/servicos`

---

## Ordens de Serviço — Situação

Todos os endpoints abaixo seguem o mesmo padrão:

- **Método:** `PUT`
- **Path Parameter:** `id` (integer) — ID da ordem de serviço
- **Respostas comuns:**

| Status | Descrição |
|--------|-----------|
| `200`  | Situação atualizada com sucesso |
| `404`  | Ordem não encontrada |
| `409`  | Transição de situação não permitida |

**Resposta `200`**

```json
{
  "id": 123,
  "id_cliente": 456,
  "id_veiculo": 789,
  "situacao": "EmDiagnostico",
  "valor_total": 500.00,
  "data_solicitacao": "2026-06-14 10:30:00",
  "data_aprovacao": null
}
```

---

### `PUT /ordens-servico/{id}/em-diagnostico`

Atualiza a situação para **Em Diagnóstico**.

---

### `PUT /ordens-servico/{id}/aguardando-aprovacao`

Atualiza a situação para **Aguardando Aprovação**.

---

### `PUT /ordens-servico/{id}/em-execucao`

Atualiza a situação para **Em Execução**.

---

### `PUT /ordens-servico/{id}/finalizada`

Atualiza a situação para **Finalizada**.

---

### `PUT /ordens-servico/{id}/entregue`

Atualiza a situação para **Entregue**.

---

### `PUT /email/ordens-servico/aprovada`

Atualiza a situação para **Aprovada** (acionado via link de e-mail enviado ao cliente).

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Ordem aprovada |
| `400`  | ID inválido |
| `404`  | Ordem não encontrada |
| `409`  | Situação atual não permite aprovação |

---

### `PUT /email/ordens-servico/rejeitada`

Atualiza a situação para **Rejeitada** (acionado via link de e-mail enviado ao cliente).

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Ordem rejeitada |
| `400`  | ID inválido |
| `404`  | Ordem não encontrada |
| `409`  | Situação atual não permite rejeição |

---

## Ordens de Serviço — Relatórios

### `GET /ordens-servico/relatorios/media_tempo_servicos`

Retorna a média de tempo de execução por serviço nas ordens de serviço finalizadas.

**Resposta `200`**

```json
{
  "servicos": [
    {
      "id_servico": 123,
      "descricao": "Troca de óleo",
      "valor_unitario": 49.90,
      "media_tempo": 1.5,
      "quantidade_execucoes": 3,
      "total_tempo_executando": 4.5,
      "min_tempo_execucao": 1.1,
      "max_tempo_execucao": 2.2
    }
  ]
}
```

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `media_tempo` | float | Média de tempo de execução (horas) |
| `quantidade_execucoes` | integer | Número de vezes que o serviço foi executado |
| `total_tempo_executando` | float | Soma total do tempo de execução |
| `min_tempo_execucao` | float | Menor tempo registrado |
| `max_tempo_execucao` | float | Maior tempo registrado |

---

## Consulta Pública

### `GET /consulta/ordem-servico` 🔓

Rota pública. Retorna a ordem de serviço mais recente vinculada ao cliente e ao veículo informados.

**Query Parameters**

| Parâmetro | Tipo | Obrigatório | Exemplo | Descrição |
|-----------|------|-------------|---------|-----------|
| `cpf_cnpj` | string | Sim | `123.456.789-09` | CPF (11 dígitos) ou CNPJ (14 dígitos), com ou sem formatação |
| `placa` | string | Sim | `ABC-1234` | Placa no formato antigo (`AAA-1234`) ou Mercosul (`AAA1A23`) |

**Respostas**

| Status | Descrição |
|--------|-----------|
| `200`  | Ordem de serviço encontrada |
| `400`  | CPF/CNPJ ou placa com formato inválido |
| `404`  | Cliente, veículo ou ordem não encontrados |

**Resposta `200`** — retorna o objeto completo `OrdemServicoCompletaResponse` (mesmo formato de `GET /ordens-servico/{id}`).

---

## Modelos de Erro

### Erro de Validação (`400`)

```json
{
  "errors": [
    {
      "field": "cpf_cnpj",
      "message": "CPF/CNPJ inválido"
    }
  ]
}
```

### Erro de Conflito (`409`)

```json
{
  "errors": [
    {
      "message": "Cliente com este CPF/CNPJ já existe"
    }
  ]
}
```
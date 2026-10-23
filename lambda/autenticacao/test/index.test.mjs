import assert from 'node:assert/strict';
import { afterEach, beforeEach, test } from 'node:test';
import { criarHandler } from '../src/index.mjs';

const SECRET = 'segredo-de-teste-com-pelo-menos-32-bytes';
const BANCO = { host: 'db', port: 3306, database: 'app_db', user: 'autenticacao', password: 'x' };
const CPF_CADASTRADO = '52998224725';
const CNPJ_CADASTRADO = '12ABC34501DE35';

let consultas;

const existeClienteFake = async (cpfCnpj, banco) => {
    consultas.push({ cpfCnpj, banco });
    return [CPF_CADASTRADO, CNPJ_CADASTRADO].includes(cpfCnpj);
};

const handler = criarHandler({ existeCliente: existeClienteFake, lerBanco: () => BANCO });

beforeEach(() => {
    process.env.JWT_SECRET = SECRET;
    process.env.JWT_ISSUER = 'tech-challenge-api';
    process.env.JWT_TTL = '120';
    consultas = [];
});

afterEach(() => {
    delete process.env.JWT_SECRET;
    delete process.env.JWT_ISSUER;
    delete process.env.JWT_TTL;
});

const invocar = async (body, extra = {}) => {
    const res = await handler({ body, ...extra });

    return { statusCode: res.statusCode, body: JSON.parse(res.body) };
};

const payloadDo = (token) => JSON.parse(Buffer.from(token.split('.')[1], 'base64url').toString('utf8'));

test('retorna token Bearer para CPF válido', async () => {
    const res = await invocar(JSON.stringify({ cpf: '529.982.247-25' }));

    assert.equal(res.statusCode, 200);
    assert.equal(res.body.token_type, 'Bearer');
    assert.equal(res.body.expires_in, 120);

    const payload = payloadDo(res.body.access_token);
    assert.equal(payload.sub, '52998224725');
    assert.equal(payload.role, 'cliente');
    assert.equal(payload.iss, 'tech-challenge-api');
    assert.equal(payload.exp - payload.iat, 120);
});

test('retorna 401 para CPF inválido', async () => {
    const res = await invocar(JSON.stringify({ cpf: '52998224724' }));

    assert.equal(res.statusCode, 401);
    assert.equal(res.body.access_token, undefined);
});

test('aceita body em base64', async () => {
    const body = Buffer.from(JSON.stringify({ cpf: '52998224725' })).toString('base64');
    const res = await invocar(body, { isBase64Encoded: true });

    assert.equal(res.statusCode, 200);
});

test('retorna 400 sem body', async () => {
    const res = await handler({});

    assert.equal(res.statusCode, 400);
});

test('retorna 400 com JSON inválido', async () => {
    const res = await invocar('{cpf:');

    assert.equal(res.statusCode, 400);
});

test('retorna 400 quando cpf não é string', async () => {
    const res = await invocar(JSON.stringify({ cpf: 52998224725 }));

    assert.equal(res.statusCode, 400);
});

test('retorna 500 sem JWT_SECRET configurado', async () => {
    delete process.env.JWT_SECRET;

    const res = await invocar(JSON.stringify({ cpf: '52998224725' }));

    assert.equal(res.statusCode, 500);
});

test('consulta o cliente pelo CPF só com dígitos', async () => {
    await invocar(JSON.stringify({ cpf: '529.982.247-25' }));

    assert.deepEqual(consultas, [{ cpfCnpj: CPF_CADASTRADO, banco: BANCO }]);
});

test('não consulta o banco para CPF inválido', async () => {
    await invocar(JSON.stringify({ cpf: '52998224724' }));

    assert.equal(consultas.length, 0);
});

test('retorna 404 para CPF válido sem cliente cadastrado', async () => {
    const res = await invocar(JSON.stringify({ cpf: '111.444.777-35' }));

    assert.equal(res.statusCode, 404);
    assert.equal(res.body.access_token, undefined);
});

test('retorna 503 quando a consulta ao banco falha', async () => {
    const falha = criarHandler({
        existeCliente: async () => {
            throw Object.assign(new Error('connect ETIMEDOUT'), { code: 'ETIMEDOUT' });
        },
        lerBanco: () => BANCO,
    });

    const res = await falha({ body: JSON.stringify({ cpf: CPF_CADASTRADO }) });

    assert.equal(res.statusCode, 503);
    assert.doesNotMatch(res.body, /ETIMEDOUT/);
});

test('retorna 500 sem variáveis DB_* configuradas', async () => {
    const semBanco = criarHandler({ existeCliente: existeClienteFake, lerBanco: () => null });

    const res = await semBanco({ body: JSON.stringify({ cpf: CPF_CADASTRADO }) });

    assert.equal(res.statusCode, 500);
    assert.equal(consultas.length, 0);
});

test('aceita o campo cpf_cnpj com CPF', async () => {
    const res = await invocar(JSON.stringify({ cpf_cnpj: '529.982.247-25' }));

    assert.equal(res.statusCode, 200);
    assert.equal(payloadDo(res.body.access_token).sub, CPF_CADASTRADO);
});

test('retorna token para CNPJ alfanumérico cadastrado, normalizado como no PHP', async () => {
    const res = await invocar(JSON.stringify({ cpf_cnpj: '12.abc.345/01de-35' }));

    assert.equal(res.statusCode, 200);
    assert.equal(payloadDo(res.body.access_token).sub, CNPJ_CADASTRADO);
    assert.deepEqual(consultas, [{ cpfCnpj: CNPJ_CADASTRADO, banco: BANCO }]);
});

test('retorna 404 para CNPJ válido sem cliente cadastrado', async () => {
    const res = await invocar(JSON.stringify({ cpf_cnpj: '11.222.333/0001-81' }));

    assert.equal(res.statusCode, 404);
});

test('retorna 401 para CNPJ inválido sem consultar o banco', async () => {
    const res = await invocar(JSON.stringify({ cpf_cnpj: '11.222.333/0001-82' }));

    assert.equal(res.statusCode, 401);
    assert.equal(consultas.length, 0);
});

test('prioriza cpf_cnpj quando cpf também é enviado', async () => {
    await invocar(JSON.stringify({ cpf_cnpj: '11.222.333/0001-81', cpf: '529.982.247-25' }));

    assert.equal(consultas[0].cpfCnpj, '11222333000181');
});

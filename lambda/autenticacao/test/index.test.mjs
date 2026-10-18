import assert from 'node:assert/strict';
import { afterEach, beforeEach, test } from 'node:test';
import { handler } from '../src/index.mjs';

const SECRET = 'segredo-de-teste-com-pelo-menos-32-bytes';

beforeEach(() => {
    process.env.JWT_SECRET = SECRET;
    process.env.JWT_ISSUER = 'tech-challenge-api';
    process.env.JWT_TTL = '120';
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

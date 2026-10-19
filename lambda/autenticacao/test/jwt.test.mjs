import assert from 'node:assert/strict';
import { createHmac } from 'node:crypto';
import { test } from 'node:test';
import { gerarJwt } from '../src/jwt.mjs';

const SECRET = 'segredo-de-teste-com-pelo-menos-32-bytes';

const decodificar = (parte) => JSON.parse(Buffer.from(parte, 'base64url').toString('utf8'));

test('gera JWT HS256 com iss, iat, exp e claims informadas', () => {
    const token = gerarJwt({ sub: '52998224725', role: 'cliente' }, { secret: SECRET, issuer: 'emissor', ttl: 60, agora: 1000 });
    const [header, payload] = token.split('.');

    assert.deepEqual(decodificar(header), { alg: 'HS256', typ: 'JWT' });
    assert.deepEqual(decodificar(payload), { iss: 'emissor', iat: 1000, exp: 1060, sub: '52998224725', role: 'cliente' });
});

test('assina com HMAC-SHA256 do segredo', () => {
    const token = gerarJwt({}, { secret: SECRET, issuer: 'emissor', ttl: 60 });
    const [header, payload, signature] = token.split('.');
    const esperada = createHmac('sha256', SECRET).update(`${header}.${payload}`).digest('base64url');

    assert.equal(signature, esperada);
});

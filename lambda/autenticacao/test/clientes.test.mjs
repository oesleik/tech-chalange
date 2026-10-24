import assert from 'node:assert/strict';
import { test } from 'node:test';
import { lerConfigBanco } from '../src/clientes.mjs';

const ENV = { DB_HOST: 'rds.local', DB_DATABASE: 'app_db', DB_USERNAME: 'autenticacao', DB_PASSWORD: 'x' };

test('monta a configuração com TLS do RDS e porta padrão', () => {
    assert.deepEqual(lerConfigBanco(ENV), {
        host: 'rds.local',
        port: 3306,
        database: 'app_db',
        user: 'autenticacao',
        password: 'x',
        ssl: 'Amazon RDS',
    });
});

test('respeita DB_PORT e DB_SSL=false', () => {
    const config = lerConfigBanco({ ...ENV, DB_PORT: '3307', DB_SSL: 'false' });

    assert.equal(config.port, 3307);
    assert.equal(config.ssl, undefined);
});

test('retorna null quando falta alguma variável obrigatória', () => {
    for (const chave of Object.keys(ENV)) {
        assert.equal(lerConfigBanco({ ...ENV, [chave]: '' }), null, chave);
    }
});

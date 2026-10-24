import assert from 'node:assert/strict';
import { test } from 'node:test';
import { ehCnpjValido, limparCnpj } from '../src/cnpj.mjs';

test('aceita CNPJ numérico válido, com ou sem formatação', () => {
    assert.equal(ehCnpjValido('11222333000181'), true);
    assert.equal(ehCnpjValido('11.222.333/0001-81'), true);
});

test('aceita CNPJ alfanumérico válido, em maiúsculas ou minúsculas', () => {
    assert.equal(ehCnpjValido('12.ABC.345/01DE-35'), true);
    assert.equal(ehCnpjValido('12abc34501de35'), true);
});

test('rejeita CNPJ com dígito verificador errado', () => {
    assert.equal(ehCnpjValido('11222333000182'), false);
});

test('rejeita CNPJ com tamanho diferente de 14 caracteres', () => {
    assert.equal(ehCnpjValido('1122233300018'), false);
    assert.equal(ehCnpjValido('112223330001811'), false);
});

test('rejeita dígitos verificadores não numéricos e caracteres especiais', () => {
    assert.equal(ehCnpjValido('12ABC34501DEAB'), false);
    assert.equal(ehCnpjValido('12 ABC 345 01DE 35'), false);
});

test('rejeita sequências repetidas', () => {
    assert.equal(ehCnpjValido('00000000000000'), false);
    assert.equal(ehCnpjValido('11111111111111'), false);
});

test('limpa separadores e converte para maiúsculas', () => {
    assert.equal(limparCnpj('12.abc.345/01de-35'), '12ABC34501DE35');
});

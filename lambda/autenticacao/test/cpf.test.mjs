import assert from 'node:assert/strict';
import { test } from 'node:test';
import { ehCpfValido, limparCpf } from '../src/cpf.mjs';

test('aceita CPF válido sem formatação', () => {
    assert.equal(ehCpfValido('52998224725'), true);
});

test('aceita CPF válido formatado', () => {
    assert.equal(ehCpfValido('529.982.247-25'), true);
});

test('aceita CPF cujo dígito verificador é zero', () => {
    assert.equal(ehCpfValido('10000000108'), true);
});

test('rejeita CPF com dígito verificador errado', () => {
    assert.equal(ehCpfValido('52998224724'), false);
});

test('rejeita CPF com tamanho diferente de 11 dígitos', () => {
    assert.equal(ehCpfValido('5299822472'), false);
    assert.equal(ehCpfValido('529982247250'), false);
});

test('rejeita CPF com caracteres não numéricos', () => {
    assert.equal(ehCpfValido('a0000000000'), false);
    assert.equal(ehCpfValido('529 982 247 25'), false);
});

test('rejeita sequências de dígitos repetidos', () => {
    assert.equal(ehCpfValido('11111111111'), false);
    assert.equal(ehCpfValido('000.000.000-00'), false);
});

test('limpa pontos e traço', () => {
    assert.equal(limparCpf('529.982.247-25'), '52998224725');
});

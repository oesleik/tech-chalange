import { ehCpfValido, limparCpf } from './cpf.mjs';
import { gerarJwt } from './jwt.mjs';

const ROLE = 'cliente';

// Invocada pelo API Gateway HTTP API (payload format 2.0): POST /autenticacao {"cpf": "..."}
export const handler = async (event) => {
    const config = lerConfig();

    if (!config) {
        console.error('JWT_SECRET não configurado.');
        return resposta(500, { error: 'Internal Server Error', message: 'Autenticação indisponível.' });
    }

    const body = lerBody(event);

    if (typeof body?.cpf !== 'string') {
        return resposta(400, { error: 'Bad Request', message: 'Informe o campo "cpf" (string) no corpo JSON.' });
    }

    if (!ehCpfValido(body.cpf)) {
        return resposta(401, { error: 'Unauthorized', message: 'CPF inválido.' });
    }

    const token = gerarJwt({ sub: limparCpf(body.cpf), role: ROLE }, config);

    return resposta(200, { access_token: token, token_type: 'Bearer', expires_in: config.ttl });
};

function lerConfig() {
    const secret = process.env.JWT_SECRET;

    if (!secret) {
        return null;
    }

    return {
        secret,
        issuer: process.env.JWT_ISSUER || 'tech-challenge-api',
        ttl: Number.parseInt(process.env.JWT_TTL ?? '', 10) || 3600,
    };
}

function lerBody(event) {
    if (!event?.body) {
        return null;
    }

    const raw = event.isBase64Encoded ? Buffer.from(event.body, 'base64').toString('utf8') : event.body;

    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function resposta(statusCode, payload) {
    return {
        statusCode,
        headers: { 'content-type': 'application/json' },
        body: JSON.stringify(payload),
    };
}

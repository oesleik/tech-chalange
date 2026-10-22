import { existeClientePorCpf, lerConfigBanco } from './clientes.mjs';
import { ehCpfValido, limparCpf } from './cpf.mjs';
import { gerarJwt } from './jwt.mjs';

const ROLE = 'cliente';

// Dependências injetáveis para os testes não precisarem de um MySQL.
export function criarHandler({ existeCliente = existeClientePorCpf, lerBanco = lerConfigBanco } = {}) {
    // Invocada pelo API Gateway HTTP API (payload format 2.0): POST /autenticacao {"cpf": "..."}
    return async (event) => {
        const config = lerConfig();
        const banco = lerBanco();

        if (!config || !banco) {
            console.error(config ? 'Variáveis DB_* não configuradas.' : 'JWT_SECRET não configurado.');
            return resposta(500, { error: 'Internal Server Error', message: 'Autenticação indisponível.' });
        }

        const body = lerBody(event);

        if (typeof body?.cpf !== 'string') {
            return resposta(400, { error: 'Bad Request', message: 'Informe o campo "cpf" (string) no corpo JSON.' });
        }

        if (!ehCpfValido(body.cpf)) {
            return resposta(401, { error: 'Unauthorized', message: 'CPF inválido.' });
        }

        const cpf = limparCpf(body.cpf);
        let existe;

        try {
            existe = await existeCliente(cpf, banco);
        } catch (error) {
            console.error('Falha ao consultar o cliente no banco:', error.code ?? error.message);
            return resposta(503, { error: 'Service Unavailable', message: 'Autenticação indisponível.' });
        }

        if (!existe) {
            return resposta(404, { error: 'Not Found', message: 'Cliente não encontrado.' });
        }

        const token = gerarJwt({ sub: cpf, role: ROLE }, config);

        return resposta(200, { access_token: token, token_type: 'Bearer', expires_in: config.ttl });
    };
}

export const handler = criarHandler();

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

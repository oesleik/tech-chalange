import mysql from 'mysql2/promise';

// Leitura direta da tabela `clientes` do RDS (mesmo schema das migrations do PHP).
// O pool fica fora do handler para reaproveitar a conexão entre invocações do mesmo
// ambiente de execução; com limite 1, uma conexão quebrada é descartada e recriada.

let pool;

export async function existeClientePorCpf(cpf, config) {
    const [linhas] = await obterPool(config).execute('SELECT 1 FROM clientes WHERE cpf_cnpj = ? LIMIT 1', [cpf]);

    return linhas.length > 0;
}

export function lerConfigBanco(env = process.env) {
    const { DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD } = env;

    if (!DB_HOST || !DB_DATABASE || !DB_USERNAME || !DB_PASSWORD) {
        return null;
    }

    return {
        host: DB_HOST,
        port: Number.parseInt(env.DB_PORT ?? '', 10) || 3306,
        database: DB_DATABASE,
        user: DB_USERNAME,
        password: DB_PASSWORD,
        // DB_SSL=false só para MySQL local sem TLS; no RDS valida o certificado com o bundle da AWS.
        ssl: env.DB_SSL === 'false' ? undefined : 'Amazon RDS',
    };
}

function obterPool(config) {
    pool ??= mysql.createPool({
        ...config,
        connectionLimit: 1,
        maxIdle: 1,
        connectTimeout: 3000,
        enableKeepAlive: true,
    });

    return pool;
}

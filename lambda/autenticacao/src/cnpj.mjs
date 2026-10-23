// Mesmo algoritmo de src/Clientes/Domain/ValueObject/Cnpj.php (inclui o CNPJ alfanumérico),
// mas mais estrito: base só com [0-9A-Z], DVs numéricos e sem sequências repetidas.

export function limparCnpj(cnpj) {
    return cnpj.replace(/[./-]/g, '').toUpperCase();
}

export function ehCnpjValido(cnpj) {
    const limpo = limparCnpj(cnpj);

    if (!/^[0-9A-Z]{12}\d{2}$/.test(limpo) || /^(.)\1{13}$/.test(limpo)) {
        return false;
    }

    const base = limpo.slice(0, 12);
    const [dv1, dv2] = descobrirDv(base);

    return limpo === `${base}${dv1}${dv2}`;
}

const PESO_DV1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
const PESO_DV2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3];

function descobrirDv(base) {
    let sumDv1 = 0;
    let sumDv2 = 0;

    [...base].forEach((char, index) => {
        const digit = char.charCodeAt(0) - 48;
        sumDv1 += digit * PESO_DV1[index];
        sumDv2 += digit * PESO_DV2[index];
    });

    const dv1 = calcularDv(sumDv1);

    sumDv2 += dv1 * 2;
    const dv2 = calcularDv(sumDv2);

    return [dv1, dv2];
}

function calcularDv(sumDv) {
    const remainder = sumDv % 11;

    return remainder < 2 ? 0 : 11 - remainder;
}

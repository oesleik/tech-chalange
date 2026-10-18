// Mesmo algoritmo de src/Clientes/Domain/ValueObject/Cpf.php, mas mais estrito:
// aceita só dígitos (após remover "." e "-") e rejeita sequências repetidas (111.111.111-11).

export function limparCpf(cpf) {
    return cpf.replace(/[.-]/g, '');
}

export function ehCpfValido(cpf) {
    const limpo = limparCpf(cpf);

    if (!/^\d{11}$/.test(limpo) || /^(\d)\1{10}$/.test(limpo)) {
        return false;
    }

    const base = limpo.slice(0, 9);
    const [dv1, dv2] = descobrirDv(base);

    return limpo === `${base}${dv1}${dv2}`;
}

function descobrirDv(base) {
    let sumDv1 = 0;
    let sumDv2 = 0;

    [...base].forEach((char, index) => {
        const digit = Number(char);
        sumDv1 += digit * (10 - index);
        sumDv2 += digit * (11 - index);
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

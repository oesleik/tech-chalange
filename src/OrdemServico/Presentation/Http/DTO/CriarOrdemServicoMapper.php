<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoInputDTO;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CriarOrdemServicoInputDTO',
    required: ['id_cliente', 'id_veiculo'],
    properties: [
        new OA\Property(property: 'id_cliente', type: 'integer', example: 10),
        new OA\Property(property: 'id_veiculo', type: 'integer', example: 20),
        new OA\Property(
            property: 'pecas',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id_peca', type: 'integer', example: 3),
                    new OA\Property(property: 'quantidade', type: 'integer', example: 2),
                ],
                required: ['id_peca', 'quantidade'],
            ),
        ),
        new OA\Property(
            property: 'servicos',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id_servico', type: 'integer', example: 4),
                    new OA\Property(property: 'quantidade', type: 'integer', example: 1),
                ],
                required: ['id_servico', 'quantidade'],
            ),
        ),
    ],
)]
final class CriarOrdemServicoMapper {
    public static function parse(array $data): CriarOrdemServicoInputDTO {
        if (empty($data['id_cliente']) || !is_int($data['id_cliente']) || $data['id_cliente'] <= 0) {
            throw new InvalidArgumentException('O campo id_cliente deve ser um inteiro positivo.');
        }

        if (empty($data['id_veiculo']) || !is_int($data['id_veiculo']) || $data['id_veiculo'] <= 0) {
            throw new InvalidArgumentException('O campo id_veiculo deve ser um inteiro positivo.');
        }

        return new CriarOrdemServicoInputDTO(
            idCliente: $data['id_cliente'],
            idVeiculo: $data['id_veiculo'],
            pecas: self::parseItens($data['pecas'] ?? null, 'id_peca', 'pecas'),
            servicos: self::parseItens($data['servicos'] ?? null, 'id_servico', 'servicos'),
        );
    }

    /** @return array<array{id_peca?: int, id_servico?: int, quantidade: int}> */
    private static function parseItens(mixed $itens, string $idCampo, string $listaCampo): array {
        if ($itens === null) {
            return [];
        }

        if (!is_array($itens)) {
            throw new InvalidArgumentException("O campo '{$listaCampo}' deve ser um array.");
        }

        foreach ($itens as $item) {
            if (!is_array($item) || empty($item[$idCampo]) || !is_int($item[$idCampo]) || $item[$idCampo] <= 0) {
                throw new InvalidArgumentException("Cada item de '{$listaCampo}' deve ter '{$idCampo}' positivo.");
            }

            if (empty($item['quantidade']) || !is_int($item['quantidade']) || $item['quantidade'] <= 0) {
                throw new InvalidArgumentException("Cada item de '{$listaCampo}' deve ter 'quantidade' positiva.");
            }
        }

        return $itens;
    }
}

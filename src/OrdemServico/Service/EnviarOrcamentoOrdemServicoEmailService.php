<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\Clientes\Model\ClienteModel;
use App\Core\Auth\OrdemServico\JwtOrdemServicoService;
use App\Core\Config\AppConfig;
use App\Core\Email\EmailService;
use App\OrdemServico\Model\OrdemServicoModel;

class EnviarOrcamentoOrdemServicoEmailService {
    public function __construct(
        private ItensOrdemServicoService $itensOrdemServicoService,
        private JwtOrdemServicoService $jwtOrdemServicoService,
        private EmailService $emailService,
        private AppConfig $appConfig,
    ) {}

    public function enviar(OrdemServicoModel $ordemServico, ClienteModel $cliente): void {
        $idOrdemServico = $ordemServico->getId();
        $pecas    = $this->itensOrdemServicoService->obterPecasPorIdOrdemServico($idOrdemServico);
        $servicos = $this->itensOrdemServicoService->obterServicosPorIdOrdemServico($idOrdemServico);

        $token = $this->jwtOrdemServicoService->generate([
            'id_ordem_servico' => $idOrdemServico,
        ]);

        $baseUrl      = rtrim($this->appConfig->getBaseUrl(), '/');
        $urlAprovada  = "{$baseUrl}/email/ordens-servico/aprovada?token={$token}";
        $urlRejeitada = "{$baseUrl}/email/ordens-servico/rejeitada?token={$token}";

        $valorTotal = $ordemServico->getValorTotal();
        $valorFormatado = 'R$ ' . number_format($valorTotal, 2, ',', '.');

        $html = $this->montarHtmlEmail(
            nomeCliente: $cliente->getNome(),
            idOrdemServico: $idOrdemServico,
            pecas: $pecas,
            servicos: $servicos,
            valorTotal: $valorFormatado,
            urlAprovada: $urlAprovada,
            urlRejeitada: $urlRejeitada,
        );

        $this->emailService->send(
            to: [['email' => $cliente->getEmail()->getValue(), 'name' => $cliente->getNome()]],
            subject: "Orçamento da Ordem de Serviço #{$idOrdemServico}",
            body: $html,
            isHtml: true,
            altBody: "Acesse o link para aprovar ou rejeitar o orçamento da OS #{$idOrdemServico}.",
        );
    }

    private function formatarMoeda(float $valor): string {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * @param \App\OrdemServico\Model\PecaOrdemServicoModel[]    $pecas
     * @param \App\OrdemServico\Model\ServicoOrdemServicoModel[] $servicos
     */
    private function montarHtmlEmail(
        string $nomeCliente,
        int $idOrdemServico,
        array $pecas,
        array $servicos,
        string $valorTotal,
        string $urlAprovada,
        string $urlRejeitada,
    ): string {
        $linhasPecas = '';
        foreach ($pecas as $peca) {
            $valorUnitarioFormatado = $this->formatarMoeda($peca->getValorUnitario());
            $subtotalFormatado = $this->formatarMoeda($peca->getSubtotal());

            $linhasPecas .= sprintf(
                '<tr>
                    <td style="padding:8px;border-bottom:1px solid #eee;">Peça #%d</td>
                    <td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">%d</td>
                    <td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">%s</td>
                    <td style="padding:8px;border-bottom:1px solid #eee;text-align:right;font-weight:bold;">%s</td>
                </tr>',
                $peca->getIdPeca(),
                $peca->getQuantidade(),
                $valorUnitarioFormatado,
                $subtotalFormatado,
            );
        }

        $linhasServicos = '';
        foreach ($servicos as $servico) {
            $valorUnitarioFormatado = $this->formatarMoeda($servico->getValorUnitario());
            $subtotalFormatado = $this->formatarMoeda($servico->getSubtotal());

            $linhasServicos .= sprintf(
                '<tr>
                    <td style="padding:8px;border-bottom:1px solid #eee;">Serviço #%d</td>
                    <td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">%d</td>
                    <td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">%s</td>
                    <td style="padding:8px;border-bottom:1px solid #eee;text-align:right;font-weight:bold;">%s</td>
                </tr>',
                $servico->getIdServico(),
                $servico->getQuantidade(),
                $valorUnitarioFormatado,
                $subtotalFormatado,
            );
        }

        $cabecalhoTabela = "<thead>
            <tr style='background:#f5f5f5;'>
                <th style='padding:8px;text-align:left;'>Descrição</th>
                <th style='padding:8px;text-align:center;'>Qtd</th>
                <th style='padding:8px;text-align:right;'>Valor Unit.</th>
                <th style='padding:8px;text-align:right;'>Subtotal</th>
            </tr>
        </thead>";

        $tabelaPecas = $linhasPecas !== ''
            ? "<h3 style='color:#444;'>Peças</h3>
               <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse;'>
                   {$cabecalhoTabela}
                   <tbody>{$linhasPecas}</tbody>
               </table>"
            : '';

        $tabelaServicos = $linhasServicos !== ''
            ? "<h3 style='color:#444;margin-top:20px;'>Serviços</h3>
               <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse;'>
                   {$cabecalhoTabela}
                   <tbody>{$linhasServicos}</tbody>
               </table>"
            : '';

        return <<<HTML
            <!DOCTYPE html>
            <html lang="pt-BR">
            <head><meta charset="UTF-8"><title>Orçamento OS #{$idOrdemServico}</title></head>
            <body style="font-family:Arial,sans-serif;background:#f9f9f9;margin:0;padding:0;">
                <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9;padding:30px 0;">
                    <tr>
                        <td align="center">
                            <table width="600" cellpadding="0" cellspacing="0"
                                   style="background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);overflow:hidden;">

                                <!-- Cabeçalho -->
                                <tr>
                                    <td style="background:#1a73e8;padding:24px 32px;">
                                        <h1 style="color:#fff;margin:0;font-size:20px;">
                                            Orçamento — Ordem de Serviço #{$idOrdemServico}
                                        </h1>
                                    </td>
                                </tr>

                                <!-- Corpo -->
                                <tr>
                                    <td style="padding:32px;">
                                        <p style="color:#333;font-size:15px;">Olá, <strong>{$nomeCliente}</strong>!</p>
                                        <p style="color:#555;font-size:14px;line-height:1.6;">
                                            Segue abaixo o orçamento da sua Ordem de Serviço.
                                            Por favor, revise os itens e utilize os botões ao final para <strong>aprovar</strong>
                                            ou <strong>rejeitar</strong> o orçamento.
                                        </p>

                                        {$tabelaPecas}
                                        {$tabelaServicos}

                                        <!-- Valor Total -->
                                        <table width="100%" cellpadding="0" cellspacing="0"
                                               style="margin-top:24px;background:#f0f7ff;border-radius:6px;">
                                            <tr>
                                                <td style="padding:16px 20px;">
                                                    <span style="color:#555;font-size:14px;">Valor Total do Orçamento</span>
                                                    <br>
                                                    <strong style="color:#1a73e8;font-size:22px;">{$valorTotal}</strong>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Botões de ação -->
                                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:32px;">
                                            <tr>
                                                <td align="center" style="padding:0 8px;">
                                                    <a href="{$urlAprovada}"
                                                       style="display:inline-block;padding:14px 32px;background:#34a853;
                                                              color:#fff;text-decoration:none;border-radius:6px;
                                                              font-size:15px;font-weight:bold;">
                                                        ✓ Aprovar Orçamento
                                                    </a>
                                                </td>
                                                <td align="center" style="padding:0 8px;">
                                                    <a href="{$urlRejeitada}"
                                                       style="display:inline-block;padding:14px 32px;background:#ea4335;
                                                              color:#fff;text-decoration:none;border-radius:6px;
                                                              font-size:15px;font-weight:bold;">
                                                        ✗ Rejeitar Orçamento
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="color:#999;font-size:12px;margin-top:24px;text-align:center;">
                                            Este link é válido por tempo limitado e é de uso exclusivo desta Ordem de Serviço.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Rodapé -->
                                <tr>
                                    <td style="background:#f5f5f5;padding:16px 32px;text-align:center;">
                                        <p style="color:#aaa;font-size:12px;margin:0;">
                                            Tech Challenge — Sistema de Gestão de Ordens de Serviço
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            HTML;
    }
}

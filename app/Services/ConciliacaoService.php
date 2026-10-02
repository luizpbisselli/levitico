<?php

namespace App\Services;

use App\Models\Cte;
use App\Models\Entrega;
use App\Models\Nfe;
use App\Models\Veiculo;
use Illuminate\Support\Facades\DB;

/**
 * Conciliação NF-e <-> CT-e <-> veículo (README seção 7).
 * Idempotência garantida pela chave de acesso única.
 */
class ConciliacaoService
{
    /**
     * Vincula as NF-e ao CT-e pelas chaves em infDoc e cria/atualiza a entrega.
     * Retorna pendências para a tela do admin (falhas nunca ficam silenciosas).
     *
     * @return string[]
     */
    public function conciliarCte(Cte $cte): array
    {
        $pendencias = [];

        DB::transaction(function () use ($cte, &$pendencias) {
            // 1) Vincula NF-e já recebidas pelas chaves referenciadas em infDoc
            $chavesNfe = $this->extrairChavesNfeDoXml((string) $cte->xml_original);
            if ($chavesNfe !== []) {
                $ids = Nfe::whereIn('chave_acesso', $chavesNfe)->pluck('id');
                $cte->nfes()->syncWithoutDetaching($ids);

                $faltando = count($chavesNfe) - $ids->count();
                if ($faltando > 0) {
                    $pendencias[] = "CT-e {$cte->numero}: {$faltando} NF-e referenciadas ainda não recebidas por e-mail.";
                }
            }

            // 2) Resolve o veículo pela placa informada no XML
            $veiculo = null;
            if ($cte->placa_informada) {
                $veiculo = Veiculo::where('placa', strtoupper($cte->placa_informada))->first();
                if (! $veiculo) {
                    $pendencias[] = "CT-e {$cte->numero}: placa {$cte->placa_informada} não cadastrada em veículos.";
                }
            } else {
                $pendencias[] = "CT-e {$cte->numero}: XML sem placa; atribua o veículo manualmente (o sistema sugere pelo histórico).";
            }

            // 3) Cria/atualiza a entrega (idempotente por cte_id)
            $entrega = Entrega::firstOrNew(['cte_id' => $cte->id]);
            if (! $entrega->veiculo_id) {
                $entrega->veiculo_id = $veiculo?->id ?? $this->sugerirVeiculoPorHistorico($cte);
            }
            $cte->load('nfes');
            $primeiraNfe = $cte->nfes->first();
            $entrega->endereco_entrega ??= $primeiraNfe?->destinatario_endereco;
            $entrega->cidade_entrega   ??= $primeiraNfe?->destinatario_cidade ?? $cte->destinatario_cidade;
            $entrega->uf_entrega       ??= $primeiraNfe?->destinatario_uf ?? $cte->destinatario_uf;
            $entrega->save();

            // 4) Vincula cliente conhecido pelo documento do destinatário da NF-e
            if ($primeiraNfe && $primeiraNfe->destinatario_documento) {
                $clienteId = \App\Models\Cliente::where('documento', $primeiraNfe->destinatario_documento)->value('id');
                if ($clienteId) {
                    $entrega->update(['cliente_id' => $clienteId]);
                    $primeiraNfe->update(['cliente_id' => $clienteId]);
                }
            }
        });

        return $pendencias;
    }

    /**
     * Extrai as chaves de acesso das NF-e referenciadas em infDoc/infNFe do XML do CT-e.
     *
     * @return string[]
     */
    public function extrairChavesNfeDoXml(string $xml): array
    {
        if (trim($xml) === '') {
            return [];
        }

        $doc = @simplexml_load_string($xml);
        if ($doc === false) {
            return [];
        }

        $ns    = $doc->getNamespaces(true);
        $cteNs = $ns[''] ?? 'http://www.portalfiscal.inf.br/cte';
        $infCte = $doc->children($cteNs)->CTe->infCte ?? null;
        if (! $infCte) {
            return [];
        }

        $chaves = [];
        foreach ($infCte->children($cteNs)->infDoc->infNFe ?? [] as $infNFe) {
            $chave = preg_replace('/\D/', '', (string) $infNFe->attributes()->Chave);
            if (preg_match('/^\d{44}$/', $chave)) {
                $chaves[] = $chave;
            }
        }

        return array_unique($chaves);
    }

    /**
     * Sugere veículo com base no histórico: veículo mais recente que já transportou
     * alguma das NF-e deste CT-e (README seção 7).
     */
    private function sugerirVeiculoPorHistorico(Cte $cte): ?int
    {
        $nfeIds = $cte->nfes()->pluck('nfes.id');
        if ($nfeIds->isEmpty()) {
            return null;
        }

        $outraEntrega = Entrega::where('cte_id', '!=', $cte->id)
            ->whereNotNull('veiculo_id')
            ->whereHas('cte.nfes', fn ($q) => $q->whereIn('nfes.id', $nfeIds))
            ->latest()
            ->first();

        return $outraEntrega?->veiculo_id;
    }
}

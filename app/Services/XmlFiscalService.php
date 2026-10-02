<?php

namespace App\Services;

use App\Models\Cte;
use App\Models\Nfe;

/**
 * Identifica o tipo do XML pela raiz (nfeProc, cteProc, eventos) e faz o parse
 * com SimpleXML, salvando dados estruturados + XML bruto (guarda fiscal).
 */
class XmlFiscalService
{
    public function identificarTipo(string $xml): ?string
    {
        $doc = @simplexml_load_string($xml);
        if (! $doc) {
            return null;
        }

        return match (true) {
            str_contains(strtolower($doc->getName()), 'nfeproc')       => 'nfe',
            str_contains(strtolower($doc->getName()), 'cteproc')       => 'cte',
            str_contains(strtolower($doc->getName()), 'procinutl')     => 'evento',
            default => null,
        };
    }

    /**
     * Processa um XML de NF-e. Idempotente pela chave de acesso.
     */
    public function processarNfe(string $xml): array
    {
        $doc = simplexml_load_string($xml);
        $ns  = $doc->getNamespaces(true);
        $infNs = $ns[''] ?? 'http://www.portalfiscal.inf.br/nfe';
        $ide = $doc->children($infNs)->NFe->infNFe ?? null;
        if (! $ide) {
            return ['ok' => false, 'erro' => 'XML NF-e sem estrutura esperada (infNFe ausente).'];
        }

        $chave = preg_replace('/\D/', '', (string) $ide->attributes()->Id);
        $emit  = $ide->children($infNs)->emit;
        $dest  = $ide->children($infNs)->dest;
        $vTot  = (string) $ide->children($infNs)->total->ICMSTot->vNF;
        $trans = $ide->children($infNs)->transp;

        $dados = [
            'chave_acesso'          => $chave,
            'numero'                => (string) $ide->children($infNs)->ide->nNF,
            'serie'                 => (string) $ide->children($infNs)->ide->serie,
            'emissao'               => !empty((string) $ide->children($infNs)->ide->dhEmi)
                ? substr((string) $ide->children($infNs)->ide->dhEmi, 0, 10) : null,
            'emitente_nome'         => (string) $emit->xNome,
            'emitente_cnpj'         => (string) ($emit->CNPJ ?? $emit->CPF ?? ''),
            'destinatario_nome'     => (string) $dest->xNome,
            'destinatario_documento'=> (string) ($dest->CNPJ ?? $dest->CPF ?? ''),
            'destinatario_telefone' => (string) ($dest->phone ?? ''),
            'destinatario_endereco' => trim(sprintf('%s, %s - %s/%s',
                (string) $dest->endereco->xLgr ?? '', (string) $dest->endereco->nro ?? '',
                (string) $dest->endereco->xMun ?? '', (string) $dest->endereco->UF ?? '')),
            'destinatario_cidade'   => (string) ($dest->endereco->xMun ?? ''),
            'destinatario_uf'       => (string) ($dest->endereco->UF ?? ''),
            'valor_total'           => (float) ($vTot ?: 0),
            'volumes'               => (int) ((string) ($trans->vol->qVol ?? 0)) ?: null,
            'peso_bruto'            => (float) (($trans->vol->pesoB ?? '') ?: '') ?: null,
            'xml_original'          => $xml,
        ];

        $nfe = Nfe::updateOrCreate(['chave_acesso' => $chave], $dados);

        return ['ok' => true, 'tipo' => 'nfe', 'id' => $nfe->id];
    }

    /**
     * Processa um XML de CT-e. Idempotente pela chave de acesso.
     */
    public function processarCte(string $xml): array
    {
        $doc = simplexml_load_string($xml);
        $ns  = $doc->getNamespaces(true);
        $cteNs = $ns[''] ?? 'http://www.portalfiscal.inf.br/cte';
        $infCte = $doc->children($cteNs)->CTe->infCte ?? null;
        if (! $infCte) {
            return ['ok' => false, 'erro' => 'XML CT-e sem estrutura esperada (infCte ausente).'];
        }

        $chave = preg_replace('/\D/', '', (string) $infCte->attributes()->Id);
        $toma  = $infCte->children($cteNs)->toma;
        $rem   = $infCte->children($cteNs)->rem;
        $dest  = $infCte->children($cteNs)->dest;
        $infModal = $infCte->children($cteNs)->infModal->rodo ?? null;

        $placa = null;
        if ($infModal) {
            $veic = $infModal->children($infModal->getNamespaces(true))->veicTran?->veiculo ?? null;
            if ($veic) {
                $placa = strtoupper((string) $veic->placa);
            }
        }

        $dados = [
            'chave_acesso'      => $chave,
            'numero'            => (string) $infCte->children($cteNs)->ide->nCT,
            'serie'             => (string) $infCte->children($cteNs)->ide->serie,
            'emissao'           => !empty((string) $infCte->children($cteNs)->ide->dhEmi)
                ? substr((string) $infCte->children($cteNs)->ide->dhEmi, 0, 10) : null,
            'tomador_nome'      => (string) ($toma->xNome ?? ''),
            'remetente_nome'    => (string) ($rem->xNome ?? ''),
            'destinatario_nome' => (string) ($dest->xNome ?? ''),
            'destinatario_cidade' => (string) ($dest->enderDest->xMun ?? ''),
            'destinatario_uf'   => (string) ($dest->enderDest->UF ?? ''),
            'valor_frete'       => (float) ((string) $infCte->children($cteNs)->vCTe->vPCteTTC ?? 0),
            'placa_informada'   => $placa,
            'xml_original'      => $xml,
        ];

        $cte = Cte::updateOrCreate(['chave_acesso' => $chave], $dados);

        return ['ok' => true, 'tipo' => 'cte', 'id' => $cte->id];
    }

    /**
     * Marca cancelamento a partir de um evento (procInutl / evento de cancelamento).
     */
    public function processarEvento(string $xml): array
    {
        if (preg_match('/descRef\s*>\s*110111/i', $xml) || str_contains($xml, 'Cancelamento')) {
            if (preg_match('/<cChave>(\d{44})<\/cChave>|Chave[=:]\s*"?(\d{44})/i', $xml, $m)) {
                $chave = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
                if ($chave) {
                    Nfe::where('chave_acesso', $chave)->update(['cancelada' => true]);
                    Cte::where('chave_acesso', $chave)->update(['cancelado' => true]);
                    return ['ok' => true, 'tipo' => 'evento_cancelamento', 'chave' => $chave];
                }
            }
        }

        return ['ok' => true, 'tipo' => 'evento_ignorado'];
    }

    /**
     * Ponto de entrada: recebe o conteúdo de um anexo .xml e roteia pelo tipo da raiz.
     */
    public function processarXml(string $xml): array
    {
        return match ($this->identificarTipo($xml)) {
            'nfe'    => $this->processarNfe($xml),
            'cte'    => $this->processarCte($xml),
            'evento' => $this->processarEvento($xml),
            default  => ['ok' => false, 'erro' => 'Raiz do XML não reconhecida (esperado nfeProc, cteProc ou procInutl).'],
        };
    }
}

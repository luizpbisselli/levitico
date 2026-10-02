<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappModelo extends Model
{
    protected $table = 'whatsapp_modelos';

    protected $fillable = ['nome', 'gatilho', 'template', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    /**
     * Monta a mensagem substituindo {cliente} {nfe} {placa} {status} {motorista} {cidade}.
     */
    public function renderizar(Entrega $entrega): string
    {
        $nfeNumeros = $entrega->cte?->nfes->pluck('numero')->filter()->implode(', ');

        return strtr($this->template, [
            '{cliente}'   => $entrega->cliente?->nome ?? $entrega->cte?->destinatario_nome ?? '',
            '{nfe}'       => $nfeNumeros,
            '{placa}'     => strtoupper((string) $entrega->veiculo?->placa),
            '{status}'    => $entrega->statusLabel(),
            '{motorista}' => $entrega->motorista?->nome ?? '',
            '{cidade}'    => $entrega->cidade_entrega ?? '',
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Model;

class Nfe extends Model
{
    protected $fillable = [
        'chave_acesso', 'numero', 'serie', 'emissao', 'emitente_nome', 'emitente_cnpj',
        'destinatario_nome', 'destinatario_documento', 'destinatario_telefone',
        'destinatario_endereco', 'destinatario_cidade', 'destinatario_uf',
        'valor_total', 'volumes', 'peso_bruto', 'xml_original', 'cancelada', 'cliente_id',
    ];

    protected $casts = ['emissao' => 'date', 'cancelada' => 'boolean', 'valor_total' => 'decimal:2'];

    public function ctes()
    {
        return $this->belongsToMany(Cte::class, 'cte_nfe');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}

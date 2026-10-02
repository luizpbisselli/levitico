<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cte extends Model
{
    protected $fillable = [
        'chave_acesso', 'numero', 'serie', 'emissao', 'tomador_nome', 'remetente_nome',
        'destinatario_nome', 'destinatario_cidade', 'destinatario_uf',
        'valor_frete', 'placa_informada', 'xml_original', 'cancelado',
    ];

    protected $casts = ['emissao' => 'date', 'cancelado' => 'boolean', 'valor_frete' => 'decimal:2'];

    public function nfes()
    {
        return $this->belongsToMany(Nfe::class, 'cte_nfe');
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class);
    }
}

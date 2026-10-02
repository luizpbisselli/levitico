<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{
    protected $table = 'entregas';

    public const STATUS = [
        'a_coleter'  => 'A coletar',
        'em_transito'=> 'Em trânsito',
        'entregue'   => 'Entregue',
        'ocorrencia' => 'Ocorrência',
    ];

    protected $fillable = [
        'cte_id', 'veiculo_id', 'motorista_id', 'cliente_id', 'status',
        'endereco_entrega', 'cidade_entrega', 'uf_entrega',
        'observacao_ocorrencia', 'saiu_em', 'entregue_em',
    ];

    protected $casts = ['saiu_em' => 'datetime', 'entregue_em' => 'datetime'];

    public function cte()      { return $this->belongsTo(Cte::class); }
    public function veiculo()  { return $this->belongsTo(Veiculo::class); }
    public function motorista(){ return $this->belongsTo(Motorista::class); }
    public function cliente()  { return $this->belongsTo(Cliente::class); }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}

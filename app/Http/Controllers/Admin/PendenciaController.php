<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailIngestao;
use App\Models\Entrega;

/** Tela de pendências: falhas nunca ficam silenciosas (README seção 7). */
class PendenciaController extends Controller
{
    public function index()
    {
        return view('admin.pendencias.index', [
            'errosEmail'     => EmailIngestao::where('status', 'erro')->latest()->limit(100)->get(),
            'semVeiculo'     => Entrega::whereNull('veiculo_id')->with('cte')->latest()->limit(100)->get(),
        ]);
    }
}

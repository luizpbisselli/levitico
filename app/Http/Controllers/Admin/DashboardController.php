<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cte;
use App\Models\EmailIngestao;
use App\Models\Entrega;
use App\Models\Nfe;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'entregasHoje'     => Entrega::whereDate('created_at', today())->count(),
            'entregesAbertas'  => Entrega::whereIn('status', ['a_coleter', 'em_transito'])->count(),
            'entreguesHoje'    => Entrega::where('status', 'entregue')->whereDate('entregue_em', today())->count(),
            'nfesTotal'        => Nfe::count(),
            'ctesTotal'        => Cte::count(),
            'pendenciasEmail'  => EmailIngestao::where('status', 'erro')->latest()->limit(10)->get(),
            'docsSemVeiculo'   => Entrega::whereNull('veiculo_id')->whereHas('cte')->latest()->limit(10)->get(),
        ]);
    }
}

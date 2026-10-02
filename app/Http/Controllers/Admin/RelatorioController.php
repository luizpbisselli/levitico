<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entrega;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    public function index(Request $request)
    {
        $entregas = Entrega::with(['cte', 'veiculo', 'motorista', 'cliente'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->de, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->ate, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->limit(2000)
            ->get();

        if ($request->query('exportar') === 'csv') {
            $head = ['id', 'cte_numero', 'ctechave', 'placa', 'motorista', 'cliente', 'cidade', 'uf', 'status', 'valor_frete', 'criada_em', 'entregue_em'];
            $rows = $entregas->map(fn ($e) => [
                $e->id, $e->cte?->numero, $e->cte?->chave_acesso, strtoupper((string) $e->veiculo?->placa),
                $e->motorista?->nome, $e->cliente?->nome ?? $e->cte?->destinatario_nome,
                $e->cidade_entrega, $e->uf_entrega, $e->status, $e->cte?->valor_frete,
                $e->created_at?->toDateTimeString(), $e->entregue_em?->toDateTimeString(),
            ]);

            $csv = fopen('php://output', 'w');
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="entregas-'.now()->format('Ymd-His').'.csv"');
            fwrite($csv, "\xEF\xBB\xBF"); // BOM para Excel
            fputcsv($csv, $head, ';');
            foreach ($rows as $r) {
                fputcsv($csv, $r->all(), ';');
            }
            exit;
        }

        return view('admin.relatorios.index', compact('entregas'));
    }
}

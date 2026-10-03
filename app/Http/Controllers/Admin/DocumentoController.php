<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cte;
use App\Models\Nfe;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentoController extends Controller
{
    public function ctes(Request $request)
    {
        $q = Cte::query();
        if ($busca = $request->query('q')) {
            $q->where(fn ($w) => $w
                ->where('chave_acesso', 'like', "%$busca%")
                ->orWhere('numero', 'like', "%$busca%")
                ->orWhere('placa_informada', 'like', "%$busca%")
                ->orWhere('destinatario_nome', 'like', "%$busca%"));
        }

        return view('admin.documentos.ctes', ['ctes' => $q->latest('id')->paginate(20)->withQueryString()]);
    }

    public function nfes(Request $request)
    {
        $q = Nfe::query();
        if ($busca = $request->query('q')) {
            $q->where(fn ($w) => $w
                ->where('chave_acesso', 'like', "%$busca%")
                ->orWhere('numero', 'like', "%$busca%")
                ->orWhere('destinatario_nome', 'like', "%$busca%"));
        }

        return view('admin.documentos.nfes', ['nfes' => $q->latest('id')->paginate(20)->withQueryString()]);
    }

    /** Visualização do XML original (guarda fiscal). */
    public function xml(string $tipo, int $id): Response
    {
        $doc = $tipo === 'cte' ? Cte::findOrFail($id) : Nfe::findOrFail($id);

        $xml = $doc->xml_original ?? '';
        $formatado = $xml ? (simplexml_load_string($xml) ? preg_replace('/><(n|inf|ide|emit|dest|total|transp|det)/', ">\n<$1", $xml) : $xml) : 'XML não armazenado.';

        return response($formatado, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function atribuirVeiculo(Request $request, Cte $cte)
    {
        $dados = $request->validate(['veiculo_id' => ['required', 'integer', 'exists:veiculos,id']]);

        $entrega = $cte->entregas()->firstOrCreate([]);
        $entrega->update(['veiculo_id' => $dados['veiculo_id']]);

        // Sincroniza o motorista pelo vínculo veículo ↔ motorista
        $motoristaId = $entrega->veiculo?->motoristas()->value('motoristas.id');
        if ($motoristaId && ! $entrega->motorista_id) {
            $entrega->update(['motorista_id' => $motoristaId]);
        }

        return back()->with('status', "Veículo atribuído ao CT-e {$cte->numero}.");
    }

    /** Visualização do comprovante / canhoto de entrega pelo administrador. */
    public function verComprovante(\App\Models\Entrega $entrega): Response
    {
        if (! $entrega->comprovante_path) {
            abort(404, 'Comprovante não encontrado.');
        }

        $filePath = storage_path('app/public/' . $entrega->comprovante_path);
        if (! file_exists($filePath)) {
            abort(404, 'Arquivo de comprovante inexistente no servidor.');
        }

        return response()->file($filePath);
    }
}

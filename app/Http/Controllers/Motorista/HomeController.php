<?php

namespace App\Http\Controllers\Motorista;

use App\Http\Controllers\Controller;
use App\Models\Entrega;
use App\Models\WhatsappModelo;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /** Lista das entregas dos veículos do motorista (mobile first). */
    public function index(Request $request)
    {
        $motorista = $request->user()->motorista;
        $abertas = $this->entregasDoMotorista($motorista)
            ->whereIn('status', ['a_coleter', 'em_transito'])
            ->latest()
            ->get();
        $concluidas = $this->entregasDoMotorista($motorista)
            ->whereIn('status', ['entregue', 'ocorrencia'])
            ->latest('entregue_em')
            ->limit(30)
            ->get();

        return view('motorista.home', compact('abertas', 'concluidas'));
    }

    public function show(Request $request, Entrega $entrega)
    {
        $this->authorize('view', $entrega); // Policy no backend: esconder botão não basta

        $entrega->load(['cte.nfes', 'veiculo', 'cliente']);

        $modelo = WhatsappModelo::where('gatilho', $entrega->status)->where('ativo', true)->first()
            ?: WhatsappModelo::where('gatilho', 'manual')->where('ativo', true)->first();

        $mensagem = $modelo?->renderizar($entrega) ?? '';
        $telefone = $this->telefoneCliente($entrega);
        $waUrl = $telefone
            ? 'https://wa.me/'.$telefone.'?text='.rawurlencode($mensagem)
            : 'https://wa.me/?text='.rawurlencode($mensagem.' - sem número cadastrado para o cliente');

        return view('motorista.entrega', compact('entrega', 'mensagem', 'waUrl'));
    }

    /** Botões rápidos: Saí para entrega / Entregue / Ocorrência e envio de Canhoto. */
    public function atualizarStatus(Request $request, Entrega $entrega)
    {
        $this->authorize('updateStatus', $entrega);

        $dados = $request->validate([
            'status'      => ['required', 'in:em_transito,entregue,ocorrencia'],
            'observacao'  => ['nullable', 'string', 'max:1000', 'required_if:status,ocorrencia'],
            'comprovante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $entrega->status = $dados['status'];
        if ($dados['status'] === 'em_transito' && ! $entrega->saiu_em) {
            $entrega->saiu_em = now();
        }
        if ($dados['status'] === 'entregue') {
            $entrega->entregue_em = now();
        }
        if ($dados['status'] === 'ocorrencia') {
            $entrega->observacao_ocorrencia = $dados['observacao'] ?? null;
        }

        // Upload do Canhoto / Comprovante
        if ($request->hasFile('comprovante')) {
            $path = $request->file('comprovante')->store('comprovantes', 'public');
            $entrega->comprovante_path = $path;
            $entrega->comprovante_enviado_em = now();
        }

        $entrega->save();

        return back()->with('status', 'Status atualizado: '.$entrega->statusLabel().($entrega->temComprovante() ? ' (Comprovante anexado)' : '').'.');
    }

    /** Exibe o comprovante / canhoto de forma protegida para o motorista. */
    public function verComprovante(Request $request, Entrega $entrega)
    {
        $this->authorize('view', $entrega);

        if (! $entrega->comprovante_path) {
            abort(404, 'Comprovante não encontrado.');
        }

        $filePath = storage_path('app/public/' . $entrega->comprovante_path);
        if (! file_exists($filePath)) {
            abort(404, 'Arquivo de comprovante inexistente no servidor.');
        }

        return response()->file($filePath);
    }

    private function entregasDoMotorista($motorista)
    {
        if (! $motorista) {
            return Entrega::query()->whereRaw('1 = 0');
        }

        $veiculoIds = $motorista->veiculos()->pluck('veiculos.id');

        return Entrega::with(['cte', 'veiculo', 'cliente'])->whereIn('veiculo_id', $veiculoIds);
    }

    private function telefoneCliente(Entrega $entrega): ?string
    {
        $numero = $entrega->cliente?->whatsapp
            ?? $entrega->cte?->nfes->first()?->destinatario_telefone
            ?? $entrega->cliente?->telefone;

        if (! $numero) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $numero);
        // Garante DDI 55 quando vier só o DDD
        if (strlen($digitos) <= 11) {
            $digitos = '55'.$digitos;
        }

        return $digitos;
    }
}

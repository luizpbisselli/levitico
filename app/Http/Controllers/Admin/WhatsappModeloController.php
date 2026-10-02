<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappModelo;
use Illuminate\Http\Request;

class WhatsappModeloController extends Controller
{
    public function index()
    {
        return view('admin.modelos.index', ['modelos' => WhatsappModelo::orderBy('nome')->get()]);
    }

    public function edit(WhatsappModelo $modelo)
    {
        return view('admin.modelos.form', compact('modelo'));
    }

    public function update(Request $request, WhatsappModelo $modelo)
    {
        $modelo->update($request->validate([
            'nome'     => ['required', 'string', 'max:100'],
            'gatilho'  => ['required', 'string', 'in:em_transito,entregue,ocorrencia,manual'],
            'template' => ['required', 'string'],
            'ativo'    => ['boolean'],
        ]) + ['ativo' => $request->boolean('ativo')]);

        return redirect()->route('admin.modelos.index')->with('status', 'Modelo atualizado.');
    }
}

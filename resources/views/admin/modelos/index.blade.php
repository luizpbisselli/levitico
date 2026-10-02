@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">Modelos de mensagem WhatsApp</h1>
<p class="text-sm text-gray-600 mb-4">Placeholders disponíveis: <code>{cliente} {nfe} {placa} {status} {motorista} {cidade}</code></p>
<table class="w-full bg-white rounded shadow text-sm">
    <thead><tr class="text-left border-b bg-gray-50"><th class="p-3">Nome</th><th class="p-3">Gatilho</th><th class="p-3">Template</th><th class="p-3">Ativo</th><th class="p-3"></th></tr></thead>
    <tbody>
    @foreach($modelos as $m)
        <tr class="border-b">
            <td class="p-3">{{ $m->nome }}</td>
            <td class="p-3">{{ $m->gatilho }}</td>
            <td class="p-3 text-gray-600">{{ str($m->template)->limit(90) }}</td>
            <td class="p-3">{{ $m->ativo ? 'Sim' : 'Não' }}</td>
            <td class="p-3"><a href="{{ route('admin.modelos.edit', $m) }}" class="text-blue-700 hover:underline">Editar</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection

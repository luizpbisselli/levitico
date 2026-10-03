@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Usuários do sistema</h1>
    <a href="{{ route('admin.usuarios.create') }}" class="bg-slate-800 text-white px-4 py-2 rounded">+ Novo usuário</a>
</div>

@if(session('status'))<div class="bg-green-50 border border-green-300 text-green-800 rounded p-3 mb-4">{{ session('status') }}</div>@endif
@if($errors->any())<div class="bg-red-50 border border-red-300 text-red-800 rounded p-3 mb-4"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<table class="w-full bg-white rounded shadow text-sm">
    <thead><tr class="text-left border-b bg-gray-50"><th class="p-3">Nome</th><th class="p-3">E-mail</th><th class="p-3">Perfil</th><th class="p-3">Motorista vinculado</th><th class="p-3">Criado em</th><th class="p-3"></th></tr></thead>
    <tbody>
    @foreach($usuarios as $u)
        <tr class="border-b">
            <td class="p-3">{{ $u->name }}</td>
            <td class="p-3">{{ $u->email }}</td>
            <td class="p-3"><span class="rounded px-2 py-0.5 {{ $u->isAdmin() ? 'bg-slate-800 text-white' : 'bg-gray-200 text-gray-800' }}">{{ ucfirst($u->profile) }}</span></td>
            <td class="p-3">{{ $u->motorista?->nome ?? '—' }}</td>
            <td class="p-3">{{ $u->created_at?->format('d/m/Y') }}</td>
            <td class="p-3 text-right">
                <a href="{{ route('admin.usuarios.edit', $u) }}" class="text-blue-700 hover:underline mr-3">Editar</a>
                @if($u->id !== auth()->id())
                <form method="POST" action="{{ route('admin.usuarios.destroy', $u) }}" class="inline" onsubmit="return confirm('Remover este usuário?')">
                    @csrf @method('DELETE')<button class="text-red-700 hover:underline">Remover</button>
                </form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $usuarios->links() }}</div>
@endsection

@extends('layouts.app')
@section('title', 'Configurações')
@section('content')
<h1 class="text-2xl font-bold mb-2">⚙️ Configurações</h1>
<p class="text-sm text-gray-600 mb-6">
    Configure aqui o banco de dados e a caixa de e-mail que alimenta o sistema com os XMLs de NF-e/CT-e.
    Tudo é salvo no próprio sistema — não é preciso editar <code>.env</code> nem acessar terminal/SSH.
</p>

{{-- ===================== BANCO DE DADOS ===================== --}}
<div class="bg-white rounded shadow p-5 mb-6 max-w-3xl">
    <h2 class="font-semibold text-lg mb-1">🗄️ Banco de dados</h2>
    <p class="text-sm text-gray-500 mb-4">
        Em hospedagem compartilhada, crie o banco e o usuário em <b>cPanel → Bancos de Dados MySQL®</b>
        e preencha abaixo. Ao salvar, as tabelas são criadas/atualizadas automaticamente na próxima página aberta.
    </p>

    <form method="POST" action="{{ route('admin.configuracoes.banco') }}" class="space-y-3">
        @csrf
        <div class="grid md:grid-cols-2 gap-3">
            <label class="block text-sm">
                <span class="font-medium">SGBD</span>
                <select name="db_driver" class="mt-1 w-full border rounded px-2 py-1.5">
                    <option value="sqlite" @selected($driverAtual === 'sqlite')>SQLite (padrão, zero-config)</option>
                    <option value="mysql"  @selected($driverAtual === 'mysql')>MySQL (recomendado em produção)</option>
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-medium">Host</span>
                <input name="db_host" value="{{ $config['db.host'] ?? 'localhost' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5" placeholder="localhost ou mysql.seudominio.com">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Porta</span>
                <input name="db_port" type="number" value="{{ $config['db.port'] ?? '3306' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Nome do banco</span>
                <input name="db_database" value="{{ $config['db.database'] ?? '' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5" placeholder="usuario_concilia">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Usuário MySQL</span>
                <input name="db_username" value="{{ $config['db.username'] ?? '' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5" placeholder="usuario_db">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Senha MySQL</span>
                <input name="db_password" type="password" value=""
                       class="mt-1 w-full border rounded px-2 py-1.5"
                       placeholder="{{ !empty($config['db.password']) ? '•••••••• (deixe em branco para manter)' : 'senha do banco' }}">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Prefixo de tabela (opcional)</span>
                <input name="db_prefixo" value="{{ $config['db.prefixo_tabela'] ?? '' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5" placeholder="ex.: wp_ / fretes_">
            </label>
        </div>
        <div class="flex gap-2 pt-2">
            <button class="bg-slate-800 text-white text-sm px-4 py-2 rounded hover:bg-slate-700">Salvar banco</button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.configuracoes.banco.testar') }}" class="mt-3 border-t pt-3">
        @csrf
        <p class="text-xs text-gray-500 mb-2">Testar conexão sem salvar (usa os valores salvos se a senha ficar em branco):</p>
        <div class="grid md:grid-cols-4 gap-2 text-sm">
            <input name="db_host" value="{{ $config['db.host'] ?? 'localhost' }}" class="border rounded px-2 py-1.5" placeholder="Host" required>
            <input name="db_port" type="number" value="{{ $config['db.port'] ?? '3306' }}" class="border rounded px-2 py-1.5" placeholder="Porta">
            <input name="db_database" value="{{ $config['db.database'] ?? '' }}" class="border rounded px-2 py-1.5" placeholder="Banco" required>
            <input name="db_username" value="{{ $config['db.username'] ?? '' }}" class="border rounded px-2 py-1.5" placeholder="Usuário" required>
        </div>
        <div class="flex items-center gap-2 mt-2">
            <input name="db_password" type="password" class="border rounded px-2 py-1.5 text-sm flex-1" placeholder="Senha (opcional)">
            <button class="bg-slate-500 text-white text-sm px-4 py-2 rounded hover:bg-slate-400">Testar conexão</button>
        </div>
    </form>
</div>

{{-- ===================== E-MAIL DE INGESTÃO ===================== --}}
<div class="bg-white rounded shadow p-5 mb-6 max-w-3xl">
    <h2 class="font-semibold text-lg mb-1">📧 Caixa de e-mail dos XMLs (NF-e / CT-e)</h2>
    <p class="text-sm text-gray-500 mb-4">
        E-mail dedicado onde a transportadora/emissor envia os XMLs. O cron lê esta caixa via IMAP, extrai os anexos
        (.xml ou .zip) e alimenta o sistema. {{ $imapDisponivel
            ? 'A extensão php-imap está disponível neste servidor.'
            : 'A extensão php-imap não está disponível; será usado o cliente IMAP nativo (socket SSL).' }}
        {{ $emailConfigurado ? '' : '⚠️ Ainda não configurado.' }}
    </p>

    <form method="POST" action="{{ route('admin.configuracoes.email') }}" class="space-y-3">
        @csrf
        <div class="grid md:grid-cols-2 gap-3">
            <label class="block text-sm">
                <span class="font-medium">Servidor IMAP</span>
                <input name="email_host" value="{{ $config['email.host'] ?? '' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5" placeholder="mail.seudominio.com">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Porta</span>
                <input name="email_port" type="number" value="{{ $config['email.port'] ?? '993' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Endereço da caixa</span>
                <input name="email_user" type="email" value="{{ $config['email.user'] ?? '' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5" placeholder="xmls@seudominio.com">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Senha</span>
                <input name="email_password" type="password" value=""
                       class="mt-1 w-full border rounded px-2 py-1.5"
                       placeholder="{{ !empty($config['email.password']) ? '•••••••• (deixe em branco para manter)' : 'senha do e-mail' }}">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Pasta de entrada</span>
                <input name="email_box" value="{{ $config['email.box'] ?? 'INBOX' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5">
            </label>
            <label class="block text-sm">
                <span class="font-medium">Pasta de processados</span>
                <input name="email_pasta_processados" value="{{ $config['email.pasta_processados'] ?? 'Processados' }}"
                       class="mt-1 w-full border rounded px-2 py-1.5">
            </label>
        </div>
        <div class="flex gap-2 pt-2">
            <button class="bg-slate-800 text-white text-sm px-4 py-2 rounded hover:bg-slate-700">Salvar e-mail</button>
        </div>
    </form>

    <div class="mt-3 border-t pt-3 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.configuracoes.email.testar') }}">
            @csrf
            <button class="bg-slate-500 text-white text-sm px-4 py-2 rounded hover:bg-slate-400">🔌 Testar conexão IMAP</button>
        </form>
        <form method="POST" action="{{ route('admin.configuracoes.email.processar') }}">
            @csrf
            <button class="bg-emerald-600 text-white text-sm px-4 py-2 rounded hover:bg-emerald-500">▶ Processar agora</button>
        </form>
    </div>
    <p class="text-xs text-gray-500 mt-2">
        “Processar agora” lê as mensagens não lidas, ingere os XMLs e move os e-mails para a pasta de processados.
        Com o cron ativo, isso roda sozinho a cada minuto.
    </p>
</div>

{{-- ===================== ÚLTIMAS INGESTÕES ===================== --}}
<div class="bg-white rounded shadow p-5 max-w-3xl">
    <h2 class="font-semibold text-lg mb-3">🕒 Últimos eventos de ingestão</h2>
    @forelse($statusUltimos as $log)
        <div class="border-b py-2 text-sm">
            <b>{{ $log->assunto }}</b>
            <span class="text-gray-500">({{ $log->remetente ?: '—' }})</span>
            <span class="ml-2 px-2 py-0.5 rounded text-xs
                {{ $log->status === 'erro' ? 'bg-red-100 text-red-700' : ($log->status === 'duplicado' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                {{ $log->status }}
            </span>
            @if($log->detalhes)
                <div class="text-xs text-gray-600 mt-1">{{ Str::limit($log->detalhes, 200) }}</div>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500">Nenhum evento registrado ainda.</p>
    @endforelse
</div>
@endsection

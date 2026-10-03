@extends('layouts.app')
@section('title', 'Configurações')
@section('content')
<div class="page-header" style="flex-direction:column; align-items:flex-start; gap:0.25rem;">
    <h1 class="page-title">⚙️ Configurações do Sistema</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
        Gerenciamento do banco de dados e da caixa de e-mail que ingere os XMLs de NF-e/CT-e.
    </p>
    <p style="font-size:0.75rem; color:var(--text-muted); margin:0.25rem 0 0 0;">
        🔒 Exclusivo de administradores. Credenciais são cifradas com AES-256 e alterações são auditadas.
    </p>
</div>

<div style="display:flex; flex-direction:column; gap:1.5rem; max-width:800px;">

    {{-- ===================== BANCO DE DADOS ===================== --}}
    <div class="card">
        <div class="card-header">
            <h2 style="font-weight:700; font-size:1.05rem; margin:0;">🗄️ Banco de Dados MySQL</h2>
            <p style="font-size:0.8rem; color:var(--text-muted); margin:0.25rem 0 0 0;">
                Em hospedagem cPanel, crie a base e o usuário em <b>Bancos de Dados MySQL®</b> e salve abaixo.
            </p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.configuracoes.banco') }}" style="display:flex; flex-direction:column; gap:1rem;">
                @csrf
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">SGBD</label>
                        <select name="db_driver" class="form-select">
                            <option value="mysql" selected>MySQL (Padrão de Produção)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Host do Banco</label>
                        <input name="db_host" value="{{ $config['db.host'] ?? 'localhost' }}" class="form-input" placeholder="localhost ou 127.0.0.1">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Porta</label>
                        <input name="db_port" type="number" value="{{ $config['db.port'] ?? '3306' }}" class="form-input">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Nome da Base de Dados</label>
                        <input name="db_database" value="{{ $config['db.database'] ?? '' }}" class="form-input" placeholder="usuario_levitico">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Usuário MySQL</label>
                        <input name="db_username" value="{{ $config['db.username'] ?? '' }}" class="form-input" placeholder="usuario_db">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Senha MySQL</label>
                        <input name="db_password" type="password" value="" class="form-input"
                               placeholder="{{ $dbPasswordConfigurada ? '•••••••• já salva (manter)' : 'senha do banco' }}">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Prefixo das Tabelas (opcional)</label>
                        <input name="db_prefixo" value="{{ $config['db.prefixo_tabela'] ?? '' }}" class="form-input" placeholder="ex.: lev_">
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Salvar Banco de Dados</button>
                </div>
            </form>

            <div style="margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border);">
                <form method="POST" action="{{ route('admin.configuracoes.banco.testar') }}">
                    @csrf
                    <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0.5rem;">Testar parâmetros sem salvar:</p>
                    <div class="grid md:grid-cols-4 gap-2">
                        <input name="db_host" value="{{ $config['db.host'] ?? 'localhost' }}" class="form-input" style="padding:0.4rem 0.6rem; font-size:0.85rem;" placeholder="Host" required>
                        <input name="db_port" type="number" value="{{ $config['db.port'] ?? '3306' }}" class="form-input" style="padding:0.4rem 0.6rem; font-size:0.85rem;" placeholder="Porta">
                        <input name="db_database" value="{{ $config['db.database'] ?? '' }}" class="form-input" style="padding:0.4rem 0.6rem; font-size:0.85rem;" placeholder="Banco" required>
                        <input name="db_username" value="{{ $config['db.username'] ?? '' }}" class="form-input" style="padding:0.4rem 0.6rem; font-size:0.85rem;" placeholder="Usuário" required>
                    </div>
                    <div style="display:flex; gap:0.5rem; margin-top:0.5rem; align-items:center;">
                        <input name="db_password" type="password" class="form-input" style="padding:0.4rem 0.6rem; font-size:0.85rem; flex:1;" placeholder="Senha (opcional se já salva)">
                        <button type="submit" class="btn btn-secondary btn-sm" style="white-space:nowrap;">🔌 Testar Conexão</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===================== E-MAIL DE INGESTÃO ===================== --}}
    <div class="card">
        <div class="card-header">
            <h2 style="font-weight:700; font-size:1.05rem; margin:0;">📧 Caixa de E-mail dos XMLs (IMAP)</h2>
            <p style="font-size:0.8rem; color:var(--text-muted); margin:0.25rem 0 0 0;">
                E-mail dedicado que recebe os XMLs (.xml/.zip). O sistema monitora via IMAP e importa automaticamente.
                {{ $emailConfigurado ? '✅ Configurado.' : '⚠️ Ainda não configurado.' }}
            </p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.configuracoes.email') }}" style="display:flex; flex-direction:column; gap:1rem;">
                @csrf
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Servidor IMAP</label>
                        <input name="email_host" value="{{ $config['email.host'] ?? '' }}" class="form-input" placeholder="mail.seudominio.com">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Porta IMAP</label>
                        <input name="email_port" type="number" value="{{ $config['email.port'] ?? '993' }}" class="form-input">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">E-mail Completo</label>
                        <input name="email_user" type="email" value="{{ $config['email.user'] ?? '' }}" class="form-input" placeholder="xmls@seudominio.com">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Senha da Caixa</label>
                        <input name="email_password" type="password" value="" class="form-input"
                               placeholder="{{ $emailConfigurado ? '•••••••• já salva (manter)' : 'senha do e-mail' }}">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Pasta de Entrada</label>
                        <input name="email_box" value="{{ $config['email.box'] ?? 'INBOX' }}" class="form-input">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Pasta de Processados</label>
                        <input name="email_pasta_processados" value="{{ $config['email.pasta_processados'] ?? 'Processados' }}" class="form-input">
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Salvar Configurações de E-mail</button>
                </div>
            </form>

            <div style="margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border); display:flex; flex-wrap:wrap; gap:0.5rem;">
                <form method="POST" action="{{ route('admin.configuracoes.email.testar') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">🔌 Testar Conexão IMAP</button>
                </form>
                <form method="POST" action="{{ route('admin.configuracoes.email.processar') }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">▶ Processar Caixa Agora</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ===================== ÚLTIMAS INGESTÕES ===================== --}}
    <div class="card">
        <div class="card-header">
            <h2 style="font-weight:700; font-size:1.05rem; margin:0;">🕒 Últimos Eventos de Ingestão</h2>
        </div>
        <div class="card-body" style="padding:0.75rem 1.25rem;">
            @forelse($statusUltimos as $log)
                <div style="border-bottom:1px solid var(--border); padding:0.75rem 0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.25rem;">
                        <div>
                            <b style="font-size:0.875rem;">{{ $log->assunto ?: 'Sem assunto' }}</b>
                            <span style="font-size:0.75rem; color:var(--text-muted);">({{ $log->remetente ?: '—' }})</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <span style="font-size:0.75rem; color:var(--text-muted); font-variant-numeric:tabular-nums;">
                                {{ $log->lido_em ? $log->lido_em->format('d/m/Y H:i:s') : ($log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—') }}
                            </span>
                            <span class="badge {{ $log->status === 'erro' ? 'badge-danger' : ($log->status === 'duplicado' ? 'badge-warning' : 'badge-success') }}">
                                {{ $log->status }}
                            </span>
                        </div>
                    </div>
                    @if($log->detalhes)
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem; word-break:break-word;">{{ Str::limit($log->detalhes, 300) }}</div>
                    @endif
                </div>
            @empty
                <p style="font-size:0.875rem; color:var(--text-muted); padding:1rem 0; margin:0; text-align:center;">Nenhum evento registrado ainda.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection

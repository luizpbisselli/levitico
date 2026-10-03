<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Núcleo de dados do sistema de conciliação de fretes (README, seção 6).
 * Regra do projeto: somente migrations aditivas e idempotentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Veículos amarrados pela placa, com campos expansíveis
        if (! Schema::hasTable('veiculos')) {
            Schema::create('veiculos', function (Blueprint $table) {
                $table->id();
                $table->string('placa', 10)->unique();
                $table->string('tipo')->nullable(); // Truck, Carreta, VUC...
                $table->string('marca')->nullable();
                $table->string('modelo')->nullable();
                $table->string('renavam')->nullable();
                $table->json('extras')->nullable(); // campos futuros sem mexer no núcleo
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        // Motoristas ligados a um user de login
        if (! Schema::hasTable('motoristas')) {
            Schema::create('motoristas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('nome');
                $table->string('cnh')->nullable();
                $table->string('telefone', 30)->nullable();
                $table->boolean('agregado')->default(false);
                $table->json('extras')->nullable();
                $table->timestamps();
            });
        }

        // Relação N:N motorista ↔ veículo
        if (! Schema::hasTable('motorista_veiculo')) {
            Schema::create('motorista_veiculo', function (Blueprint $table) {
                $table->id();
                $table->foreignId('motorista_id')->constrained()->cascadeOnDelete();
                $table->foreignId('veiculo_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['motorista_id', 'veiculo_id']);
            });
        }

        // Clientes / contatos (número de WhatsApp pode vir do XML ou do cadastro)
        if (! Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('documento', 25)->nullable(); // CNPJ/CPF
                $table->string('telefone', 30)->nullable();
                $table->string('whatsapp', 30)->nullable();
                $table->string('endereco')->nullable();
                $table->string('cidade', 100)->nullable();
                $table->string('uf', 2)->nullable();
                $table->string('cep', 10)->nullable();
                $table->timestamps();
            });
        }

        // NF-e
        if (! Schema::hasTable('nfes')) {
            Schema::create('nfes', function (Blueprint $table) {
                $table->id();
                $table->char('chave_acesso', 44)->unique();
                $table->string('numero', 20)->nullable();
                $table->string('serie', 10)->nullable();
                $table->date('emissao')->nullable();
                $table->string('emitente_nome')->nullable();
                $table->string('emitente_cnpj', 25)->nullable();
                $table->string('destinatario_nome')->nullable();
                $table->string('destinatario_documento', 25)->nullable();
                $table->string('destinatario_telefone', 30)->nullable();
                $table->string('destinatario_endereco')->nullable();
                $table->string('destinatario_cidade', 100)->nullable();
                $table->string('destinatario_uf', 2)->nullable();
                $table->decimal('valor_total', 14, 2)->default(0);
                $table->integer('volumes')->nullable();
                $table->decimal('peso_bruto', 14, 3)->nullable();
                $table->text('xml_original')->nullable(); // guarda fiscal: sempre armazenar
                $table->boolean('cancelada')->default(false);
                $table->foreignId('cliente_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }

        // CT-e
        if (! Schema::hasTable('ctes')) {
            Schema::create('ctes', function (Blueprint $table) {
                $table->id();
                $table->char('chave_acesso', 44)->unique();
                $table->string('numero', 20)->nullable();
                $table->string('serie', 10)->nullable();
                $table->date('emissao')->nullable();
                $table->string('tomador_nome')->nullable();
                $table->string('remetente_nome')->nullable();
                $table->string('destinatario_nome')->nullable();
                $table->string('destinatario_cidade', 100)->nullable();
                $table->string('destinatario_uf', 2)->nullable();
                $table->decimal('valor_frete', 14, 2)->default(0);
                $table->string('placa_informada', 10)->nullable(); // às vezes não vem no XML
                $table->text('xml_original')->nullable();
                $table->boolean('cancelado')->default(false);
                $table->timestamps();
            });
        }

        // Relação N:N CT-e ↔ NF-e (conciliação pelas chaves em infDoc)
        if (! Schema::hasTable('cte_nfe')) {
            Schema::create('cte_nfe', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cte_id')->constrained()->cascadeOnDelete();
                $table->foreignId('nfe_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['cte_id', 'nfe_id']);
            });
        }

        // Entregas geradas a partir dos documentos
        if (! Schema::hasTable('entregas')) {
            Schema::create('entregas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cte_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('veiculo_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('motorista_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('cliente_id')->nullable()->constrained()->nullOnDelete();
                // Status: a_coleter | em_transito | entregue | ocorrencia
                $table->string('status', 20)->default('a_coleter');
                $table->string('endereco_entrega')->nullable();
                $table->string('cidade_entrega', 100)->nullable();
                $table->string('uf_entrega', 2)->nullable();
                $table->text('observacao_ocorrencia')->nullable();
                $table->timestamp('saiu_em')->nullable();
                $table->timestamp('entregue_em')->nullable();
                $table->timestamps();
                $table->index('status');
            });
        }

        // Log de cada e-mail lido pela ingestão
        if (! Schema::hasTable('email_ingestoes')) {
            Schema::create('email_ingestoes', function (Blueprint $table) {
                $table->id();
                $table->string('message_id')->nullable()->index();
                $table->string('assunto')->nullable();
                $table->string('remetente')->nullable();
                $table->timestamp('lido_em')->nullable();
                // Status: processado | erro | duplicado | ignorado
                $table->string('status', 20)->default('processado');
                $table->text('detalhes')->nullable();
                $table->timestamps();
            });
        }

        // Modelos de mensagem de WhatsApp editáveis pelo admin
        if (! Schema::hasTable('whatsapp_modelos')) {
            Schema::create('whatsapp_modelos', function (Blueprint $table) {
                $table->id();
                $table->string('nome'); // ex.: "Saiu para entrega"
                $table->string('gatilho', 30); // em_transito | entregue | ocorrencia | manual
                $table->text('template'); // placeholders: {cliente} {nfe} {placa} {status} {motorista}
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        // Log de auditoria das ações do admin
        if (! Schema::hasTable('auditoria_logs')) {
            Schema::create('auditoria_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('acao'); // login | create | update | delete | migrate ...
                $table->string('modelo', 100)->nullable();
                $table->unsignedBigInteger('registro_id')->nullable();
                $table->json('payload')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_logs');
        Schema::dropIfExists('whatsapp_modelos');
        Schema::dropIfExists('email_ingestoes');
        Schema::dropIfExists('entregas');
        Schema::dropIfExists('cte_nfe');
        Schema::dropIfExists('ctes');
        Schema::dropIfExists('nfes');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('motorista_veiculo');
        Schema::dropIfExists('motoristas');
        Schema::dropIfExists('veiculos');
    }
};

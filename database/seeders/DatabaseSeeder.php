<?php

namespace Database\Seeders;

use App\Models\Motorista;
use App\Models\User;
use App\Models\Veiculo;
use App\Models\WhatsappModelo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin inicial (README seção 8: "criar o primeiro usuário admin")
        $admin = User::firstOrCreate(
            ['email' => 'admin@fretes.local'],
            ['name' => 'Administrador', 'password' => Hash::make('admin123'), 'profile' => User::PROFILE_ADMIN]
        );

        // Usuário de teste com perfil motorista
        if (! app()->environment('production')) {
            $userMotorista = User::firstOrCreate(
                ['email' => 'joao@fretes.local'],
                ['name' => 'João Motorista', 'password' => Hash::make('motorista123'), 'profile' => User::PROFILE_MOTORISTA]
            );

            $veiculo = Veiculo::firstOrCreate(['placa' => 'ABC1D23'], [
                'tipo' => 'Truck', 'marca' => 'Volvo', 'modelo' => 'FH 460', 'ativo' => true,
            ]);

            $motorista = Motorista::firstOrCreate(
                ['nome' => 'João da Silva'],
                ['user_id' => $userMotorista->id, 'cnh' => '12345678900', 'telefone' => '5511999998888', 'agregado' => true]
            );
            $motorista->veiculos()->syncWithoutDetaching([$veiculo->id]);
        }

        // Modelos de mensagem WhatsApp editáveis pelo admin (README seção 4)
        WhatsappModelo::firstOrCreate(
            ['gatilho' => 'em_transito', 'nome' => 'Saiu para entrega'],
            ['template' => "Olá {cliente}! Sua mercadoria (NF-e {nfe}) saiu para entrega hoje. Motorista: {motorista} ({placa})."]
        );
        WhatsappModelo::firstOrCreate(
            ['gatilho' => 'entregue', 'nome' => 'Entrega concluída'],
            ['template' => "Olá {cliente}! Pedido NF-e {nfe} entregue em {cidade}. Obrigado!"]
        );
        WhatsappModelo::firstOrCreate(
            ['gatilho' => 'ocorrencia', 'nome' => 'Ocorrência na entrega'],
            ['template' => "Olá {cliente}, tivemos uma ocorrência na entrega da NF-e {nfe} ({status}). Em breve retornamos contato."]
        );
        WhatsappModelo::firstOrCreate(
            ['gatilho' => 'manual', 'nome' => 'Mensagem livre'],
            ['template' => "Olá {cliente}, sobre a NF-e {nfe} com destino a {cidade}: "]
        );
    }
}

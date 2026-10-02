<?php

namespace App\Policies;

use App\Models\Entrega;
use App\Models\User;

/**
 * O motorista só enxerga entregas ligadas aos seus veículos (README seção 5).
 * Esconder botão na tela não basta: a regra é aplicada no backend.
 */
class EntregaPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // o escopo da query filtra por perfil
    }

    public function view(User $user, Entrega $entrega): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->veiculoDoMotorista($user, $entrega->veiculo_id);
    }

    public function updateStatus(User $user, Entrega $entrega): bool
    {
        return $this->view($user, $entrega);
    }

    private function veiculoDoMotorista(User $user, ?int $veiculoId): bool
    {
        if (!$veiculoId || !$user->motorista) {
            return false;
        }

        return $user->motorista->veiculos()->where('veiculos.id', $veiculoId)->exists();
    }
}

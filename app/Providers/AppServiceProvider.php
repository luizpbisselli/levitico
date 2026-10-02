<?php

namespace App\Providers;

use App\Models\Entrega;
use App\Policies\EntregaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Produção no IP fixo: força HTTPS em todo o site (README seção 5)
        if (str_contains((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::policy(Entrega::class, EntregaPolicy::class);
    }
}

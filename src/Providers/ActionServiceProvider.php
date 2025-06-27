<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions\Providers;

use Illuminate\Support\ServiceProvider;

final class ActionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/quantum-action.php', // caminho do config padrão do pacote
            'quantum-action' // nome da chave de configuração
        );

        $this->app->register(\Illuminate\Concurrency\ConcurrencyServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}

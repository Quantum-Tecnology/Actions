<?php

declare(strict_types = 1);

namespace Quantumtecnology\Actions\Providers;

use Illuminate\Support\ServiceProvider;

final class ActionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(\Illuminate\Concurrency\ConcurrencyServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}

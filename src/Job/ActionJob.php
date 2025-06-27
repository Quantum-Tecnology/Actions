<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions\Job;

use BackedEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use UnitEnum;

class ActionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public object $action,
        public BackedEnum | UnitEnum | string | null $onQueue,
        /** @var array<int, mixed> $arguments */
        public array $arguments = [],
        public array $backoff = []
    ) {
        dd($this->backoff());
    }

    public function handle(): void
    {
        if (method_exists($this->action, 'execute')) {
            $this->action->execute(...$this->arguments);
        }
    }

    public function backoff(): array
    {
        if (filled($this->backoff)) {
            return $this->backoff;
        }

        $env     = app()->environment();
        $baseKey = 'quantum-action.job.backoff.';
        $default = config($baseKey . 'local.default') ?: [];

        if (!$this->onQueue) {
            return config($baseKey . $env . '.default', $default);
        }

        return config(
            $baseKey . $env . '.' . $this->onQueue,
            config($baseKey . $this->onQueue, $default)
        );
    }
}

<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ActionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public object $action,
        /** @var array<int, mixed> $arguments */
        public array $arguments = [],
    ) {
        //
    }

    public function handle(): void
    {
        if (method_exists($this->action, 'execute')) {
            $this->action->execute(...$this->arguments);
        }
    }
}

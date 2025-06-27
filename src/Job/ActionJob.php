<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions\Job;

use BackedEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use function QuantumTecnology\Actions\Support\quantum_action_enum_value;

use UnitEnum;

class ActionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public ?string $onQueue = null;

    /**
     * @param array<int, mixed>     $arguments
     * @param array<int, int|float> $backoff
     */
    public function __construct(
        public object $action,
        BackedEnum | UnitEnum | string | null $onQueue,
        public array $arguments = [],
        public array $backoff = []
    ) {
        $this->setOnQueueValue($onQueue);
    }

    public function handle(): void
    {
        if (method_exists($this->action, 'execute')) {
            $this->action->execute(...$this->arguments);
        }
    }

    /**
     * @return array<int | float|int>
     */
    public function backoff(): array
    {
        if (filled($this->backoff)) {
            return $this->backoff;
        }

        $env     = app()->environment();
        $baseKey = 'quantum-action.job.backoff.';
        $default = config($baseKey . 'local.default') ?: [];

        if (null === $this->onQueue || '' === $this->onQueue || '0' === $this->onQueue) {
            return array_map(
                fn ($item) => is_int($item) ? $item : (is_numeric($item) ? (int) $item : 0),
                (array) config($baseKey . $env . '.default', $default)
            );
        }

        return array_map(
            fn ($item) => is_int($item) ? $item : (is_numeric($item) ? (int) $item : 0),
            (array) config(
                $baseKey . $env . '.' . $this->onQueue,
                (array) config($baseKey . $this->onQueue, $default)
            )
        );
    }

    protected function setOnQueueValue(BackedEnum | UnitEnum | string | null $onQueue): void
    {
        $value = quantum_action_enum_value($onQueue);

        if (is_string($value)) {
            $this->onQueue = $value;
        } elseif (is_null($value)) {
            $this->onQueue = null;
        } elseif (is_int($value) || is_float($value) || is_bool($value)) {
            $this->onQueue = (string) $value;
        } else {
            $this->onQueue = null;
        }
    }
}

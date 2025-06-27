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
     * @return array<int>
     */
    public function backoff(): array
    {
        if (filled($this->backoff)) {
            return $this->castArrayToInt($this->backoff);
        }

        $env     = app()->environment();
        $baseKey = 'quantum-action.job.backoff.';
        /** @var array<int, int|float> $default */
        $default = is_array(config($baseKey . 'local.default')) ? config($baseKey . 'local.default') : [];

        $configKey = $this->getConfigKey($env, $baseKey);
        $fallback  = $this->getFallback($baseKey, $default);

        /** @var array<int, mixed> $values */
        $values = is_array(config($configKey, $fallback)) ? config($configKey, $fallback) : [];

        return $this->castArrayToInt($values);
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

    /**
     * @param array<int, mixed> $items
     *
     * @return array<int>
     */
    private function castArrayToInt(array $items): array
    {
        return array_map([$this, 'castToInt'], $items);
    }

    /**
     * @param int|float|bool|string|null $item
     */
    private function castToInt($item): int
    {
        return is_int($item) ? $item : (is_numeric($item) ? (int) $item : 0);
    }

    private function getConfigKey(string $env, string $baseKey): string
    {
        if (null === $this->onQueue || '' === $this->onQueue || '0' === $this->onQueue) {
            return $baseKey . $env . '.default';
        }

        return $baseKey . $env . '.' . $this->onQueue;
    }

    /**
     * @param array<int, int|float> $default
     *
     * @return array<int, int|float>
     */
    private function getFallback(string $baseKey, array $default): array
    {
        if (null === $this->onQueue || '' === $this->onQueue || '0' === $this->onQueue) {
            return array_values($default);
        }
        $config = config($baseKey . $this->onQueue, $default);
        $values = is_array($config) ? $config : $default;

        // Garante que todos os valores sejam int|float e reindexa
        return array_values(array_map(
            static fn ($v) => is_int($v) || is_float($v) ? $v : 0,
            $values
        ));
    }
}

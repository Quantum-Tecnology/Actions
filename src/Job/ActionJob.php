<?php

declare(strict_types = 1);

namespace Core\Actions\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ActionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public mixed $action,
        public array $arguments = [],
    ) {
        //
    }

    public function handle(): void
    {
        $this->action->execute(...$this->arguments);
    }
}

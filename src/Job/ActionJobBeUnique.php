<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions\Job;

use Illuminate\Contracts\Queue\ShouldQueue;

final class ActionJobBeUnique extends ActionJob implements ShouldQueue
{
    public int $uniqueFor = 0;
}

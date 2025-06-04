<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions\Job;

use Illuminate\Contracts\Queue\ShouldQueue;

final class ActionJobBeUniqueUntilProcessing extends ActionJob implements ShouldQueue
{
    //
}

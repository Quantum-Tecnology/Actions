<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions\Support;

use BackedEnum;
use UnitEnum;

if (!function_exists('quantum_enum_value')) {
    function quantum_action_enum_value(mixed $value, mixed $default = null): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum   => $value->name,

            default => $value ?? value($default),
        };
    }
}

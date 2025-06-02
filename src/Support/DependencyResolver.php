<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions\Support;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class DependencyResolver
{
    /**
     * Resolve the dependencies of a closure or serializable closure.
     *
     * @param array<int, mixed> $arguments
     *
     * @return array<string, mixed>
     *
     * @throws ReflectionException|BindingResolutionException
     */
    public function resolve(string $closure, array $arguments = []): array
    {
        $container = app(Container::class);

        $reflection = new ReflectionMethod($closure, 'execute');

        $parameters = $reflection->getParameters();
        $resolved   = [];

        $application = app(Application::class);

        if (count($arguments) === count($parameters)) {
            foreach ($parameters as $index => $parameter) {
                $resolved[$parameter->name] = $arguments[$index];
            }

            return $resolved;
        }

        foreach ($parameters as $parameter) {
            foreach ($parameter->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();

                if (method_exists($instance, 'resolve')) {
                    $resolved[$parameter->name] = $instance->resolve($instance, $container);

                    continue 2;
                }
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $resolved[$parameter->name] = $application->make($type->getName());

                continue;
            }

            $resolved[$parameter->name] = array_shift($arguments);
        }

        return $resolved;
    }
}

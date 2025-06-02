<?php

declare(strict_types = 1);

namespace Core\Actions\Support;

use Closure;
use Core\Actions\Exceptions\DependencyUnresolvable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Laravel\SerializableClosure\SerializableClosure;
use ReflectionException;
use ReflectionMethod;

final readonly class DependencyResolver
{
    /**
     * Resolve the dependencies of a closure or serializable closure.
     *
     * @throws ReflectionException|BindingResolutionException|DependencyUnresolvable
     */
    public function resolve($closure, array $arguments = []): array
    {
        $container = app(Container::class);

        $closure = $closure instanceof SerializableClosure
            ? $closure->getClosure()
            : $closure;

        $reflection = new ReflectionMethod($closure, 'execute');

        $parameters = $reflection->getParameters();
        $resolved   = [];

        $application = app(Application::class);

        if (count($arguments) === count($parameters)) {
            return $arguments;
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

            if (method_exists($type, 'getName') && !$type->isBuiltin()) {
                $resolved[$parameter->name] = $application->make($type->getName());

                continue;
            }

            $resolved[$parameter->name] = array_shift($arguments);
        }

        return $resolved;
    }
}

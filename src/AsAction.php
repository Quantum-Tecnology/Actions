<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions;

use Core\Actions\Contracts\ShouldDefer;
use Core\Actions\Contracts\ShouldQueue;
use Core\Actions\Support\DependencyResolver;
use RuntimeException;

trait AsAction
{
    final public static function run(...$arguments): mixed
    {
        $instance = self::getInstance();

        $hasShouldQueue = in_array(ShouldQueue::class, class_implements($instance));
        $hasShouldDefer = in_array(ShouldDefer::class, class_implements($instance));

        if ($hasShouldQueue && $hasShouldDefer) {
            throw new RuntimeException('The action class cannot implement both ShouldQueue and ShouldDefer interfaces at the same time.');
        }

        if (!method_exists($instance, 'execute')) {
            throw new RuntimeException('The execute method is not defined in the action class.');
        }

        $data = app(DependencyResolver::class)->resolve(static::class, $arguments);

        return match (true) {
            $hasShouldQueue => self::dispatchJob($instance, $data),
            $hasShouldDefer => self::dispatchDefer($instance, $data),
            default         => $instance->execute(...$data),
        };
    }

    final public static function runIf($condition, ...$arguments): mixed
    {
        if ($condition) {
            return static::run(...$arguments);
        }

        return null;
    }

    final public static function runUnless($condition, ...$arguments): mixed
    {
        if (!$condition) {
            return static::run(...$arguments);
        }

        return null;
    }

    protected static function getInstance(): self
    {
        return app(static::class);
    }

    protected static function dispatchJob($instance, $data): bool
    {
        $job = new Job\ActionJob($instance, $data);

        if (method_exists($instance, 'onQueue')) {
            $job->onQueue($instance->onQueue());
        }

        if (method_exists($instance, 'delay')) {
            $job->delay($instance->delay());
        }

        dispatch($job);

        return true;
    }

    protected static function dispatchDefer($instance, $data): bool
    {
        \Illuminate\Support\Facades\Concurrency::defer(fn () => $instance->execute(...$data));

        return true;
    }
}

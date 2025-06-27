<?php

declare(strict_types = 1);

namespace QuantumTecnology\Actions;

use QuantumTecnology\Actions\Contracts\ShouldDefer;
use QuantumTecnology\Actions\Contracts\ShouldQueue;
use QuantumTecnology\Actions\Contracts\ShouldUniqueQueue;
use QuantumTecnology\Actions\Support\DependencyResolver;
use RuntimeException;

trait AsAction
{
    protected static mixed $instanceClass = null;

    final public static function run(...$arguments): mixed
    {
        $instance = self::getInstance();

        $classImplements = class_implements($instance);
        $hasShouldQueue  = in_array(ShouldQueue::class, $classImplements, true);
        $hasShouldDefer  = in_array(ShouldDefer::class, $classImplements, true);

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

    protected static function getInstance(): mixed
    {
        if (null === self::$instanceClass) {
            self::$instanceClass = app(static::class);
        }

        return self::$instanceClass;
    }

    protected static function dispatchJob($instance, $data): bool
    {
        $backoff = [];

        $queue = null;

        if (method_exists($instance, 'onQueue')) {
            $queue = $instance->onQueue();
        }

        if (method_exists($instance, 'backoff')) {
            $backoff = $instance->backoff();
        }

        $classImplements                  = class_implements($instance);
        $hasShouldBeUnique                = in_array(ShouldUniqueQueue::class, $classImplements, true);
        $hasShouldBeUniqueUntilProcessing = in_array(ShouldUniqueQueue::class, $classImplements, true);

        $job = match (true) {
            $hasShouldBeUnique                => new Job\ActionJobUnique($instance, $data, $queue, $backoff),
            $hasShouldBeUniqueUntilProcessing => new Job\ActionJobBeUniqueUntilProcessing($instance, $queue, $data, $backoff),
            default                           => new Job\ActionJob($instance, $queue, $data, $backoff),
        };

        if ($queue) {
            $job->onQueue($queue);
        }

        if (method_exists($instance, 'delay')) {
            $job->delay($instance->delay());
        }

        if (method_exists($instance, 'uniqueVia') && method_exists($job, 'uniqueVia')) {
            $job->uniqueVia();
        }

        if (method_exists($instance, 'uniqueId') && method_exists($job, 'uniqueId')) {
            $job->uniqueId();
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

<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\Agent\Task;

use UseTheFork\Synapse\Agents\Middleware\AddMemory;
use UseTheFork\Synapse\Agents\Middleware\LoadMemory;
use UseTheFork\Synapse\Config;
use UseTheFork\Synapse\Contracts\Memory;

trait UseMemory
{
    /**
     * The Memory
     */
    protected Memory $memory;

    /**
     * gets the Tasks Memory.
     */
    public function memory(): Memory
    {
        return $this->memory ??= $this->resolveMemory();
    }

    /**
     * Define the default request sender.
     */
    public function resolveMemory(): Memory
    {
        if (empty($this->memory)) {
            return Config::getDefaultMemory();
        }

        return new $this->memory;
    }

    public function bootUseMemory(): void
    {
        $this->middleware()->onStartIteration(new LoadMemory, 'LoadMemory');
        $this->middleware()->onEndIteration(new AddMemory, 'AddMemory');
    }
}

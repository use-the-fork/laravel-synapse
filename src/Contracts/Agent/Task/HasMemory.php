<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Contracts\Agent\Task;

use UseTheFork\Synapse\Contracts\Memory;

interface HasMemory
{
    public function memory(): Memory;

    public function resolveMemory(): Memory;
}

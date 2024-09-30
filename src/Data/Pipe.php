<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Data;

use Closure;
use UseTheFork\Synapse\Enums\PipeOrder;

class Pipe
{
    /**
     * The callable inside the pipe
     */
    public readonly Closure $callable;

    /**
     * Constructor
     *
     * @param  callable(mixed $payload): (mixed)  $callable
     */
    public function __construct(
        callable $callable,
        public readonly ?string $name = null,
        public readonly ?PipeOrder $pipeOrder = null,
    ) {
        $this->callable = $callable(...);
    }
}

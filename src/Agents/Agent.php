<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents;

use UseTheFork\Synapse\Contracts\Agent\HasIntegration;
use UseTheFork\Synapse\Traits\Agent\HasMiddleware;
use UseTheFork\Synapse\Traits\Agent\InvokesRequests;
use UseTheFork\Synapse\Traits\Bootable;

abstract class Agent
{
    use InvokesRequests;
    use HasMiddleware;
    use Bootable;

    /**
     * The view to use when generating the prompt for this agent.
     */
    abstract public function resolveIntegration(): HasIntegration;

}

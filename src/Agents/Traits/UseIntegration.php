<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Traits;

use UseTheFork\Synapse\Agents\PendingAgentTask;

trait UseIntegration
{

    /**
     * Initializes the integration by registering it.
     *
     */
    public function bootUseIntegration(PendingAgentTask $pendingAgentTask): void
    {
        $pendingAgentTask->setIntegration($this->resolveIntegration());
    }
}

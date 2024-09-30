<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\PendingAgentTask;

use UseTheFork\Synapse\Agents\PendingAgentTask;

class MergeProperties
{
    /**
     * Merge connector and request properties (headers, query, config, middleware)
     */
    public function __invoke(PendingAgentTask $pendingAgentTask): PendingAgentTask
    {
        $agent = $pendingAgentTask->getAgent();
        $task = $pendingAgentTask->getTask();

        $pendingAgentTask->middleware()
            ->merge($agent->middleware())
            ->merge($task->middleware());

        return $pendingAgentTask;
    }
}

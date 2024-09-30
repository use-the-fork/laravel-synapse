<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Contracts\Agent\Task;

use UseTheFork\Synapse\Agents\AgentTaskResponse;
use UseTheFork\Synapse\Agents\PendingAgentTask;

interface HasOutputSchema
{
    public function getOutputSchema(): string;

    public function validateOutputSchema(PendingAgentTask $pendingAgentTask): AgentTaskResponse;

    public function resolveOutputSchema(): mixed;
}

<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Contracts\Tools;

use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\Agents\AgentTaskResponse;

interface HasTools
{
    public function resolveTools(): array;

    public function executeTools(PendingAgentTask $pendingAgentTask): PendingAgentTask;
}

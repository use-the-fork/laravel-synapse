<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Middleware;

use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\Contracts\TaskMiddleware;
use UseTheFork\Synapse\ValueObject\Agent\Message;

class AddMemory implements TaskMiddleware
{
    public function __invoke(PendingAgentTask $pendingAgentTask): PendingAgentTask
    {

        if ($pendingAgentTask->currentTaskResponse()) {
            $agentResponse = $pendingAgentTask->currentTaskResponse();
            $agentResponseMessage = $agentResponse->response();

            $pendingAgentTask->addMemory(Message::makeOrNull($agentResponseMessage));
        }

        return $pendingAgentTask;

    }
}

<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Agents\Middleware;

    use UseTheFork\Synapse\Agents\PendingAgentTask;
    use UseTheFork\Synapse\Contracts\TaskMiddleware;
    use UseTheFork\Synapse\ValueObject\Agent\Message;

    class LoadMemory implements TaskMiddleware
    {
        public function __invoke(PendingAgentTask $pendingAgentTask): PendingAgentTask
        {

            $inputs = $pendingAgentTask->getTask()->memory()->asInputs();

            $pendingAgentTask->addInput('memoryWithMessages', $inputs['memoryWithMessages']);
            $pendingAgentTask->addInput('memory', $inputs['memory']);

            return $pendingAgentTask;

        }
    }

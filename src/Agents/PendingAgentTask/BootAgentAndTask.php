<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Agents\PendingAgentTask;

    use UseTheFork\Synapse\Agents\PendingAgentTask;

    class BootAgentAndTask
    {
        /**
         * Boot the plugins
         */
        public function __invoke(PendingAgentTask $pendingAgentTask): PendingAgentTask
        {
            $pendingAgentTask->getAgent()->boot($pendingAgentTask);
            $pendingAgentTask->getTask()->boot($pendingAgentTask);

            return $pendingAgentTask;
        }
    }

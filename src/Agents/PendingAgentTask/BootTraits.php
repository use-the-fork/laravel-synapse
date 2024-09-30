<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Agents\PendingAgentTask;

    use UseTheFork\Synapse\Agents\Helpers\Helpers;
    use UseTheFork\Synapse\Agents\PendingAgentTask;

    class BootTraits
    {
        /**
         * Boot the plugins
         */
        public function __invoke(PendingAgentTask $pendingAgentTask): PendingAgentTask
        {
            $agent = $pendingAgentTask->getAgent();
            $task = $pendingAgentTask->getTask();

            $agentTraits = Helpers::classUsesRecursive($agent);
            $taskTraits = Helpers::classUsesRecursive($task);

            foreach ($agentTraits as $agentTrait) {
                Helpers::bootPlugin($pendingAgentTask, $agent, $agentTrait);
            }

            foreach ($taskTraits as $taskTrait) {
                Helpers::bootPlugin($pendingAgentTask, $task, $taskTrait);
            }

            return $pendingAgentTask;
        }
    }

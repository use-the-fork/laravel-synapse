<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Contracts;

    use UseTheFork\Synapse\Agents\PendingAgentTask;

    interface TaskMiddleware
    {
        /**
         * Register a task middleware
         *
         * @return PendingAgentTask|void
         */
        public function __invoke(PendingAgentTask $pendingRequest);
    }

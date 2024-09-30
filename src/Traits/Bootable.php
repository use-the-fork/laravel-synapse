<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Traits;

    use UseTheFork\Synapse\Agents\PendingAgentTask;

    trait Bootable
    {
        /**
         * Handle the boot lifecycle hook
         */
        public function boot(PendingAgentTask $pendingRequest): void
        {
            //
        }
    }

<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Contracts\Agent;


    use UseTheFork\Synapse\Agents\PendingAgentTask;
    use UseTheFork\Synapse\ValueObject\Agent\Response;

    interface HasIntegration
    {
        /**
         * Handles the request to generate a chat response.
         *
         */
        public function handleCompletion(PendingAgentTask $pendingAgentTask): Response;

        /**
         * Forces a model to output its response in a specific format.
         *
         * @return Response The response from the chat request.
         */
        public function handleValidationCompletion(PendingAgentTask $pendingAgentTask): Response;

    }

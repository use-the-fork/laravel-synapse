<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Contracts\OutputSchema;

use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\Agents\Response;

interface HasOutputSchema
{
    public function resolveOutputSchema(): mixed;

    public function setOutputSchema(PendingAgentTask $pendingAgentTask): PendingAgentTask;

    public function validateOutputSchema(Response $response): Response;
}

<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Contracts\OutputSchema;

use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\Agents\Response;
use UseTheFork\Synapse\ValueObject\OutputSchema\SchemaRule;

interface HasOutputSchema
{
    /**
     * Registers the Output Schema rules that the agent must answer with.
     *
     * @return array<SchemaRule> The registered rules.
     */
    public function defaultOutputSchema(): array;

    public function setOutputSchema(PendingAgentTask $pendingAgentTask): PendingAgentTask;

    public function validateOutputSchema(Response $response): Response;
}

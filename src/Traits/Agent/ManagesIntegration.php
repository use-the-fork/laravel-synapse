<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\Agent;

use UseTheFork\Synapse\AgentTask\PendingAgentTask;
use UseTheFork\Synapse\Contracts\Integration;
use UseTheFork\Synapse\Exceptions\MissingResolverException;
use UseTheFork\Synapse\Traits\HasIntegration;
use UseTheFork\Synapse\Traits\HasMiddleware;

trait ManagesIntegration
{
    use HasIntegration;
    use HasMiddleware;

    /**
     * The integration that this Model should use
     */
    protected Integration $integration;

    /**
     * Initializes the integration by registering it.
     *
     * This method assigns the integration object returned by the `registerIntegration` method
     * to the `$integration` property of the class.
     */
    public function bootManagesIntegration(PendingAgentTask $pendingAgentTask): void
    {
        $this->integration = $this->resolveIntegration();
    }

    /**
     * Registers the integration and returns the integration object.
     *
     * This method creates a new instance of a `Integration` class and
     * returns it as the integration object.
     *
     * @return Integration The integration object.
     */
    public function resolveIntegration(): Integration
    {
        throw new MissingResolverException('ManagesIntegration', 'resolveIntegration');
    }

    public function integration(): Integration
    {
        return $this->integration;
    }
}

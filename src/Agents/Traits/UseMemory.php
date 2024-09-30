<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Traits;


trait UseMemory
{

    protected mixed $memory = null;

    /**
     * Initializes the integration by registering it.
     *
     * This method assigns the integration object returned by the `registerIntegration` method
     * to the `$integration` property of the class.
     */
    protected function initializeMemory(): void
    {
        $this->memory = $this->resolveMemory();
    }

}

<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\Agent;

use UseTheFork\Synapse\Contracts\OutputSchema\HasTools;

trait UseOutputSchema
{
    /**
     * Specify the default sender
     */
    protected string $defaultOutputSchema = '';

    /**
     * The request sender.
     */
    protected HasTools $outputSchema;

    /**
     * Manage the request sender.
     */
    public function outputSchema(): HasTools
    {
        return $this->outputSchema ??= $this->defaultOutputSchema();
    }

    /**
     * Define the default request sender.
     */
    protected function defaultOutputSchema(): HasTools
    {
        if (empty($this->defaultOutputSchema)) {
            return Config::getDefaultSender();
        }

        return new $this->defaultOutputSchema;
    }
}

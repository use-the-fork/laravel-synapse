<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\Agent;

use UseTheFork\Synapse\Contracts\OutputSchema\HasOutputSchema;

trait UseOutputSchema
{
    /**
     * Specify the default sender
     */
    protected string $defaultOutputSchema = '';

    /**
     * The request sender.
     */
    protected HasOutputSchema $outputSchema;

    /**
     * Manage the request sender.
     */
    public function outputSchema(): HasOutputSchema
    {
        return $this->outputSchema ??= $this->defaultOutputSchema();
    }

    /**
     * Define the default request sender.
     */
    protected function defaultOutputSchema(): HasOutputSchema
    {
        if (empty($this->defaultOutputSchema)) {
            return Config::getDefaultSender();
        }

        return new $this->defaultOutputSchema;
    }
}

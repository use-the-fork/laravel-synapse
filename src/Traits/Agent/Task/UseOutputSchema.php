<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\Agent\Task;

use UseTheFork\Synapse\Contracts\OutputSchema;
use UseTheFork\Synapse\Traits\Agent\Config;

trait UseOutputSchema
{
    /**
     * Specify the default sender
     */
    protected string $defaultOutputSchema = '';

    /**
     * The request sender.
     */
    protected OutputSchema $outputSchema;

    /**
     * Manage the request sender.
     */
    public function outputSchema(): OutputSchema
    {
        return $this->outputSchema ??= $this->defaultOutputSchema();
    }

    /**
     * Define the default request sender.
     */
    protected function defaultOutputSchema(): OutputSchema
    {
        if (empty($this->defaultOutputSchema)) {
            return Config::getDefaultSender();
        }

        return new $this->defaultOutputSchema;
    }
}

<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Contracts\Agent\Task;

use UseTheFork\Synapse\Contracts\OutputSchema;

interface HasOutputSchema
{
    public function outputSchema(): OutputSchema;

    public function resolveOutputSchema(): OutputSchema;
}

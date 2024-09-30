<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Contracts;

interface HasTools
{
    /**
     * Registers the tools.
     *
     * @return array The registered tools.
     */
    public function resolveTools(): array;
}

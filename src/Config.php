<?php

declare(strict_types=1);

namespace UseTheFork\Synapse;

use UseTheFork\Synapse\Contracts\Memory;
use UseTheFork\Synapse\Memory\CollectionMemory;

final class Config
{
    /**
     * Resolve the sender with a callback
     *
     * @var callable|null
     */
    private static mixed $senderResolver = null;

    /**
     * Create a new default sender
     */
    public static function getDefaultMemory(): Memory
    {
        return new CollectionMemory;
    }
}

<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Enums;

enum PipeOrder: string
{
    case FIRST = 'first';
    case LAST = 'last';
}

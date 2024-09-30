<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Enums;

enum PromptType: string
{
    case COMPLETION = 'COMPLETION';
    case CHAT = 'CHAT';
}

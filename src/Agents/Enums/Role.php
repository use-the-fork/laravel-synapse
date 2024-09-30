<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Enums;

final class Role
{
    public const SYSTEM = 'system';

    public const USER = 'user';

    public const ASSISTANT = 'assistant';

    public const TOOL = 'tool';

    public const IMAGE_URL = 'image_url';
}

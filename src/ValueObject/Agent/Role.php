<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\ValueObject\Agent;

final class Role
{
    public const SYSTEM = 'system';

    public const USER = 'user';

    public const ASSISTANT = 'assistant';

    public const TOOL = 'tool';

    public const IMAGE_URL = 'image_url';
}

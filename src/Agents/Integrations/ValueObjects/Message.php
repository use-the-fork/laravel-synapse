<?php

declare(strict_types=1);
//Credits to https://github.com/bootstrapguru/dexor

namespace UseTheFork\Synapse\Agents\Integrations\ValueObjects;

use UseTheFork\Synapse\ValueObject\ArrayValueObject;

class Message extends ArrayValueObject
{
    /**
     * Define the rules for Message validator.
     */
    protected function validationRules(): array
    {
        return [
            'role' => 'required',
            'finish_reason' => 'nullable|sometimes|string',
            'content' => 'nullable|sometimes',

            'tool_call_id' => 'nullable|sometimes|string',
            'tool_name' => 'nullable|sometimes|string',
            'tool_arguments' => 'nullable|sometimes|string',
            'tool_content' => 'nullable|sometimes',

            'image' => 'nullable|sometimes|array',
            'image.url' => 'nullable|sometimes|string',
        ];
    }

    /**
     * Apply sanitization rules
     */
    protected function sanitize(): void {}

    public function finishReason(): string
    {
        return $this->value['finish_reason'];
    }

    public function content(): ?string
    {
        return empty($this->value['content']) ? null : $this->value['content'];
    }

    public function role(): string
    {
        return $this->value['role'];
    }
}

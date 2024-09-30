<?php

declare(strict_types=1);
//Credits to https://github.com/bootstrapguru/dexor

namespace UseTheFork\Synapse\ValueObject\Agent;

use UseTheFork\Synapse\ValueObject\ArrayValueObject;

class Response extends ArrayValueObject
{
    /**
     * Define the rules for Response validator.
     */
    protected function validationRules(): array
    {
        return [
            'role' => 'required',
            'finish_reason' => 'nullable|sometimes|string',
            'content' => 'nullable|sometimes|string',
            'tool_call' => 'nullable|sometimes',
        ];
    }

    /**
     * Apply sanitization rules
     */
    protected function sanitize(): void
    {

        if (empty($this->value['tool_call'])) {
            $this->value['tool_call'] = [];
        }
    }

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

    public function toolCall(): array
    {
        return $this->value['tool_call'];
    }
}

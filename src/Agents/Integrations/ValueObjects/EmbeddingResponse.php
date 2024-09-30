<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Integrations\ValueObjects;

use UseTheFork\Synapse\ValueObject\ArrayValueObject;

class EmbeddingResponse extends ArrayValueObject
{
    /**
     * Define the rules for embedding validator.
     */
    protected function validationRules(): array
    {
        return [
            'embedding' => 'required',
        ];
    }

    /**
     * Apply sanitization rules
     */
    protected function sanitize(): void {}

    public function embedding(): array
    {
        return $this->value['embedding'];
    }
}

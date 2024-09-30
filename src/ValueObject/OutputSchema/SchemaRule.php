<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\ValueObject\OutputSchema;

use InvalidArgumentException;
use UseTheFork\Synapse\ValueObject\ValueObject;

class SchemaRule extends ValueObject
{
    protected array $value;

    /**
     * Constructs a new instance of the class.
     *
     * @param  array  $value  The value array to be assigned.
     *
     * @throws InvalidArgumentException If the value has already been set.
     */
    public function __construct(array $value)
    {

        if (isset($this->value)) {
            throw new InvalidArgumentException(static::IMMUTABLE_MESSAGE);
        }

        $this->value = $value;

        if (isset($this->before)) {
            ($this->before)();
        }

        $this->validate();
        $this->sanitize();
    }

    /**
     * Validates the output rule.
     *
     * This method is used to validate the output rule by checking if the required fields are not empty.
     * It throws an InvalidArgumentException if any of the fields are empty.
     *
     * @throws InvalidArgumentException When the output rule is empty.
     * @throws InvalidArgumentException When the name field is empty.
     * @throws InvalidArgumentException When the rules field is empty.
     * @throws InvalidArgumentException When the description field is empty.
     */
    protected function validate(): void
    {
        if ($this->value() === []) {
            throw new InvalidArgumentException('a output rule cannot be empty.');
        }

        if (
            empty($this->value['name'])
        ) {
            throw new InvalidArgumentException('a name is required.');
        }

        if (
            empty($this->value['rules'])
        ) {
            throw new InvalidArgumentException('rules are required.');
        }

        if (
            empty($this->value['description'])
        ) {
            throw new InvalidArgumentException('a description is required.');
        }

    }

    /**
     * {@inheritdoc}
     */
    public function value(): array
    {
        return $this->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->value;
    }

    /**
     * Sanitizes the data in the SchemaRule.
     */
    protected function sanitize(): void {}

    /**
     * Retrieves the description from the value array.
     *
     * @return string The description from the value array.
     */
    public function getDescription(): string
    {
        return $this->value['description'];
    }

    /**
     * Retrieves the name from the value array.
     *
     * @return string The name from the value array.
     */
    public function getName(): string
    {
        return $this->value['name'];
    }

    /**
     * Retrieves the rules from the value array.
     *
     * @return string The rules from the value array.
     */
    public function getRules(): string
    {
        return $this->value['rules'];
    }
}

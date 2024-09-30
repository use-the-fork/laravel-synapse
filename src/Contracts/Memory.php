<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Contracts;

use UseTheFork\Synapse\ValueObject\Agent\Message;

interface Memory
{
    public function load(): void;

    /**
     * Clears the memory of the application.
     *
     * This method clears the memory that is currently stored in the application.
     */
    public function clear(): void;

    /**
     * Retrieves the memory of the agent
     *
     * @return array The memory object of the agent
     */
    public function get(): array;

    /**
     * Retrieves the memory of the agent as inputs for the prompt.
     *
     * @return array The memory object of the agent
     */
    public function asInputs(): array;

    /**
     * Adds a message to the current memory
     *
     * @param  Message  $message  The message to add to the memory.
     */
    public function add(Message $message): void;

    /**
     * Sets the memory with the given array of messages.
     *
     * @param  array  $messages  The array of messages to be set in the memory.
     */
    public function set(array $messages): void;
}

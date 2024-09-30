<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Contracts\Memory;

use UseTheFork\Synapse\Agents\Integrations\ValueObjects\Message;

interface HasMemory
{
    /**
     * Clears the memory of the application.
     *
     * This method clears the memory that is currently stored in the application.
     */
    public function clearMemory(): void;

    /**
     * Retrieves the memory of the agent
     *
     * @return array The memory object of the agent
     */
    public function getMemory(): array;

    /**
     * Retrieves the memory of the agent as inputs for the prompt.
     *
     * @return array The memory object of the agent
     */
    public function getMemoryAsInputs(): array;

    /**
     * Adds a message to the current memory
     *
     * @param  Message  $message  The message to add to the memory.
     */
    public function addMemory(Message $message): void;

    /**
     * Sets the memory with the given array of messages.
     *
     * @param  array  $messages  The array of messages to be set in the memory.
     */
    public function setMemory(array $messages): void;
}

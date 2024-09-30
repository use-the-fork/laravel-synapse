# Memory

When working with agents it's often desirable for an agent to have "memory" or a record of the conversation so far. Synapse makes this easy for you with built-in traits.

## Getting Started

To get started, you will need to add the `HasMemory` interface to your agent. This interface is required as it bootstraps the agent with the required methods for memory.

```php
<?php

use UseTheFork\Synapse\Agents\Agent;use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIIntegration;

class SimpleAgent extends Agent implements HasMemory
{

    public function resolvePromptView(): string
    {
        return 'synapse::Prompts.SimpleAgentPrompt';
    }

    public function resolveIntegration(): string
    {
        return new OpenAIIntegration();
    }
}
```

Adding memory to your agent exposes the following methods:

- clearMemory() -> Clears the agents memory.
- getMemory() -> Returns the current memory as an `array`.
- getMemoryAsInputs() -> Returns the current memory converted in to inputs for the agents Prompt.
- addMemory(Message $message) -> Adds a message to the memory.
- setMemory(array $messages) -> Sets the memory with the given array of messages.
- resolveMemory() -> Registers the memory type. equivelent of a `__construct()`

Next, you will need to add a trait to provide an implementation for the missing memory methods. Synapse has a trait for some common memory implementations.

Continue reading below to understand more about the specific body type that you need.

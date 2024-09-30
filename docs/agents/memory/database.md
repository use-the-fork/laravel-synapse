# Database Memory

To get started you will need to add the `HasMemory` interface. To your agent without this interface, your Agent will not have memory.

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

TODO: SECTION ON MIGRATIONS

Next, you will need to add the `HasDatabaseMemory` trait to your request. This trait will implement the agents memory using the `AgentMemory` and `Message` models. This means the memory is stored in the database and can be retrieved or modified at will.

```php
<?php

use UseTheFork\Synapse\Agents\Agent;use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIIntegration;

class SimpleAgent extends Agent implements HasMemory
{
    use HasCollectionMemory;

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

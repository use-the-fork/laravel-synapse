# Tools

Tools are functions that an Agent can call to perform desired tasks. Synapse provides a simple way for  your agent to have `tools`. As well as ways for you to create your own tools.

## Getting Started
To get started, you will need to add the `HasTools` interface to your agent as well as the `UseTools` trait.

```php
<?php

use UseTheFork\Synapse\Agents\Agent;
use UseTheFork\Synapse\Agents\Contracts\HasTools;
use UseTheFork\Synapse\Agents\Traits\UseTools;
use UseTheFork\Synapse\Tools\FirecrawlTool;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIIntegration;
use UseTheFork\Synapse\Agents\Integrations\Contracts\Integration;

class SimpleAgent extends Agent implements HasTools
{
    use UseTools;

    public function resolvePromptView(): string
    {
        return 'synapse::Prompts.SimplePrompt';
    }

    public function resolveIntegration(): Integration
    {
        return new OpenAIIntegration();
    }
}
```

Adding tools to your agent exposes the `registerTools` method. This method takes an array of `tools` that the agent can then use. For example below a `CalculatorTool` is exposed. 

```php
<?php

use UseTheFork\Synapse\Agents\Agent;
use UseTheFork\Synapse\Agents\Contracts\HasTools;
use UseTheFork\Synapse\Agents\Traits\UseTools;
use UseTheFork\Synapse\Tools\FirecrawlTool;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIIntegration;
use UseTheFork\Synapse\Agents\Integrations\Contracts\Integration;

class SimpleAgent extends Agent implements HasTools
{
    use UseTools;

    public function resolvePromptView(): string
    {
        return 'synapse::Prompts.SimplePrompt';
    }

    public function resolveIntegration(): Integration
    {
        return new OpenAIIntegration();
    }
    
    public function registerTools(): array
    {
        return [
            new CalculatorTool(),
        ];
    }
}
```

Synapse has many tools that you can use with your own agents. However, if you would like to create your own agent please continue to the Tools section.

Continue reading below on all the tools that come with Synapse.

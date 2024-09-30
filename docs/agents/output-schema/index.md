# Output Schema

It's often desired for an Agent to return a specific Schema. To make this task easier Synapse leverages Laravels built in validation engine and an internal loop to force the Agent to respond in a specific way.

## Getting Started
To get started, you will need to add the `HasOutputSchema` interface to your agent.

```php
<?php

use UseTheFork\Synapse\Agents\Agent;use UseTheFork\Synapse\Agents\Integrations\Contracts\Integration;use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIIntegration;use UseTheFork\Synapse\Contracts\OutputSchema\HasOutputSchema;

class SimpleAgent extends Agent implements HasOutputSchema
{

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

Adding `HasOutputSchema` to your agent exposes the `resolveOutputSchema` method. This method takes an array of `SchemaRule` classes that the agent can then use.

You would then add the `resolveOutputSchema` method and have it return an array of `SchemaRule` classes.

```php
<?php

use UseTheFork\Synapse\Agents\Agent;use UseTheFork\Synapse\Agents\Integrations\Contracts\Integration;use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIIntegration;use UseTheFork\Synapse\Contracts\OutputSchema\HasOutputSchema;

class SimpleAgent extends Agent implements HasOutputSchema
{

    public function resolvePromptView(): string
    {
        return 'synapse::Prompts.SimplePrompt';
    }

    public function resolveIntegration(): Integration
    {
        return new OpenAIIntegration();
    }
    
    public function resolveOutputSchema(): array
    {
        return [
            SchemaRule::make([
                                 'name' => 'final_answer',
                                 'rules' => 'required|string',
                                 'description' => 'The final answer.',
                             ]),
        ];
    }
}
```

> If you are using your own Prompt make sure to also include the `synapse::Parts.OutputSchema` blade file so that the agent knows the desired output format.


## How it works

When `HasOutputSchema` is set the Agent will be forced in to a revalidation loop that follows these steps. 
1. The Agent is instructed to respond with a valid JSON array that follows the requested rules. 
2. The JSON is decoded and then validated using Laravel Validation.
3. If the JSON decoded Or the validation fails a new request is sent to the agent with the failed validation the previous final response and the Schema again.
4. This is repeated until the decode and Validation rules pass.

## The `SchemaRule` class

`SchemaRule` classes have a handy `make` method that takes a `name`, `rules`, and `description`

* name: will be the key that is returned in the final response.
* rules: Is a string of Laravel validation rules.
* description: Is a description of what the data in this key should be.

Example:
```php
  SchemaRule::make([
      'name' => 'country',
      'rules' => 'required|string',
      'description' => 'The country that the text is referring too.',
  ])
```

In the above example we tell the Agent to return an array as it's final answer that must contain a key named `country` that is a string. 



<?php

declare(strict_types=1);

use UseTheFork\Synapse\Agents\Agent;
use UseTheFork\Synapse\Agents\Enums\PromptType;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIConnector;
use UseTheFork\Synapse\Agents\Task;
use UseTheFork\Synapse\Contracts\OutputSchema\HasTools;
use UseTheFork\Synapse\Traits\OutputSchema\UseJsonRuleOutputSchema;
use UseTheFork\Synapse\ValueObject\OutputSchema\SchemaRule;

it('can do a simple query', function () {

    class CollectionMemoryAgent extends Agent
    {
        public function resolveIntegration(): OpenAIConnector
        {
            return new OpenAIConnector;
        }
    }

    class CollectionMemoryTask extends Task implements HasTools
    {
        //        use UseCollectionMemory;
        use UseJsonRuleOutputSchema;

        protected PromptType $promptType = PromptType::CHAT;

        public function resolvePromptView(): string
        {
            return 'synapse::Prompts.SimplePrompt';
        }

        public function resolveOutputSchema(): array
        {
            return [
                SchemaRule::make([
                    'name' => 'answer',
                    'rules' => 'required|string',
                    'description' => 'your final answer to the query.',
                ]),
            ];
        }
    }

    $agent = new CollectionMemoryAgent;
    $task = new CollectionMemoryTask;
    $agentResponse = $agent->invoke(['input' => 'hello this a test'], $task);

    expect($agentResponse)->toBeArray()
        ->and($agentResponse)->toHaveKey('answer');
});

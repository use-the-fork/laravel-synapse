<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use UseTheFork\Synapse\Agents\Agent;
    use UseTheFork\Synapse\Agents\Enums\PromptType;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\Requests\ChatRequest;
    use UseTheFork\Synapse\Agents\Task;
    use UseTheFork\Synapse\Contracts\OutputSchema\HasOutputSchema;
    use UseTheFork\Synapse\Services\Serper\Requests\SerperSearchRequest;
use UseTheFork\Synapse\Tools\SerperTool;
    use UseTheFork\Synapse\Traits\OutputSchema\UseJsonRuleOutputSchema;
    use UseTheFork\Synapse\ValueObject\OutputSchema\SchemaRule;
    use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIConnector;

test('Connects', function (): void {

    class OpenAiTestAgent extends Agent
    {
        public function resolveIntegration(): OpenAIConnector
        {
            return new OpenAIConnector();
        }
    }

    class OpenAiTestTask  extends Task implements HasOutputSchema
    {
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

    MockClient::global([
        ChatRequest::class => MockResponse::fixture('Integrations/OpenAI/Connects'),
    ]);

    $agent = new OpenAiTestAgent;
    $task = new OpenAiTestTask();
    $agentResponse = $agent->invoke(['input' => 'hello!'], $task);

    expect($agentResponse)->toBeArray()
        ->and($agentResponse)->toHaveKey('answer');
});

test('uses tools', function (): void {

    class OpenAiToolTestAgent extends Agent
    {
        protected string $promptView = 'synapse::Prompts.SimplePrompt';

        protected function registerOutputSchema(): array
        {
            return [
                SchemaRule::make([
                    'name' => 'answer',
                    'rules' => 'required|string',
                    'description' => 'your final answer to the query.',
                ]),
            ];
        }

        protected function registerTools(): array
        {
            return [
                new SerperTool,
            ];
        }
    }

    MockClient::global([
        ChatRequest::class => function (PendingRequest $pendingRequest): \Saloon\Http\Faking\Fixture {
            $count = count($pendingRequest->body()->get('messages'));

            return MockResponse::fixture("openai/uses-tools/message-{$count}");
        },
        SerperSearchRequest::class => MockResponse::fixture('openai/uses-tools/serper'),
    ]);

    $agent = new OpenAiToolTestAgent;
    $agentResponse = $agent->handle(['input' => 'search google for the current president of the united states.']);

    expect($agentResponse)->toBeArray()
        ->and($agentResponse)->toHaveKey('answer');
});

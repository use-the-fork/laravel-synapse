<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use UseTheFork\Synapse\Agents\Agent;
use UseTheFork\Synapse\Agents\AgentTaskResponse;
use UseTheFork\Synapse\Agents\Enums\PromptType;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIConnector;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\Requests\ChatRequest;
use UseTheFork\Synapse\Agents\Task;
use UseTheFork\Synapse\Contracts\Agent\Task\HasOutputSchema;
use UseTheFork\Synapse\Services\Serper\Requests\SerperSearchRequest;
use UseTheFork\Synapse\Tools\SerperTool;
use UseTheFork\Synapse\Traits\OutputSchema\UseJsonRuleOutputSchema;
use UseTheFork\Synapse\ValueObject\OutputSchema\SchemaRule;

test('Connects', function (): void {

    class OpenAiTestAgent extends Agent
    {
        public function resolveIntegration(): OpenAIConnector
        {
            return new OpenAIConnector;
        }
    }

    class OpenAiTestTask extends Task implements HasOutputSchema
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

        public function resolveTools(): array
        {
            return [];
        }
    }

    MockClient::global([
        ChatRequest::class => MockResponse::fixture('Integrations/OpenAI/Connects'),
    ]);

    $agent = new OpenAiTestAgent;
    $task = new OpenAiTestTask;
    $agentTaskResponse = $agent->invoke(['input' => 'hello!'], $task);

    expect($agentTaskResponse)->toBeInstanceOf(AgentTaskResponse::class)
        ->and($agentTaskResponse->getFinalResponse())->toHaveKey('answer');
});

test('Uses Tools', function (): void {

    class OpenAiTestAgent extends Agent
    {
        public function resolveIntegration(): OpenAIConnector
        {
            return new OpenAIConnector;
        }
    }

    class OpenAiTestToolTask extends Task implements HasOutputSchema
    {
        use UseJsonRuleOutputSchema;

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

        public function resolveTools(): array
        {
            return [
                new SerperTool,
            ];
        }
    }

    MockClient::global([
        ChatRequest::class => function (PendingRequest $pendingRequest): \Saloon\Http\Faking\Fixture {
            $count = count($pendingRequest->body()->get('messages'));

            return MockResponse::fixture("Integrations/OpenAI/Connects/UsesTools-{$count}");
        },
        SerperSearchRequest::class => MockResponse::fixture('Integrations/OpenAI/Connects/UsesToolsSerper'),
    ]);

    $agent = new OpenAiTestAgent;
    $task = new OpenAiTestToolTask;
    $agentTaskResponse = $agent->invoke(['input' => 'search google for the current president of the united states.'], $task);

    expect($agentTaskResponse->getFinalResponse())->toBeArray()
        ->and($agentTaskResponse->getFinalResponse())->toHaveKey('answer');
});

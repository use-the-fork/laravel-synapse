<?php

declare(strict_types=1);

    use Saloon\Http\Connector;
    use Saloon\Http\Faking\MockClient;
    use Saloon\Http\Faking\MockResponse;
    use Saloon\Http\PendingRequest;
    use UseTheFork\Synapse\Agents\Agent;
    use UseTheFork\Synapse\Agents\Integrations\Claude\ClaudeAIConnector;
    use UseTheFork\Synapse\Agents\Integrations\Claude\Requests\ChatRequest;
    use UseTheFork\Synapse\Agents\Integrations\Claude\Requests\ValidateOutputRequest;
    use UseTheFork\Synapse\Services\Serper\Requests\SerperSearchRequest;
    use UseTheFork\Synapse\Tools\SerperTool;
    use UseTheFork\Synapse\ValueObject\OutputSchema\SchemaRule;

    test('Connects', function () {

    class ClaudeTestAgent extends Agent
    {
        protected string $promptView = 'synapse::Prompts.SimplePrompt';

        protected function registerIntegration(): Connector
        {
            return new ClaudeAIConnector;
        }

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
    }

    MockClient::global([
        ChatRequest::class => MockResponse::fixture('claude/simple'),
    ]);

    $agent = new ClaudeTestAgent;
    $agentResponse = $agent->handle(['input' => 'hello!']);

    expect($agentResponse)->toBeArray()
        ->and($agentResponse)->toHaveKey('answer');
});

test('uses tools', function () {

    class ClaudeToolTestAgent extends Agent
    {
        protected string $promptView = 'synapse::Prompts.SimplePrompt';

        protected function registerIntegration(): Connector
        {
            return new ClaudeAIConnector;
        }

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
        ChatRequest::class => function (PendingRequest $pendingRequest) {
            $count = count($pendingRequest->body()->get('messages'));

            return MockResponse::fixture("claude/uses-tools/message-{$count}");
        },
        ValidateOutputRequest::class => MockResponse::fixture('claude/uses-tools/validate'),
        SerperSearchRequest::class => MockResponse::fixture('claude/uses-tools/serper'),
    ]);

    $agent = new ClaudeToolTestAgent;
    $agentResponse = $agent->handle(['input' => 'search google for the current president of the united states.']);

    expect($agentResponse)->toBeArray()
        ->and($agentResponse)->toHaveKey('answer');
});

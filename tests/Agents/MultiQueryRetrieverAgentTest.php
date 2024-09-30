<?php

declare(strict_types=1);

	use Saloon\Http\Faking\MockClient;
	use Saloon\Http\Faking\MockResponse;
	use Saloon\Http\PendingRequest;
	use UseTheFork\Synapse\Agents\Integrations\OpenAI\Requests\ChatRequest;
	use UseTheFork\Synapse\Templates\MultiQueryRetrieverAgent;

	it('can run the Multi Query Retriever Agent.', function () {

    MockClient::global([
        ChatRequest::class => function (PendingRequest $pendingRequest) {
            $count = count($pendingRequest->body()->get('messages'));

            return MockResponse::fixture("agents/multi-query-retriever-agent/message-{$count}");
        },
    ]);

    $agent = new MultiQueryRetrieverAgent;

    $agentResponse = $agent->handle(['queryCount' => '5', 'input' => 'What gym activities do you recommend for heart health?']);

    expect($agentResponse)->toBeArray()
        ->and($agentResponse)->toHaveKey('answer')
        ->and($agentResponse['answer'])->toBeArray()
        ->and($agentResponse['answer'])->toHaveCount(5);

});

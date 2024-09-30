<?php

declare(strict_types=1);

use UseTheFork\Synapse\Templates\KnowledgeGraphExtractionAgent;

it('can run the Knowledge Graph Extraction Agent.', function (): void {

    //    MockClient::global([
    //        ChatRequest::class => function (PendingRequest $pendingRequest) {
    //            $count = count($pendingRequest->body()->get('messages'));
    //
    //            return MockResponse::fixture("agents/chat-rephrase-agent/message-{$count}");
    //        },
    //    ]);

    $agent = new KnowledgeGraphExtractionAgent;

    $agentResponse = $agent->handle(['input' => 'The ML2000 Series is an ANSI/BHMA Grade 1 mortise lock designed to meet the rigors of high-traffic, abusive environments. Constructed of heavy-gauge steel with unique, patented features and a full range of trim and functions including status indicators and Motorized Electric Latch Retraction (MELR), the versatility and reliability of this lock complement any application.']);

    dd($agentResponse);

    expect($agentResponse)->toBeArray()
        ->and($agentResponse)->toHaveKey('standalone_question')
        ->and($agentResponse['standalone_question'] == 'What are some methods to improve heart health?')->toBeTrue();
});

<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use UseTheFork\Synapse\Agents\Enums\ResponseType;
use UseTheFork\Synapse\Agents\Enums\Role;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\Requests\ChatRequest;
use UseTheFork\Synapse\Agents\Integrations\ValueObjects\Message;
use UseTheFork\Synapse\Templates\ChatRephraseAgent;

it('can run the Chat Rephrase Agent.', function (): void {

    MockClient::global([
        ChatRequest::class => function (PendingRequest $pendingRequest): \Saloon\Http\Faking\Fixture {
            $count = count($pendingRequest->body()->get('messages'));

            return MockResponse::fixture("agents/chat-rephrase-agent/message-{$count}");
        },
    ]);

    $agent = new ChatRephraseAgent;
    $agent->addMessageToMemory(Message::make([
        'role' => Role::USER,
        'content' => 'What gym activities do you recommend?',
    ]));

    $agent->addMessageToMemory(Message::make([
        'role' => Role::ASSISTANT,
        'finish_reason' => ResponseType::STOP,
        'content' => 'For a balanced gym routine, I recommend incorporating a mix of cardio, strength training, and flexibility exercises. Start with 20-30 minutes of cardiovascular activities like running on the treadmill, cycling, or using the elliptical to improve heart health and endurance. Follow this with strength training exercises, such as weightlifting or bodyweight exercises like squats, deadlifts, bench presses, and pull-ups, to build muscle and enhance metabolism. Finish with 10-15 minutes of stretching or yoga to improve flexibility, reduce the risk of injury, and aid muscle recovery. This combination ensures a comprehensive workout that targets overall fitness, strength, and flexibility.',
    ]));

    $agentResponse = $agent->handle(['input' => 'improve heart health?']);

    expect($agentResponse)->toBeArray()
        ->and($agentResponse)->toHaveKey('standalone_question')
        ->and($agentResponse['standalone_question'] == 'What are some methods to improve heart health?')->toBeTrue();
});

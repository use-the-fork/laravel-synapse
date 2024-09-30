<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Integrations\OpenAI\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use UseTheFork\Synapse\Agents\Enums\ResponseType;
use UseTheFork\Synapse\Agents\Enums\Role;
use UseTheFork\Synapse\Agents\Integrations\ValueObjects\Message;
use UseTheFork\Synapse\Agents\Integrations\ValueObjects\Response as IntegrationResponse;

class ValidateOutputRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly Message $message,
        public readonly array $extraAgentArgs = []
    ) {}

    public function resolveEndpoint(): string
    {
        return '/chat/completions';
    }

    public function defaultBody(): array
    {
        $model = config('synapse.integrations.openai.validate_model');

        $userMessage = $this->message->content();

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => Role::USER,
                    'content' => $userMessage,
                ],
            ],
        ];

        return [
            ...$payload,
            ...$this->extraAgentArgs,
        ];
    }

    public function createDtoFromResponse(Response $response): IntegrationResponse
    {
        $data = $response->array();
        $message = $data['choices'][0]['message'] ?? [];
        $message['finish_reason'] = ResponseType::STOP;

        return IntegrationResponse::makeOrNull($message);
    }
}

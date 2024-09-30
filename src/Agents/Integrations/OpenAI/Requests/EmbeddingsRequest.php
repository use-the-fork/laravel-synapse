<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Integrations\OpenAI\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use UseTheFork\Synapse\Agents\Integrations\ValueObjects\EmbeddingResponse;

class EmbeddingsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $input,
        public readonly array $extraAgentArgs = []
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/embeddings';
    }

    public function defaultBody(): array
    {
        $model = config('synapse.integrations.openai.embedding_model');

        return [
            'model' => $model,
            'input' => $this->input,
            ...$this->extraAgentArgs,
        ];
    }

    public function createDtoFromResponse(Response $response): EmbeddingResponse
    {
        $data = $response->array();
        //        dd($data);

        return EmbeddingResponse::makeOrNull($data);
    }
}

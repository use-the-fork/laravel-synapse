<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Integrations\OpenAI;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\Requests\ChatRequest;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\Requests\EmbeddingsRequest;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\Requests\ValidateOutputRequest;
use UseTheFork\Synapse\Agents\Integrations\ValueObjects\EmbeddingResponse;
use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\Contracts\Agent\HasIntegration;
use UseTheFork\Synapse\ValueObject\Agent\Message;
use UseTheFork\Synapse\ValueObject\Agent\Response;

// implementation of https://github.com/bootstrapguru/dexor/blob/main/app/Integrations/OpenAI/OpenAIConnector.php
class OpenAIConnector extends Connector implements HasIntegration
{
    use AcceptsJson, AlwaysThrowOnErrors, HasTimeout;

    protected int $connectTimeout = 60;

    protected int $requestTimeout = 120;

    /**
     * Handles the request to generate a chat response.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function handleCompletion(PendingAgentTask $pendingAgentTask): Response
    {

        return $this->send(new ChatRequest($pendingAgentTask->getTask()->compilePrompt($pendingAgentTask->getInputs()), $pendingAgentTask->getTools(), $pendingAgentTask->getExtraAgentArgs()))->dto();
    }

    /**
     * Forces a model to output its response in a specific format.
     *
     * @param  Message  $message  The chat message that is used for validation.
     * @param  array  $extraAgentArgs  Extra arguments to be passed to the agent.
     * @return Response The response from the chat request.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function handleValidationCompletion(PendingAgentTask $pendingAgentTask): Response
    {
        return $this->send(new ValidateOutputRequest($message, $extraAgentArgs))->dto();
    }

    public function createEmbeddings(string $input, array $extraAgentArgs = []): EmbeddingResponse
    {
        return $this->send(new EmbeddingsRequest($input, $extraAgentArgs))->dto();
    }

    public function resolveBaseUrl(): string
    {
        return 'https://api.openai.com/v1';

    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.config('synapse.integrations.openai.key'),
        ];
    }
}

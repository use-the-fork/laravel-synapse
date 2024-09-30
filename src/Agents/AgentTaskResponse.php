<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents;

use UseTheFork\Synapse\Contracts\Agent\HasIntegration;
use UseTheFork\Synapse\Traits\Agent\HasMiddleware;
use UseTheFork\Synapse\Traits\Agent\InvokesRequests;
use UseTheFork\Synapse\Traits\Bootable;
use UseTheFork\Synapse\ValueObject\Agent\Response;

class AgentTaskResponse
{
    protected mixed $finalResponse;

    public function __construct(
        public Response $response,
        public string $rawResponse,
    ) {
        $this->finalResponse = $this->rawResponse;
    }

    /**
     * @param mixed $finalResponse
     */
    public function setFinalResponse(mixed $finalResponse): void
    {
        $this->finalResponse = $finalResponse;
    }

    /**
     * @return mixed
     */
    public function getFinalResponse(): mixed
    {
        return $this->finalResponse;
    }

}

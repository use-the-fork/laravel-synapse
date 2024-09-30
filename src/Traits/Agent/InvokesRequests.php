<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\Agent;

use LogicException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use UseTheFork\Synapse\Agents\AgentTaskResponse;
use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\Agents\Task;

trait InvokesRequests
{
    use UseIntegration;

    public function invoke(array $input, Task $task): AgentTaskResponse
    {

        try {
            // sets the integration that this Agent is using.
            $this->integration = $this->resolveIntegration();

            $pendingAgentTask = $this->createPendingAgentTask($task, $input);

            $response = $this->integration()->handleCompletion($pendingAgentTask);

            // We'll execute the response pipeline now so that all the response
            // middleware can be run before we throw any exceptions.

            $response = $pendingAgentTask->executeCompleteTaskPipeline($response);

            return $response;
        } catch (FatalRequestException|RequestException $exception) {
            // We'll attempt to get the response from the exception. We'll only be able
            // to do this if the exception was a "RequestException".

            $exceptionResponse = $exception instanceof RequestException ? $exception->getResponse() : null;

            // If the exception is a FatalRequestException, we'll execute the fatal pipeline
            if ($exception instanceof FatalRequestException) {
                $exception->getPendingRequest()->executeFatalPipeline($exception);
            }
        }

        throw new LogicException('The request was not sent.');
    }

    /**
     * Create a new PendingRequest
     */
    public function createPendingAgentTask(Task $task, $input): PendingAgentTask
    {
        return new PendingAgentTask($this, $task, $input);
    }
}

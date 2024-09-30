<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\Agent;

use LogicException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use UseTheFork\Synapse\Agents\AgentTaskResponse;
use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\Agents\Task;
use UseTheFork\Synapse\Enums\Agent\ResponseType;
use UseTheFork\Synapse\Exceptions\UnknownFinishReasonException;

trait InvokesRequests
{
    use UseIntegration;

    public function invoke(array $input, Task $task): AgentTaskResponse
    {

        try {
            // sets the integration that this Agent is using.
            $this->integration = $this->resolveIntegration();

            $pendingAgentTask = $this->createPendingAgentTask($task, $input);

            $continueTask = true;
            while ($continueTask) {
                $pendingAgentTask = $pendingAgentTask->executeStartIterationPipeline($pendingAgentTask);

                $pendingAgentTask = $this->integration()->handleCompletion($pendingAgentTask);

                switch (ResponseType::from($pendingAgentTask->currentTaskResponse()->getFinishReason())) {
                    case ResponseType::TOOL_CALL:
                        $pendingAgentTask = $pendingAgentTask->getTask()->handleTools($pendingAgentTask);
                        break;
                    case ResponseType::STOP:
                        $continueTask = false;
                        break;
                    default:
                        throw new UnknownFinishReasonException("{$chatResponse->getFinishReason()} is not a valid finish reason.");
                }

                $pendingAgentTask = $pendingAgentTask->executeEndIterationPipeline($pendingAgentTask);

            }

            // We'll execute the response pipeline now so that all the response
            // middleware can be run before we throw any exceptions.
            $response = $pendingAgentTask->executeCompleteTaskPipeline($pendingAgentTask->currentTaskResponse());
            return $response;
        } catch (FatalRequestException|RequestException $exception) {
            // We'll attempt to get the response from the exception. We'll only be able
            // to do this if the exception was a "RequestException".

            dd($exception);

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

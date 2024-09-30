<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents;

use Throwable;
use UseTheFork\Synapse\Agents\Enums\ResponseType;
use UseTheFork\Synapse\Agents\PendingAgentTask\BootAgentAndTask;
use UseTheFork\Synapse\Agents\PendingAgentTask\BootTraits;
use UseTheFork\Synapse\Agents\PendingAgentTask\MergeProperties;
use UseTheFork\Synapse\Exceptions\UnknownFinishReasonException;
use UseTheFork\Synapse\Traits\Agent\HasMiddleware;
use UseTheFork\Synapse\ValueObject\Agent\Response;

class PendingAgentTask
{
    public $integration;
    public $registered_tools;
    use HasMiddleware;

    /**
     * The agent making the task.
     */
    protected Agent $agent;

    protected Task $task;

    protected array $input;

    protected array $extraAgentArgs = [];

    /**
     * Initializes the agent.
     *
     * This method is called upon object creation to initialize the agent.
     * It is responsible for performing any necessary setup tasks.
     *
     * @throws Throwable
     */
    public function __construct(Agent $agent, Task $task, array $input)
    {

        // Set the base properties
        $this->agent = $agent;
        $this->task = $task;
        $this->input = $input;

        $this
            ->tap(new BootTraits)
            ->tap(new MergeProperties)
            ->tap(new BootAgentAndTask);

        // Finally, we will execute the request middleware pipeline which will
        // process the middleware in the order we added it.
        $this->middleware()->executeStartTaskPipeline($this);
    }

    /**
     * Handles the user input and extra agent arguments to retrieve the response.
     *
     * @param  array|null  $input  The input array.
     * @param  array|null  $extraAgentArgs  The extra agent arguments array.
     * @return array The validated response array.
     *
     * @throws Throwable
     */
    public function handle(?array $input, ?array $extraAgentArgs = []): array
    {
        $response = $this->getAnswer($input, $extraAgentArgs);

        $this->log('Start validation', [$response]);

        return $this->doValidate($response);
    }

    /**
     * @throws Throwable
     */
    protected function getAnswer(?array $input, ?array $extraAgentArgs = []): string
    {
        while (true) {
            $this->loadMemory();

            $prompt = $this->parsePrompt(
                $this->getPrompt($input)
            );

            $this->log('Call Integration');

            // Create the Chat request we will be sending.
            $chatResponse = $this->integration->handleCompletion($prompt, $this->registered_tools, $extraAgentArgs);
            $this->log("Finished Integration with {$chatResponse->finishReason()}");

            switch ($chatResponse->finishReason()) {
                case ResponseType::TOOL_CALL:
                    $this->handleTools($chatResponse);
                    break;
                case ResponseType::STOP:
                    return $chatResponse->content();
                default:
                    throw new UnknownFinishReasonException("{$chatResponse->finishReason()} is not a valid finish reason.");
            }
        }
    }

    /**
     * Execute the Complete Task pipeline.
     */
    public function executeCompleteTaskPipeline(AgentTaskResponse $response): AgentTaskResponse
    {
        return $this->middleware()->executeCompleteTaskPipeline($response);
    }

    /**
     * Get the task.
     */
    public function addInput($key, $value): self
    {
        $this->input[$key] = $value;

        return $this;
    }

    /**
     * Get the task.
     */
    public function getInputs(): array
    {
        return $this->input;
    }

    public function getTools(): array
    {
        return [];
    }

    public function getExtraAgentArgs(): array
    {
        return $this->extraAgentArgs;
    }

    /**
     * Get the task.
     */
    public function getTask(): Task
    {
        return $this->task;
    }

    /**
     * Get the agent.
     */
    public function getAgent(): Agent
    {
        return $this->agent;
    }

    /**
     * Tap into the agent
     *
     * @return $this
     */
    protected function tap(callable $callable): static
    {
        $callable($this);

        return $this;
    }
}

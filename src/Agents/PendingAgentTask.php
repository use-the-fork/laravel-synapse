<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents;

use Throwable;
use UseTheFork\Synapse\Agents\PendingAgentTask\BootAgentAndTask;
use UseTheFork\Synapse\Agents\PendingAgentTask\BootTraits;
use UseTheFork\Synapse\Agents\PendingAgentTask\MergeProperties;
use UseTheFork\Synapse\Traits\Agent\HasMiddleware;
use UseTheFork\Synapse\ValueObject\Agent\Message;

class PendingAgentTask
{
    use HasMiddleware;

    /**
     * The agent making the task.
     */
    protected Agent $agent;

    protected Task $task;

    protected ?AgentTaskResponse $currentTaskResponse = null;

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
     * Execute the Complete Task pipeline.
     */
    public function executeCompleteTaskPipeline(AgentTaskResponse $response): AgentTaskResponse
    {
        return $this->middleware()->executeCompleteTaskPipeline($response);
    }

    public function executeStartIterationPipeline(PendingAgentTask $pendingAgentTask): PendingAgentTask
    {
        return $this->middleware()->executeStartIterationPipeline($pendingAgentTask);
    }

    public function executeEndIterationPipeline(PendingAgentTask $pendingAgentTask): PendingAgentTask
    {
        return $this->middleware()->executeEndIterationPipeline($pendingAgentTask);
    }

    public function setCurrentTaskResponse(AgentTaskResponse $currentTaskResponse): PendingAgentTask
    {
        $this->currentTaskResponse = $currentTaskResponse;

        return $this;
    }

    public function currentTaskResponse(): ?AgentTaskResponse
    {
        return $this->currentTaskResponse;
    }

    /**
     * Get the task.
     */
    public function addInput($key, $value): PendingAgentTask
    {
        $this->input[$key] = $value;

        return $this;
    }

    /**
     * Get the task.
     */
    public function addMemory(Message $messageData): PendingAgentTask
    {
        $this->getTask()->memory()->add($messageData);

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
        return $this->task->tools();
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

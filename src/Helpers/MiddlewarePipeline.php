<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Helpers;

use Saloon\Enums\PipeOrder;
use Saloon\Exceptions\Request\FatalRequestException;
use UseTheFork\Synapse\Agents\AgentTaskResponse;
use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\ValueObject\Agent\Response;

class MiddlewarePipeline
{
    /**
     * Boot Task Pipeline
     */
    protected Pipeline $bootTaskPipeline;

    protected Pipeline $startIterationPipeline;
    protected Pipeline $endIterationPipeline;

    /**
     * Start Task Pipeline
     */
    protected Pipeline $startTaskPipeline;

    /**
     * Complete Task Pipeline
     */
    protected Pipeline $completeTaskPipeline;

    /**
     * Fatal Pipeline
     */
    protected Pipeline $fatalPipeline;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->bootTaskPipeline = new Pipeline;

        $this->startIterationPipeline = new Pipeline;
        $this->endIterationPipeline = new Pipeline;


        $this->startTaskPipeline = new Pipeline;
        $this->completeTaskPipeline = new Pipeline;

        $this->fatalPipeline = new Pipeline;
    }

    /**
     * Add a middleware before the task starts
     *
     * @param  callable(PendingAgentTask): (PendingAgentTask|void)  $callable
     * @return $this
     */
    public function onBootTask(callable $callable, ?string $name = null, ?PipeOrder $order = null): static
    {
        $this->bootTaskPipeline->pipe(static function (PendingAgentTask $pendingAgentTask) use ($callable): PendingAgentTask {
            $result = $callable($pendingAgentTask);

            if ($result instanceof PendingAgentTask) {
                return $result;
            }

            return $pendingAgentTask;
        }, $name, $order);

        return $this;
    }


    public function onStartIteration(callable $callable, ?string $name = null, ?PipeOrder $order = null): static
    {
        $this->startIterationPipeline->pipe(static function (PendingAgentTask $pendingAgentTask) use ($callable): PendingAgentTask {
            $result = $callable($pendingAgentTask);

            if ($result instanceof PendingAgentTask) {
                return $result;
            }

            return $pendingAgentTask;
        }, $name, $order);

        return $this;
    }

    public function onEndIteration(callable $callable, ?string $name = null, ?PipeOrder $order = null): static
    {
        $this->endIterationPipeline->pipe(static function (PendingAgentTask $pendingAgentTask) use ($callable): PendingAgentTask {
            $result = $callable($pendingAgentTask);

            if ($result instanceof PendingAgentTask) {
                return $result;
            }

            return $pendingAgentTask;
        }, $name, $order);

        return $this;
    }

    /**
     *
     * Add a middleware before the task starts
     *
     * @param  callable(PendingAgentTask): (PendingAgentTask|void)  $callable
     * @return $this
     */
    public function onStartTask(callable $callable, ?string $name = null, ?PipeOrder $order = null): static
    {
        $this->startTaskPipeline->pipe(static function (PendingAgentTask $pendingAgentTask) use ($callable): PendingAgentTask {
            $result = $callable($pendingAgentTask);

            if ($result instanceof PendingAgentTask) {
                return $result;
            }

            return $pendingAgentTask;
        }, $name, $order);

        return $this;
    }

    /**
     * Add a middleware after the Agent responds with a final answer.
     *
     * @param  callable(AgentTaskResponse): (AgentTaskResponse|void)  $callable
     * @return $this
     */
    public function onCompleteTask(callable $callable, ?string $name = null, ?PipeOrder $order = null): static
    {
        /**
         * For some reason, PHP is not destructing non-static Closures, or 'things' using non-static Closures, correctly, keeping unused objects intact.
         * Using a *static* Closure, or re-binding it to an empty, anonymous class/object is a workaround for the issue.
         * If we don't, things using the MiddlewarePipeline, in turn, won't destruct.
         * Concretely speaking, for Saloon, this means that the Connector will *not* get destructed, and thereby also not the underlying client.
         * Which in turn leaves open file handles until the process terminates.
         *
         * Do note that this is entirely about our *wrapping* Closure below.
         * The provided callable doesn't affect the MiddlewarePipeline.
         */
        $this->completeTaskPipeline->pipe(static function (AgentTaskResponse $response) use ($callable): AgentTaskResponse {
            $result = $callable($response);

            return $result instanceof AgentTaskResponse ? $result : $response;
        }, $name, $order);

        return $this;
    }

    /**
     * Add a middleware to run on fatal errors
     *
     * @param  callable(FatalRequestException): (void)  $callable
     * @return $this
     */
    public function onFatalException(callable $callable, ?string $name = null, ?PipeOrder $order = null): static
    {
        /**
         * For some reason, PHP is not destructing non-static Closures, or 'things' using non-static Closures, correctly, keeping unused objects intact.
         * Using a *static* Closure, or re-binding it to an empty, anonymous class/object is a workaround for the issue.
         * If we don't, things using the MiddlewarePipeline, in turn, won't destruct.
         * Concretely speaking, for Saloon, this means that the Connector will *not* get destructed, and thereby also not the underlying client.
         * Which in turn leaves open file handles until the process terminates.
         *
         * Do note that this is entirely about our *wrapping* Closure below.
         * The provided callable doesn't affect the MiddlewarePipeline.
         */
        $this->fatalPipeline->pipe(static function (FatalRequestException $throwable) use ($callable): FatalRequestException {
            $callable($throwable);

            return $throwable;
        }, $name, $order);

        return $this;
    }


    /**
     * Process the request pipeline.
     */
    public function executeStartTaskPipeline(PendingAgentTask $pendingAgentTask): PendingAgentTask
    {
        return $this->startTaskPipeline->process($pendingAgentTask);
    }

    public function executeStartIterationPipeline(PendingAgentTask $pendingAgentTask): PendingAgentTask
    {
        return $this->startIterationPipeline->process($pendingAgentTask);
    }

    public function executeEndIterationPipeline(PendingAgentTask $pendingAgentTask): PendingAgentTask
    {
        return $this->endIterationPipeline->process($pendingAgentTask);
    }

    /**
     * Process the complete task pipeline.
     */
    public function executeCompleteTaskPipeline(AgentTaskResponse $response): AgentTaskResponse
    {
        return $this->completeTaskPipeline->process($response);
    }

    /**
     * Process the fatal pipeline.
     *
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     */
    public function executeFatalPipeline(FatalRequestException $throwable): void
    {
        $this->fatalPipeline->process($throwable);
    }

    /**
     * Merge in another middleware pipeline.
     *
     * @return $this
     */
    public function merge(MiddlewarePipeline $middlewarePipeline): static
    {
        $startIterationPipes = array_merge(
            $this->getStartIterationPipeline()->getPipes(),
            $middlewarePipeline->getStartIterationPipeline()->getPipes()
        );

        $endIterationPipes = array_merge(
            $this->getEndIterationPipeline()->getPipes(),
            $middlewarePipeline->getEndIterationPipeline()->getPipes()
        );

        $startTaskPipes = array_merge(
            $this->getStartTaskPipelinePipeline()->getPipes(),
            $middlewarePipeline->getStartTaskPipelinePipeline()->getPipes()
        );

        $completeTaskPipes = array_merge(
            $this->getCompleteTaskPipelinePipeline()->getPipes(),
            $middlewarePipeline->getCompleteTaskPipelinePipeline()->getPipes()
        );

        $fatalPipes = array_merge(
            $this->getFatalPipeline()->getPipes(),
            $middlewarePipeline->getFatalPipeline()->getPipes()
        );

        $this->startIterationPipeline->setPipes($startIterationPipes);
        $this->endIterationPipeline->setPipes($endIterationPipes);

        $this->startTaskPipeline->setPipes($startTaskPipes);
        $this->startTaskPipeline->setPipes($startTaskPipes);
        $this->completeTaskPipeline->setPipes($completeTaskPipes);
        $this->fatalPipeline->setPipes($fatalPipes);

        return $this;
    }

    public function getStartIterationPipeline(): Pipeline
    {
        return $this->startIterationPipeline;
    }

    public function getEndIterationPipeline(): Pipeline
    {
        return $this->endIterationPipeline;
    }

    /**
     * Get the request pipeline
     */
    public function getStartTaskPipelinePipeline(): Pipeline
    {
        return $this->startTaskPipeline;
    }

    /**
     * Get the response pipeline
     */
    public function getCompleteTaskPipelinePipeline(): Pipeline
    {
        return $this->completeTaskPipeline;
    }

    /**
     * Get the fatal pipeline
     */
    public function getFatalPipeline(): Pipeline
    {
        return $this->fatalPipeline;
    }
}

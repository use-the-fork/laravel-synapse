<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Helpers;

use Saloon\Enums\PipeOrder;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Response;
use UseTheFork\Synapse\Agents\PendingAgentTask;

class MiddlewarePipeline
{
    /**
     * Request Pipeline
     */
    protected Pipeline $startTaskPipeline;

    /**
     * Response Pipeline
     */
    protected Pipeline $responsePipeline;

    /**
     * Fatal Pipeline
     */
    protected Pipeline $fatalPipeline;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->startTaskPipeline = new Pipeline;
        $this->responsePipeline = new Pipeline;
        $this->fatalPipeline = new Pipeline;
    }

    /**
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
     * Add a middleware after the request is sent
     *
     * @param  callable(\Saloon\Http\Response): (\Saloon\Http\Response|void)  $callable
     * @return $this
     */
    public function onTaskComplete(callable $callable, ?string $name = null, ?PipeOrder $order = null): static
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
        $this->responsePipeline->pipe(static function (Response $response) use ($callable): Response {
            $result = $callable($response);

            return $result instanceof Response ? $result : $response;
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

    /**
     * Process the response pipeline.
     */
    public function executeResponsePipeline(Response $response): Response
    {
        return $this->responsePipeline->process($response);
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
        $requestPipes = array_merge(
            $this->getStartTaskPipelinePipeline()->getPipes(),
            $middlewarePipeline->getStartTaskPipelinePipeline()->getPipes()
        );

        $responsePipes = array_merge(
            $this->getResponsePipeline()->getPipes(),
            $middlewarePipeline->getResponsePipeline()->getPipes()
        );

        $fatalPipes = array_merge(
            $this->getFatalPipeline()->getPipes(),
            $middlewarePipeline->getFatalPipeline()->getPipes()
        );

        $this->startTaskPipeline->setPipes($requestPipes);
        $this->responsePipeline->setPipes($responsePipes);
        $this->fatalPipeline->setPipes($fatalPipes);

        return $this;
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
    public function getResponsePipeline(): Pipeline
    {
        return $this->responsePipeline;
    }

    /**
     * Get the fatal pipeline
     */
    public function getFatalPipeline(): Pipeline
    {
        return $this->fatalPipeline;
    }
}

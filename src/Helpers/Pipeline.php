<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Helpers;

    use UseTheFork\Synapse\Data\Pipe;
    use UseTheFork\Synapse\Enums\PipeOrder;
    use UseTheFork\Synapse\Exceptions\DuplicatePipeNameException;

    /*
     * Credits to https://github.com/saloonphp/saloon/blob/v3/src/Helpers/Pipeline.php
     */
    class Pipeline
    {
        /**
         * The pipes in the pipeline.
         *
         * @var array<Pipe>
         */
        protected array $pipes = [];

        /**
         * Add a pipe to the pipeline
         *
         * @param callable(mixed $payload): (mixed) $callable
         * @return $this
         */
        public function pipe(callable $callable, ?string $name = null, ?PipeOrder $order = null): static
        {
            $pipe = new Pipe($callable, $name, $order);

            if (is_string($name) && $this->pipeExists($name)) {
                throw new DuplicatePipeNameException($name);
            }

            $this->pipes[] = $pipe;

            return $this;
        }

        /**
         * Process the pipeline.
         */
        public function process(mixed $payload): mixed
        {
            foreach ($this->sortPipes() as $pipe) {
                $payload = call_user_func($pipe->callable, $payload);
            }

            return $payload;
        }

        /**
         * Sort the pipes based on the "order" classes
         *
         * @return array<Pipe>
         */
        protected function sortPipes(): array
        {
            $firstPipes = [];
            $nullPipes = [];
            $lastPipes = [];

            // We'll simply loop through each pipe and add them to their respective
            // arrays based on the order type. We'll then merge the arrays.

            foreach ($this->pipes as $pipe) {
                match ($pipe->pipeOrder) {
                    PipeOrder::FIRST => $firstPipes[] = $pipe,
                    null => $nullPipes[] = $pipe,
                    PipeOrder::LAST => $lastPipes[] = $pipe,
                };
            }

            return array_merge($firstPipes, $nullPipes, $lastPipes);
        }

        /**
         * Set the pipes on the pipeline.
         *
         * @param array<Pipe> $pipes
         * @return $this
         */
        public function setPipes(array $pipes): static
        {
            $this->pipes = [];

            // Loop through each of the pipes and manually add each pipe
            // so we can check if the name already exists.

            foreach ($pipes as $pipe) {
                $this->pipe($pipe->callable, $pipe->name, $pipe->pipeOrder);
            }

            return $this;
        }

        /**
         * Get all the pipes in the pipeline
         *
         * @return array<Pipe>
         */
        public function getPipes(): array
        {
            return $this->pipes;
        }

        /**
         * Check if a given pipe exists for a name
         */
        protected function pipeExists(string $name): bool
        {
            foreach ($this->pipes as $pipe) {
                if ($pipe->name === $name) {
                    return true;
                }
            }

            return false;
        }
    }

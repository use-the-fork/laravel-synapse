<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\OutputSchemas;

    use Illuminate\Support\Collection;
    use UseTheFork\Synapse\Contracts\OutputSchema;
    use UseTheFork\Synapse\ValueObject\Agent\Message;
    use UseTheFork\Synapse\ValueObject\Agent\Role;

    class JsonRuleOutputSchema implements OutputSchema
    {
        public function load(): void
        {
            // TODO: Implement load() method.
        }

        public function clear(): void
        {
            // TODO: Implement clear() method.
        }

        public function get(): array
        {
            // TODO: Implement get() method.
        }

        public function asInputs(): array
        {
            // TODO: Implement asInputs() method.
        }

        public function add(Message $message): void
        {
            // TODO: Implement add() method.
        }

        public function set(array $messages): void
        {
            // TODO: Implement set() method.
        }
    }

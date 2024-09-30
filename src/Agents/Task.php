<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Agents;


    use UseTheFork\Synapse\Agents\Enums\PromptType;
    use UseTheFork\Synapse\Traits\Agent\HasMiddleware;
    use UseTheFork\Synapse\Traits\Bootable;
    use UseTheFork\Synapse\Traits\Makeable;

    abstract class Task
    {
        use Bootable;
        use Makeable;
        use HasMiddleware;

        /**
         * The view to use when generating the prompt for this agent
         */
        protected PromptType $promptType;

        /**
         * The view to use when generating the prompt for this agent.
         */
        abstract public function resolvePromptView(): string;

        public function compilePrompt(array $inputs): string
        {
            if (isset($inputs['image'])) {
                $inputs['image'] = base64_encode(json_encode($inputs['image']));
            }

            return view($this->resolvePromptView(), [
                ...$inputs,
            ])->render();
        }

    }

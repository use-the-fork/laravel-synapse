<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Agents;


    use UseTheFork\Synapse\Agents\Enums\PromptType;
    use UseTheFork\Synapse\Contracts\Agent\Task\HasMemory;
    use UseTheFork\Synapse\Contracts\Tools\Tool;
    use UseTheFork\Synapse\Exceptions\InvalidArgumentException;
    use UseTheFork\Synapse\Traits\Agent\HasMiddleware;
    use UseTheFork\Synapse\Traits\Agent\Task\UseMemory;
    use UseTheFork\Synapse\Traits\Agent\Task\UseTools;
    use UseTheFork\Synapse\Traits\Bootable;
    use UseTheFork\Synapse\Traits\Makeable;
    use UseTheFork\Synapse\ValueObject\Agent\Message;
    use UseTheFork\Synapse\ValueObject\Agent\Role;

    abstract class Task implements HasMemory
    {
        use Bootable;
        use Makeable;
        use HasMiddleware;
        use UseMemory;
        use UseTools;

        /**
         * The view to use when generating the prompt for this agent
         */
        protected PromptType $promptType;

        /**
         * The view to use when generating the prompt for this agent.
         */
        abstract public function resolvePromptView(): string;

        /**
         * @return array<Tool>
 */
        abstract public function resolveTools(): array;

        public function compilePrompt(array $inputs): array
        {
            return match ($this->promptType) {
                PromptType::COMPLETION => $this->handleCompletionPrompt($inputs),
                PromptType::CHAT => $this->handleChatPrompt($inputs),
                default => throw new \InvalidArgumentException('Invalid prompt type'),
            };
        }

        public function handleCompletionPrompt(array $inputs): array
        {
            if (isset($inputs['image'])) {
                $inputs['image'] = base64_encode(json_encode($inputs['image']));
            }

            $compilePromptAsString = $this->compilePromptFromView($inputs);

            // The whole document is a prompt
            $prompts[] = Message::make([
                                           'role' => Role::USER,
                                           'content' => trim($compilePromptAsString),
                                       ]);

            return $prompts;
        }

        public function handleChatPrompt(array $inputs): array
        {
            $prompts = [];
            // Adjusted pattern to account for possible newlines, nested content, and the new 'image' attribute
            $pattern = '/<message\s+type=[\'"](?P<role>\w+)[\'"](?:\s+tool=[\'"](?P<tool>[\w\-+=\/]+)[\'"])?(?:\s+image=[\'"](?P<image>[\w\-+=\/]+)[\'"])?\s*>\s*(?P<message>.*?)\s*<\/message>/s';
            preg_match_all($pattern, $this->compilePromptFromView($inputs), $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $role = $match['role'] ?? null;
                $tool = $match['tool'] ?? null;
                $image = $match['image'] ?? null;
                $promptContent = $match['message'] ?? '';

                $promptContent = trim($promptContent);

                if (! $role) {
                    throw new InvalidArgumentException("Each message block must define a type.\nExample:\n<message type='assistant'>Foo {bar}</message>");
                }

                $messageData = [
                    'role' => $role,
                    'content' => $promptContent,
                ];
                if ($tool) {
                    $tool = json_decode(base64_decode($tool), true);
                    $messageData['tool_call_id'] = $tool['tool_call_id'];
                    $messageData['tool_name'] = $tool['tool_name'] ?? null;
                    $messageData['tool_arguments'] = $tool['tool_arguments'] ?? null;
                    $messageData['tool_content'] = $tool['tool_content'] ?? null;
                }
                if ($image) {
                    $image = json_decode(base64_decode($image), true);
                    // attach the image data to the message.
                    $messageData['image'] = $image;
                }
                $prompts[] = Message::make($messageData);
            }

            return $prompts;
        }

        public function compilePromptFromView(array $inputs): string
        {
            if (isset($inputs['image'])) {
                $inputs['image'] = base64_encode(json_encode($inputs['image']));
            }

            return view($this->resolvePromptView(), [
                ...$inputs,
            ])->render();
        }

        public function getPromptType(): string
        {
            return $this->promptType->value;
        }



    }

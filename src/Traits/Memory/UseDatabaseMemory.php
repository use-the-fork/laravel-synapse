<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\Memory;

use UseTheFork\Synapse\Agents\Enums\Role;
use UseTheFork\Synapse\Agents\Integrations\ValueObjects\Message;
use UseTheFork\Synapse\Agents\Models\Memory\AgentMemory;
use UseTheFork\Synapse\Traits\Agent\HasMiddleware;

trait UseDatabaseMemory
{
	use HasMiddleware;

    protected AgentMemory $memory;

    public function clearMemory(): void
    {
        $this->memory->delete();

        $this->memory = new AgentMemory;
        $this->memory->save();
    }

    public function getMemory(): array
    {
        return $this->memory->messages->toArray();
    }

    public function getMemoryAsInputs(): array
    {
        $payload = [
            'memory' => [],
            'memoryWithMessages' => [],
        ];
        $messages = $this->memory->messages->toArray();

        foreach ($messages as $message) {
            if ($message['role'] == Role::IMAGE_URL) {
                // TODO: Fix this it should put the content in to the message and encode the image info.
                $payload['memoryWithMessages'][] = "<message type='".Role::IMAGE_URL."'>\n{$message['image']['url']}}\n</message>";
            } elseif ($message['role'] == Role::TOOL) {

                $tool = base64_encode(json_encode([
                                                      'name' => $message['tool_name'],
                                                      'id' => $message['tool_call_id'],
                                                      'arguments' => $message['tool_arguments'],
                                                      'content' => $message['tool_content'],
                                                  ]));

                $payload['memoryWithMessages'][] = "<message type='".Role::TOOL."' tool='{$tool}'>\n{$message['content']}\n</message>";

                $payload['memory'][] = Role::ASSISTANT.": Call Tool `{$message['tool_name']}` with input `{$message['tool_arguments']}`";
                $payload['memory'][] = "{$message['tool_name']} response: {$message['tool_content']}";

            } else {
                $payload['memoryWithMessages'][] = "<message type='{$message['role']}'>\n{$message['content']}\n</message>";
                $payload['memory'][] = "{$message['role']}: {$message['content']}";
            }
        }

        return [
            'memoryWithMessages' => implode("\n", $payload['memoryWithMessages']),
            'memory' => implode("\n", $payload['memory']),
        ];
    }

    public function addMemory(Message $message): void
    {
        $this->memory->messages()->create($message->toArray());
    }

    public function setMemory(array $messages): void
    {
        //First we delete all the agents memory
        $this->memory->messages()->delete();

        //Iterate over the messages and insert them in to memory
        foreach ($messages as $message) {
            $message = Message::make($message);
            $this->memory->messages()->create($message->toArray());
        }
    }

    public function loadMemory(): void
    {
		$this->memory->load('messages');
    }

    public function bootUseDatabaseMemory(): void
    {
        $this->memory = new AgentMemory;
        $this->memory->save();

		$this->middleware()->onStartTask(fn() => $this->loadMemory(), 'loadMemory');
    }
}

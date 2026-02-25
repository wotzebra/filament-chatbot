<?php

namespace Wotz\FilamentChatbot\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Ai\Messages\MessageRole;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;

class AgentConversationMessageFactory extends Factory
{
    protected $model = AgentConversationMessage::class;

    public function definition(): array
    {
        return [
            'conversation_id' => AgentConversation::factory(),
            'user_id' => null,
            'agent' => fake()->word(),
            'role' => fake()->randomElement([MessageRole::User->value, MessageRole::Assistant->value]),
            'content' => fake()->paragraph(),
            'attachments' => [],
            'tool_calls' => [],
            'tool_results' => [],
            'usage' => [],
            'meta' => [],
        ];
    }

    public function user(): static
    {
        return $this->state(['role' => MessageRole::User->value]);
    }

    public function assistant(): static
    {
        return $this->state(['role' => MessageRole::Assistant->value]);
    }
}

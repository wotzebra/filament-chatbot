<?php

namespace Wotz\FilamentChatbot\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wotz\FilamentChatbot\Models\AgentConversation;

class AgentConversationFactory extends Factory
{
    protected $model = AgentConversation::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'title' => fake()->sentence(4),
        ];
    }
}

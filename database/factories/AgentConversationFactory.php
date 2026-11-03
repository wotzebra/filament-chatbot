<?php

namespace Wotz\FilamentChatbot\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Wotz\FilamentChatbot\Models\AgentConversation;

class AgentConversationFactory extends Factory
{
    protected $model = AgentConversation::class;

    public function definition(): array
    {
        return [
            'participant_type' => null,
            'participant_id' => null,
            'title' => fake()->sentence(4),
        ];
    }

    public function ownedBy(Model $participant): static
    {
        return $this->state([
            'participant_type' => $participant->getMorphClass(),
            'participant_id' => $participant->getKey(),
        ]);
    }
}

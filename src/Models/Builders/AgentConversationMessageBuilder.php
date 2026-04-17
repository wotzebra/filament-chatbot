<?php

namespace Wotz\FilamentChatbot\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Messages\MessageRole;

class AgentConversationMessageBuilder extends Builder
{
    public function forConversation(string $conversationId): static
    {
        return $this->where('conversation_id', $conversationId);
    }

    public function visibleInChat(): static
    {
        return $this->whereIn('role', [
            MessageRole::User->value,
            MessageRole::Assistant->value,
        ]);
    }

    public function assistant(): static
    {
        return $this->where('role', MessageRole::Assistant->value);
    }
}

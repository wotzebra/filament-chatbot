<?php

namespace Wotz\FilamentChatbot\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Messages\MessageRole;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;

/**
 * @extends Builder<AgentConversationMessage>
 */
class AgentConversationMessageBuilder extends Builder
{
    public function forConversation(string $conversationId): static
    {
        return $this->where('conversation_id', $conversationId);
    }

    public function forConversationColumn(string $conversationColumn): static
    {
        return $this->whereColumn('conversation_id', $conversationColumn);
    }

    public function visibleInChat(): static
    {
        $this->whereIn('role', [
            MessageRole::User->value,
            MessageRole::Assistant->value,
        ]);

        return $this;
    }

    public function assistant(): static
    {
        return $this->where('role', MessageRole::Assistant->value);
    }
}

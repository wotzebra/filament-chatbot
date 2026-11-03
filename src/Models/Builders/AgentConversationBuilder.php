<?php

namespace Wotz\FilamentChatbot\Models\Builders;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Models\Conversation;
use Wotz\FilamentChatbot\Models\AgentConversation;

/**
 * @extends Builder<AgentConversation>
 */
class AgentConversationBuilder extends Builder
{
    public function ownedBy(?Authenticatable $user): static
    {
        if ($user === null) {
            return $this->whereNull('participant_type')->whereNull('participant_id');
        }

        return $this
            ->where('participant_type', Conversation::participantType($user))
            ->where('participant_id', $user->getAuthIdentifier());
    }
}

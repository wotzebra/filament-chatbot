<?php

namespace Wotz\FilamentChatbot\Models\Builders;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Wotz\FilamentChatbot\Models\AgentConversation;

/**
 * @extends Builder<AgentConversation>
 */
class AgentConversationBuilder extends Builder
{
    public function ownedBy(?Authenticatable $user): static
    {
        return $this->where('user_id', $user?->getAuthIdentifier());
    }
}

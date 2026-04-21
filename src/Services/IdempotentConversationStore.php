<?php

namespace Wotz\FilamentChatbot\Services;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Storage\DatabaseConversationStore;

/**
 * Wraps the SDK's DatabaseConversationStore so that a user message that was
 * already persisted by the Livewire widget is not inserted a second time by
 * the RememberConversation middleware.
 */
class IdempotentConversationStore extends DatabaseConversationStore
{
    public function storeUserMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt): string
    {
        $existing = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('role', 'user')
            ->where('content', $prompt->prompt)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderByDesc('created_at')
            ->limit(1)
            ->value('id');

        if ($existing) {
            return $existing;
        }

        return parent::storeUserMessage($conversationId, $userId, $prompt);
    }
}

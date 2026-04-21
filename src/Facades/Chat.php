<?php

namespace Wotz\FilamentChatbot\Facades;

use Illuminate\Support\Facades\Facade;
use Wotz\FilamentChatbot\Services\ChatManager;

/**
 * @method static \Wotz\FilamentChatbot\Models\AgentConversation start(?\Illuminate\Contracts\Auth\Authenticatable $user = null, ?string $title = null, array $attributes = [])
 * @method static ?\Wotz\FilamentChatbot\Models\AgentConversation find(string $id)
 * @method static array<int, array{role: string, content: string}> history(string $id)
 * @method static bool ownedBy(string $id, ?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool delete(string $id)
 * @method static \Wotz\FilamentChatbot\Services\PendingChat for(string $conversationId)
 * @method static \Wotz\FilamentChatbot\Models\AgentConversationMessage addUserMessage(string $conversationId, ?\Illuminate\Contracts\Auth\Authenticatable $user, string $message)
 * @method static \Wotz\FilamentChatbot\Models\AgentConversationMessage addAssistantErrorMessage(string $conversationId, ?\Illuminate\Contracts\Auth\Authenticatable $user, string $content, ?string $agent = null)
 *
 * @see ChatManager
 */
class Chat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ChatManager::class;
    }
}

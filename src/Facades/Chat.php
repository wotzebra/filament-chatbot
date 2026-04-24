<?php

namespace Wotz\FilamentChatbot\Facades;

use Illuminate\Support\Facades\Facade;
use Wotz\FilamentChatbot\Services\ChatManager;

/**
 * @method static \Wotz\FilamentChatbot\Models\AgentConversation start(?\Illuminate\Contracts\Auth\Authenticatable $user = null, string $title = '')
 * @method static ?\Wotz\FilamentChatbot\Models\AgentConversation find(string $id)
 * @method static \Illuminate\Support\Collection<int, array{role: string, content: string}> history(string $id)
 * @method static bool ownedBy(string $id, ?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool delete(string $id)
 * @method static \Wotz\FilamentChatbot\Services\PendingChat for(string $conversationId)
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

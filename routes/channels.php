<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Broadcast;
use Wotz\FilamentChatbot\Models\AgentConversation;

$prefix = (string) config('filament-chatbot.stream.websocket.channel_prefix', 'chatbot.conversation');

Broadcast::channel(
    $prefix . '.{conversationId}',
    function (Authenticatable $user, string $conversationId): bool {
        return AgentConversation::query()
            ->whereKey($conversationId)
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    },
);

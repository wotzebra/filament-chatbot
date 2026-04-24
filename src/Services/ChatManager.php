<?php

namespace Wotz\FilamentChatbot\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Support\Chatbot\PreparePendingChat;

class ChatManager
{
    public function __construct(protected PreparePendingChat $preparePendingChat) {}

    public function start(?Authenticatable $user = null, string $title = ''): AgentConversation
    {
        return AgentConversation::create([
            'user_id' => $user?->getAuthIdentifier(),
            'title' => $title !== '' ? Str::limit($title, 80) : '',
        ]);
    }

    public function find(string $id): ?AgentConversation
    {
        return AgentConversation::query()->find($id);
    }

    public function history(string $id): Collection
    {
        return AgentConversationMessage::query()
            ->forConversation($id)
            ->visibleInChat()
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->map(fn (AgentConversationMessage $message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values();
    }

    public function ownedBy(string $id, ?Authenticatable $user): bool
    {
        return AgentConversation::query()
            ->whereKey($id)
            ->ownedBy($user)
            ->exists();
    }

    public function delete(string $id): bool
    {
        return AgentConversation::query()->whereKey($id)->delete();
    }

    public function for(string $conversationId): PendingChat
    {
        return $this->preparePendingChat->fromOverrides($conversationId, user: null);
    }
}

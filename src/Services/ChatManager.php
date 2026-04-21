<?php

namespace Wotz\FilamentChatbot\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\MessageRole;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;

class ChatManager
{
    public function __construct(protected ChatConfig $defaults) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function start(?Authenticatable $user = null, ?string $title = null, array $attributes = []): AgentConversation
    {
        if ($title !== null && $title !== '') {
            $attributes['title'] ??= Str::limit($title, 80);
        }

        return AgentConversation::query()->create([
            'user_id' => $user?->getAuthIdentifier(),
            ...$attributes,
        ]);
    }

    public function find(string $id): ?AgentConversation
    {
        return AgentConversation::query()->find($id);
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function history(string $id): array
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
            ->values()
            ->all();
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
        return (bool) AgentConversation::query()->whereKey($id)->delete();
    }

    public function addUserMessage(string $conversationId, ?Authenticatable $user, string $message): AgentConversationMessage
    {
        return $this->createMessage(
            conversationId: $conversationId,
            user: $user,
            role: MessageRole::User->value,
            content: $message,
        );
    }

    public function addPendingAssistantMessage(string $conversationId, ?Authenticatable $user, ?string $agent = null): AgentConversationMessage
    {
        return $this->createMessage(
            conversationId: $conversationId,
            user: $user,
            role: MessageRole::Assistant->value,
            content: '',
            agent: $agent,
            meta: ['pending' => true],
        );
    }

    public function addAssistantErrorMessage(string $conversationId, ?Authenticatable $user, string $content, ?string $agent = null): AgentConversationMessage
    {
        return $this->createMessage(
            conversationId: $conversationId,
            user: $user,
            role: MessageRole::Assistant->value,
            content: $content,
            agent: $agent,
            meta: ['error' => true],
        );
    }

    /**
     * @param  array<int, string>  $conversationIds
     * @return array<int, string>
     */
    public function ownedConversationIds(array $conversationIds, ?Authenticatable $user): array
    {
        if ($conversationIds === []) {
            return [];
        }

        $ownedConversationIds = AgentConversation::query()
            ->whereIn('id', $conversationIds)
            ->ownedBy($user)
            ->pluck('id')
            ->all();

        return array_values(array_filter(
            $conversationIds,
            fn (string $conversationId): bool => in_array($conversationId, $ownedConversationIds, true),
        ));
    }

    /**
     * @param  array<int, string>  $conversationIds
     * @return array<int, string>
     */
    public function pendingConversationIds(array $conversationIds): array
    {
        if ($conversationIds === []) {
            return [];
        }

        return AgentConversationMessage::query()
            ->whereIn('conversation_id', $conversationIds)
            ->assistant()
            ->get(['conversation_id', 'meta'])
            ->filter(fn (AgentConversationMessage $message): bool => (bool) ($message->meta['pending'] ?? false))
            ->pluck('conversation_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $conversationIds
     * @return array<string, string>
     */
    public function titlesFor(array $conversationIds): array
    {
        if ($conversationIds === []) {
            return [];
        }

        $titles = AgentConversation::query()
            ->whereIn('id', $conversationIds)
            ->pluck('title', 'id')
            ->all();

        return array_intersect_key($titles, array_flip($conversationIds));
    }

    /**
     * @return array<int, string>
     */
    public function recentConversationIds(?Authenticatable $user, int $limit = 12): array
    {
        return AgentConversation::query()
            ->ownedBy($user)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int, string>  $conversationIds
     * @return array<string, array{id: string, title: string, preview: string, updated_at_human: string}>
     */
    public function summariesFor(array $conversationIds): array
    {
        if ($conversationIds === []) {
            return [];
        }

        $conversations = AgentConversation::query()
            ->whereIn('id', $conversationIds)
            ->select([
                'id',
                'title',
                'updated_at',
            ])
            ->selectSub(
                AgentConversationMessage::query()
                    ->select('content')
                    ->forConversationColumn('agent_conversations.id')
                    ->visibleInChat()
                    ->where('content', '!=', '')
                    ->latest('created_at')
                    ->limit(1),
                'preview',
            )
            ->get()
            ->keyBy('id');

        $summaries = [];

        foreach ($conversationIds as $conversationId) {
            /** @var AgentConversation|null $conversation */
            $conversation = $conversations->get($conversationId);

            if ($conversation === null) {
                continue;
            }

            $title = trim((string) $conversation->title);
            $preview = trim((string) ($conversation->getAttribute('preview') ?? ''));

            $summaries[$conversationId] = [
                'id' => $conversationId,
                'title' => $title !== '' ? $title : __('filament-chatbot::chatbot.conversation'),
                'preview' => $preview !== '' ? Str::limit($preview, 120) : __('filament-chatbot::chatbot.no_messages'),
                'updated_at_human' => $conversation->updated_at?->diffForHumans(short: true) ?? '',
            ];
        }

        return $summaries;
    }

    public function for(string $conversationId): PendingChat
    {
        return new PendingChat(clone $this->defaults, $conversationId);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function createMessage(
        string $conversationId,
        ?Authenticatable $user,
        string $role,
        string $content,
        ?string $agent = null,
        array $meta = [],
    ): AgentConversationMessage {
        $message = AgentConversationMessage::query()->create([
            'conversation_id' => $conversationId,
            'user_id' => $user?->getAuthIdentifier(),
            'agent' => $agent ?? '',
            'role' => $role,
            'content' => $content,
            'attachments' => [],
            'tool_calls' => [],
            'tool_results' => [],
            'usage' => [],
            'meta' => $meta,
        ]);

        $this->touchConversation($conversationId);

        return $message;
    }

    protected function touchConversation(string $conversationId): void
    {
        AgentConversation::query()
            ->whereKey($conversationId)
            ->update(['updated_at' => Carbon::now()]);
    }
}

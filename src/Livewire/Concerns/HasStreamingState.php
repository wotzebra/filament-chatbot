<?php

namespace Wotz\FilamentChatbot\Livewire\Concerns;

use Laravel\Ai\Messages\MessageRole;
use Livewire\Attributes\Computed;
use Wotz\FilamentChatbot\Models\AgentConversation;

trait HasStreamingState
{
    public bool $isStreaming = false;

    public string $streamMessage = '';

    public string $initialStreamingText = '';

    public bool $shouldStartStreamRequest = false;

    #[Computed]
    public function streamingConversationIds(): array
    {
        return array_keys($this->activeStreams());
    }

    protected function activeStreams(): array
    {
        return $this->resolveActiveStreams(session()->get($this->activeStreamSessionKey()));
    }

    protected function resolveActiveStreams(mixed $activeStreams): array
    {
        if (! is_array($activeStreams)) {
            return [];
        }

        $resolvedConversationIds = AgentConversation::query()
            ->ownedBy(auth()->user())
            ->whereKey(array_keys($activeStreams))
            ->pluck('id');

        $resolvedActiveStreams = [];

        foreach ($resolvedConversationIds as $conversationId) {
            $message = trim((string) ($activeStreams[$conversationId]['message'] ?? ''));

            if ($message === '') {
                continue;
            }

            $resolvedActiveStreams[$conversationId] = ['message' => $message];
        }

        $this->storeActiveStreams($resolvedActiveStreams);

        return $resolvedActiveStreams;
    }

    protected function restoreActiveStream(string $message): void
    {
        $last = end($this->messages) ?: null;

        if ($last !== null && $last['role'] === MessageRole::Assistant->value) {
            $popped = array_pop($this->messages);
            $this->initialStreamingText = $popped['content'];
            $last = end($this->messages) ?: null;
        }

        $hasPendingUserMessage = $last !== null
            && $last['role'] === MessageRole::User->value
            && $last['content'] === $message;

        if (! $hasPendingUserMessage) {
            $this->messages[] = [
                'role' => MessageRole::User->value,
                'content' => $message,
            ];
        }

        $this->streamMessage = $message;
        $this->isStreaming = true;
        $this->shouldStartStreamRequest = false;
    }

    protected function activeStreamSessionKey(): string
    {
        return $this->chatbot()->getActiveStreamsSessionKey();
    }

    protected function storeActiveStreams(array $activeStreams): void
    {
        session()->put($this->activeStreamSessionKey(), $activeStreams);
    }

    protected function clearVisibleStreamState(): void
    {
        $this->isStreaming = false;
        $this->streamMessage = '';
        $this->initialStreamingText = '';
        $this->shouldStartStreamRequest = false;
    }

    protected function cleanupVisibleStream(): void
    {
        if (! $this->isStreaming || $this->conversationId === null) {
            return;
        }

        $this->dispatch('chatbot-stream-cleanup', conversationId: $this->conversationId);
    }
}

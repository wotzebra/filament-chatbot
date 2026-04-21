<?php

namespace Wotz\FilamentChatbot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Wotz\FilamentChatbot\Broadcasting\ChatbotStreamEvent;
use Wotz\FilamentChatbot\Facades\Chat;
use Wotz\FilamentChatbot\Support\Chatbot\StreamRunner;

class StreamAgentResponseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     */
    public function __construct(
        public string $conversationId,
        public string $message,
        public int|string|null $userId,
        public ?string $userModel,
        public array $context = [],
        public array $overrides = [],
    ) {
        $queue = config('filament-chatbot.stream.websocket.queue');

        if ($queue) {
            $this->onQueue($queue);
        }
    }

    public function handle(): void
    {
        $user = $this->resolveUser();
        $pending = Chat::for($this->conversationId)
            ->as($user)
            ->applyOverrides($this->overrides);
        $runner = app(StreamRunner::class);

        $sink = fn (array $event) => broadcast(new ChatbotStreamEvent(
            conversationId: $this->conversationId,
            type: (string) ($event['type'] ?? 'event'),
            payload: $event,
        ));

        try {
            $result = $runner->run($this->conversationId, $user, fn () => $pending->streamEvents($this->message), $sink);

            if (! $result['failed']) {
                $pending->finalize($result['message']);
            }
        } finally {
            broadcast(new ChatbotStreamEvent(
                conversationId: $this->conversationId,
                type: 'done',
                payload: ['type' => 'done'],
            ));
        }
    }

    protected function resolveUser(): mixed
    {
        if ($this->userId === null) {
            return null;
        }

        $model = $this->userModel ?: config('filament-chatbot.user_model');

        if (! is_string($model) || $model === '') {
            $provider = Auth::createUserProvider();

            return $provider ? $provider->retrieveById($this->userId) : null;
        }

        /** @var class-string<AuthUser> $model */
        return $model::query()->find($this->userId);
    }
}

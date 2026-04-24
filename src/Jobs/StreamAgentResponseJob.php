<?php

namespace Wotz\FilamentChatbot\Jobs;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Throwable;
use Wotz\FilamentChatbot\Support\Chatbot\ChatOverrides;
use Wotz\FilamentChatbot\Support\Chatbot\FriendlyErrorMessage;
use Wotz\FilamentChatbot\Support\Chatbot\PreparePendingChat;

class StreamAgentResponseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $conversationId,
        public string $message,
        public int|string|null $userId,
        public ?string $userModel,
        public ?ChatOverrides $overrides = null,
    ) {
        $queue = config('filament-chatbot.stream.websocket.queue');

        if ($queue) {
            $this->onQueue($queue);
        }
    }

    public function handle(): void
    {
        $user = $this->resolveUser();

        $pending = app(PreparePendingChat::class)->fromOverrides(
            conversationId: $this->conversationId,
            user: $user,
            overrides: $this->overrides,
        );

        $channel = new PrivateChannel($this->channelName());
        $response = $pending->streamResponse($this->message);

        try {
            foreach ($response as $event) {
                if ($event instanceof StreamEvent) {
                    $event->broadcastNow($channel);
                }
            }
        } catch (Throwable $e) {
            report($e);

            Broadcast::on($channel)
                ->as('text_delta')
                ->with([
                    'type' => 'text_delta',
                    'delta' => FriendlyErrorMessage::resolve($e),
                ])
                ->sendNow();

            Broadcast::on($channel)
                ->as('stream_end')
                ->with(['type' => 'stream_end'])
                ->sendNow();
        }
    }

    protected function channelName(): string
    {
        $prefix = (string) config('filament-chatbot.stream.websocket.channel_prefix', 'chatbot.conversation');

        return "{$prefix}.{$this->conversationId}";
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

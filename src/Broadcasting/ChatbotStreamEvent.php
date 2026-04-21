<?php

namespace Wotz\FilamentChatbot\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatbotStreamEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $conversationId,
        public string $type,
        public array $payload = [],
    ) {}

    public function broadcastOn(): Channel
    {
        $prefix = (string) config('filament-chatbot.stream.websocket.channel_prefix', 'chatbot.conversation');

        return new PrivateChannel("{$prefix}.{$this->conversationId}");
    }

    public function broadcastAs(): string
    {
        return 'chatbot.stream';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }

    /**
     * @return array<int, string|null>
     */
    public function broadcastConnections(): array
    {
        $connection = config('filament-chatbot.stream.websocket.connection');

        return $connection ? [$connection] : [null];
    }
}

<?php

namespace Wotz\FilamentChatbot\Streaming;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Jobs\StreamAgentResponseJob;
use Wotz\FilamentChatbot\Support\Chatbot\PreparePendingChat;

class WebsocketStreamTransport implements StreamTransport
{
    public function start(string $conversationId, string $message, Closure $eventsFactory): SymfonyResponse
    {
        /** @var ChatbotPlugin $chatbot */
        $chatbot = filament('chatbot');
        $hasActiveDuplicateStream = $this->hasActiveDuplicateStream($chatbot, $conversationId, $message);

        if (! $hasActiveDuplicateStream) {
            StreamAgentResponseJob::dispatch(
                $conversationId,
                $message,
                auth()->user()?->getAuthIdentifier(),
                $chatbot?->getUserModel(),
                app(PreparePendingChat::class)->resolveOverrides(
                    chatbot: $chatbot,
                    rawContext: (array) request()->input('context', []),
                    request: request(),
                ),
            );
        }

        return new JsonResponse([
            'accepted' => true,
            'conversation_id' => $conversationId,
            'transport' => $this->name(),
            'client' => $this->clientConfig($conversationId),
        ], 202);
    }

    public function name(): string
    {
        return 'websocket';
    }

    public function clientConfig(string $conversationId): array
    {
        $prefix = (string) config('filament-chatbot.stream.websocket.channel_prefix', 'chatbot.conversation');

        return [
            'endpoint' => route('chatbot.stream'),
            'channel' => "{$prefix}.{$conversationId}",
        ];
    }

    protected function hasActiveDuplicateStream(?ChatbotPlugin $chatbot, string $conversationId, string $message): bool
    {
        if ($chatbot === null || ! session()->isStarted()) {
            return false;
        }

        $activeStreams = session()->get($chatbot->getActiveStreamsSessionKey(), []);

        if (! is_array($activeStreams)) {
            return false;
        }

        $activeMessage = trim((string) ($activeStreams[$conversationId]['message'] ?? ''));

        return $activeMessage !== '' && $activeMessage === trim($message);
    }
}

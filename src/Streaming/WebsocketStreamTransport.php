<?php

namespace Wotz\FilamentChatbot\Streaming;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Jobs\StreamAgentResponseJob;

class WebsocketStreamTransport implements StreamTransport
{
    public function start(string $conversationId, string $message, iterable $events): SymfonyResponse
    {
        $chatbot = $this->chatbot();
        $hasActiveDuplicateStream = $this->hasActiveDuplicateStream($chatbot, $conversationId, $message);

        if (! $hasActiveDuplicateStream) {
            StreamAgentResponseJob::dispatch(
                $conversationId,
                $message,
                auth()->user()?->getAuthIdentifier(),
                $chatbot?->getUserModel(),
                [],
                $this->buildOverrides($chatbot),
            );
        }

        $this->logStart($conversationId, $message, $hasActiveDuplicateStream);

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
            'event' => 'chatbot.stream',
        ];
    }

    protected function chatbot(): ?ChatbotPlugin
    {
        try {
            /** @var ChatbotPlugin $plugin */
            $plugin = filament('chatbot');

            return $plugin;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildOverrides(?ChatbotPlugin $chatbot): array
    {
        if ($chatbot === null) {
            return [];
        }

        return [
            'agent' => $chatbot->getAgentClass(),
            'provider' => $chatbot->getProvider(),
            'model' => $chatbot->getModel(),
            'tools' => $chatbot->getTools(),
            'context' => $this->resolveContext($chatbot),
        ];
    }

    protected function resolveContext(ChatbotPlugin $chatbot): mixed
    {
        $resolver = $chatbot->getContextResolver();

        if (! is_callable($resolver)) {
            return null;
        }

        $request = request();

        return $resolver((array) $request->input('context', []), $request);
    }

    protected function hasActiveDuplicateStream(?ChatbotPlugin $chatbot, string $conversationId, string $message): bool
    {
        if ($chatbot === null || ! session()->isStarted()) {
            return false;
        }

        $activeStreams = session()->get($chatbot->getConversationKey() . '_active_streams', []);

        if (! is_array($activeStreams)) {
            return false;
        }

        $activeMessage = trim((string) ($activeStreams[$conversationId]['message'] ?? ''));

        return $activeMessage !== '' && $activeMessage === trim($message);
    }

    protected function logStart(string $conversationId, string $message, bool $duplicateSkipped): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        Log::debug('filament-chatbot.websocket.start', [
            'conversation_id' => $conversationId,
            'message' => $message,
            'duplicate_skipped' => $duplicateSkipped,
            'session_id' => session()->getId(),
            'user_id' => auth()->user()?->getAuthIdentifier(),
            'transport' => $this->name(),
        ]);
    }
}

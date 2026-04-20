<?php

namespace Wotz\FilamentChatbot\Streaming;

use Illuminate\Http\JsonResponse;
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

        StreamAgentResponseJob::dispatch(
            $conversationId,
            $message,
            auth()->user()?->getAuthIdentifier(),
            $chatbot?->getUserModel(),
            [],
            $this->buildOverrides($chatbot),
        );

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
}

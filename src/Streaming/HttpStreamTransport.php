<?php

namespace Wotz\FilamentChatbot\Streaming;

use Closure;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Facades\Chat;
use Wotz\FilamentChatbot\Support\Chatbot\FriendlyErrorMessage;
use Wotz\FilamentChatbot\Support\Chatbot\SseStream;
use Wotz\FilamentChatbot\Support\Chatbot\StreamEventNormalizer;

class HttpStreamTransport implements StreamTransport
{
    public function start(string $conversationId, string $message, Closure $eventsFactory): SymfonyResponse
    {
        set_time_limit(300);

        $user = auth()->user();

        return new StreamedResponse(function () use ($conversationId, $eventsFactory, $user): void {
            $sse = new SseStream;
            $normalizer = app(StreamEventNormalizer::class);
            $streamedMessage = '';

            try {
                foreach ($eventsFactory() as $event) {
                    $decoded = $normalizer->decode($event);
                    $normalized = $normalizer->normalize($streamedMessage, $decoded);

                    $streamedMessage = $normalized['message'];

                    $sse->event($normalized['event']);
                }
            } catch (Throwable $e) {
                report($e);

                $errorMessage = FriendlyErrorMessage::resolve($e);
                $persistedContent = $streamedMessage !== ''
                    ? $streamedMessage . "\n\n" . $errorMessage
                    : $errorMessage;

                $sse->event(['type' => 'text_delta', 'delta' => $errorMessage]);
                Chat::addAssistantErrorMessage($conversationId, $user, $persistedContent);
            } finally {
                $sse->done();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function name(): string
    {
        return 'http';
    }

    public function clientConfig(string $conversationId): array
    {
        return [
            'endpoint' => route('chatbot.stream'),
        ];
    }
}

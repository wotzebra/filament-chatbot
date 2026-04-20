<?php

namespace Wotz\FilamentChatbot\Streaming;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Support\Chatbot\FriendlyErrorMessage;
use Wotz\FilamentChatbot\Support\Chatbot\SseStream;

class HttpStreamTransport implements StreamTransport
{
    public function start(string $conversationId, string $message, iterable $events): SymfonyResponse
    {
        return new StreamedResponse(function () use ($events): void {
            $sse = new SseStream;

            try {
                foreach ($events as $event) {
                    $sse->event((string) $event);
                }
            } catch (Throwable $e) {
                report($e);

                $sse->event(['type' => 'text_delta', 'delta' => FriendlyErrorMessage::resolve($e)]);
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

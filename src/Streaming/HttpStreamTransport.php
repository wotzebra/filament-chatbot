<?php

namespace Wotz\FilamentChatbot\Streaming;

use Closure;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Support\Chatbot\SseStream;
use Wotz\FilamentChatbot\Support\Chatbot\StreamRunner;

class HttpStreamTransport implements StreamTransport
{
    public function start(string $conversationId, string $message, Closure $eventsFactory): SymfonyResponse
    {
        set_time_limit(300);

        $user = auth()->user();

        return new StreamedResponse(function () use ($conversationId, $eventsFactory, $user): void {
            $sse = new SseStream;
            $runner = app(StreamRunner::class);

            try {
                $runner->run($conversationId, $user, $eventsFactory, fn (array $event) => $sse->event($event));
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

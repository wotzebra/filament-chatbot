<?php

namespace Wotz\FilamentChatbot\Streaming;

use Closure;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Support\Chatbot\FriendlyErrorMessage;

class HttpStreamTransport implements StreamTransport
{
    public function start(string $conversationId, string $message, Closure $eventsFactory): SymfonyResponse
    {
        set_time_limit(300);

        return response()->stream(function () use ($eventsFactory): void {
            try {
                foreach ($eventsFactory() as $event) {
                    $this->emit($event instanceof StreamEvent
                        ? (string) $event
                        : json_encode($event));
                }
            } catch (Throwable $e) {
                report($e);

                $this->emit(json_encode([
                    'type' => 'text_delta',
                    'delta' => FriendlyErrorMessage::resolve($e),
                ]));
            } finally {
                $this->emit('[DONE]');
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

    protected function emit(string $payload): void
    {
        echo 'data: ' . $payload . "\n\n";

        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }
}

<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;
use Wotz\FilamentChatbot\Facades\Chat;

class StreamRunner
{
    public function __construct(protected StreamEventNormalizer $normalizer) {}

    /**
     * Consume a stream of agent events, forward normalized events to the sink,
     * and persist a friendly error message on failure.
     *
     * @param  Closure(): iterable  $eventsFactory
     * @param  Closure(array<string, mixed>): void  $sink
     * @return array{message: string, failed: bool}
     */
    public function run(string $conversationId, ?Authenticatable $user, Closure $eventsFactory, Closure $sink): array
    {
        $streamedMessage = '';

        try {
            foreach ($eventsFactory() as $event) {
                $decoded = $this->normalizer->decode($event);
                $normalized = $this->normalizer->normalize($streamedMessage, $decoded);

                $streamedMessage = $normalized['message'];

                $sink($normalized['event']);
            }

            return ['message' => $streamedMessage, 'failed' => false];
        } catch (Throwable $e) {
            report($e);

            $errorMessage = FriendlyErrorMessage::resolve($e);
            $persistedContent = $streamedMessage !== ''
                ? $streamedMessage . "\n\n" . $errorMessage
                : $errorMessage;

            Chat::addAssistantErrorMessage($conversationId, $user, $persistedContent);
            $sink(['type' => 'text_delta', 'delta' => $errorMessage]);

            return ['message' => $streamedMessage, 'failed' => true];
        }
    }
}

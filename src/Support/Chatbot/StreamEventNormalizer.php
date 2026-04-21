<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

class StreamEventNormalizer
{
    /**
     * @return array<string, mixed>
     */
    public function decode(mixed $event): array
    {
        if (is_array($event)) {
            return $event;
        }

        $payload = is_scalar($event) || $event instanceof \Stringable ? (string) $event : '';

        $decoded = json_decode($payload, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return ['type' => 'text_delta', 'delta' => $payload];
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array{event: array<string, mixed>, message: string}
     */
    public function normalize(string $streamedMessage, array $event): array
    {
        if (($event['type'] ?? null) === 'text_delta' && is_string($event['delta'] ?? null)) {
            $snapshot = $event['delta'];

            if ($snapshot !== '' && str_starts_with($snapshot, $streamedMessage)) {
                return [
                    'event' => [
                        ...$event,
                        'delta' => substr($snapshot, strlen($streamedMessage)),
                    ],
                    'message' => $snapshot,
                ];
            }

            return [
                'event' => $event,
                'message' => $streamedMessage . $snapshot,
            ];
        }

        if (is_string($event['message'] ?? null) && $event['message'] !== '') {
            return [
                'event' => $event,
                'message' => $event['message'],
            ];
        }

        return [
            'event' => $event,
            'message' => $streamedMessage,
        ];
    }
}

<?php

namespace Wotz\FilamentChatbot\Contracts;

use Closure;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

interface StreamTransport
{
    /**
     * Start a stream for the given conversation. Returns whatever the
     * controller should hand back to the frontend (a StreamedResponse for
     * the http driver, a JsonResponse for the websocket driver).
     *
     * The factory is invoked only when the transport needs the event stream.
     * The websocket driver dispatches a job that builds its own stream and
     * never calls the factory, avoiding a wasted agent instantiation.
     *
     * @param  Closure(): iterable<mixed>  $eventsFactory
     */
    public function start(string $conversationId, string $message, Closure $eventsFactory): SymfonyResponse;

    /**
     * Short key the frontend uses to pick the right client implementation.
     */
    public function name(): string;

    /**
     * Extra configuration the frontend needs to consume the stream
     * (e.g. the endpoint to call or the channel to subscribe to).
     *
     * @return array<string, mixed>
     */
    public function clientConfig(string $conversationId): array;
}

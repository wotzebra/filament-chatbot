<?php

namespace Wotz\FilamentChatbot\Streaming;

use Illuminate\Support\Manager;
use Wotz\FilamentChatbot\Contracts\StreamTransport;

class TransportManager extends Manager
{
    public function getDefaultDriver(): string
    {
        $driver = $this->config->get('filament-chatbot.stream.transport');

        return is_string($driver) && $driver !== '' ? $driver : 'http';
    }

    protected function createHttpDriver(): StreamTransport
    {
        return $this->container->make(HttpStreamTransport::class);
    }

    protected function createWebsocketDriver(): StreamTransport
    {
        return $this->container->make(WebsocketStreamTransport::class);
    }
}

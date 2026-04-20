<?php

use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Streaming\HttpStreamTransport;
use Wotz\FilamentChatbot\Streaming\TransportManager;
use Wotz\FilamentChatbot\Streaming\WebsocketStreamTransport;

afterEach(function () {
    app()->forgetInstance(TransportManager::class);
});

it('resolves the http driver by default', function () {
    config()->set('filament-chatbot.stream.transport', 'http');

    expect(app(TransportManager::class)->driver())
        ->toBeInstanceOf(HttpStreamTransport::class);
});

it('falls back to the http driver when transport config is missing', function () {
    config()->set('filament-chatbot.stream.transport', null);

    expect(app(TransportManager::class)->driver())
        ->toBeInstanceOf(HttpStreamTransport::class);
});

it('resolves the websocket driver when configured', function () {
    config()->set('filament-chatbot.stream.transport', 'websocket');

    expect(app(TransportManager::class)->driver())
        ->toBeInstanceOf(WebsocketStreamTransport::class);
});

it('binds the StreamTransport contract to the configured driver', function () {
    config()->set('filament-chatbot.stream.transport', 'websocket');

    expect(app(StreamTransport::class))
        ->toBeInstanceOf(WebsocketStreamTransport::class);
});

it('exposes the driver name for the frontend', function (string $configured, string $expectedName) {
    config()->set('filament-chatbot.stream.transport', $configured);

    expect(app(TransportManager::class)->driver()->name())->toBe($expectedName);
})->with([
    'http' => ['http', 'http'],
    'websocket' => ['websocket', 'websocket'],
]);

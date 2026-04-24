<?php

namespace Wotz\FilamentChatbot\Tests\Streaming;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Streaming\HttpStreamTransport;
use Wotz\FilamentChatbot\Streaming\TransportManager;
use Wotz\FilamentChatbot\Streaming\WebsocketStreamTransport;
use Wotz\FilamentChatbot\Tests\TestCase;

class TransportManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        app()->forgetInstance(TransportManager::class);

        parent::tearDown();
    }

    #[Test]
    public function it_resolves_the_http_driver_by_default(): void
    {
        config()->set('filament-chatbot.stream.transport', 'http');

        $this->assertInstanceOf(
            HttpStreamTransport::class,
            app(TransportManager::class)->driver(),
        );
    }

    #[Test]
    public function it_falls_back_to_the_http_driver_when_transport_config_is_missing(): void
    {
        config()->set('filament-chatbot.stream.transport', null);

        $this->assertInstanceOf(
            HttpStreamTransport::class,
            app(TransportManager::class)->driver(),
        );
    }

    #[Test]
    public function it_resolves_the_websocket_driver_when_configured(): void
    {
        config()->set('filament-chatbot.stream.transport', 'websocket');

        $this->assertInstanceOf(
            WebsocketStreamTransport::class,
            app(TransportManager::class)->driver(),
        );
    }

    #[Test]
    public function it_binds_the_stream_transport_contract_to_the_configured_driver(): void
    {
        config()->set('filament-chatbot.stream.transport', 'websocket');

        $this->assertInstanceOf(WebsocketStreamTransport::class, app(StreamTransport::class));
    }

    #[Test]
    #[DataProvider('driverNameProvider')]
    public function it_exposes_the_driver_name_for_the_frontend(string $configured, string $expectedName): void
    {
        config()->set('filament-chatbot.stream.transport', $configured);

        $this->assertSame($expectedName, app(TransportManager::class)->driver()->name());
    }

    public static function driverNameProvider(): array
    {
        return [
            'http' => ['http', 'http'],
            'websocket' => ['websocket', 'websocket'],
        ];
    }
}

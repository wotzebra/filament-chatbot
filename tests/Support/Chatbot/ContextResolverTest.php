<?php

namespace Wotz\FilamentChatbot\Tests\Support\Chatbot;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Support\Chatbot\ContextResolver;
use Wotz\FilamentChatbot\Tests\TestCase;

class ContextResolverTest extends TestCase
{
    #[Test]
    public function it_returns_null_when_the_raw_context_is_empty(): void
    {
        $request = Request::create('/');
        $request->headers->set('Referer', 'https://example.test/admin/orders/42');

        $this->assertNull((new ContextResolver)([], $request));
    }

    #[Test]
    public function it_includes_the_referer_url_as_page_metadata_when_a_record_is_given(): void
    {
        $request = Request::create('/');
        $request->headers->set('Referer', 'https://example.test/admin/orders/42');

        $resolved = (new ContextResolver)(['type' => 'order'], $request);

        $this->assertStringContainsString('Current page context:', $resolved);
        $this->assertStringContainsString('"url": "https://example.test/admin/orders/42"', $resolved);
    }

    #[Test]
    public function it_serializes_the_raw_context_as_json_under_the_record_key(): void
    {
        $resolved = (new ContextResolver)(
            ['type' => 'order', 'id' => 42, 'number' => 'O-42'],
            Request::create('/'),
        );

        $this->assertStringContainsString('Current page context:', $resolved);
        $this->assertStringContainsString('"type": "order"', $resolved);
        $this->assertStringContainsString('"id": 42', $resolved);
        $this->assertStringContainsString('"number": "O-42"', $resolved);
    }

    #[Test]
    public function it_includes_the_current_panel_id_as_page_metadata(): void
    {
        $resolved = (new ContextResolver)(
            ['type' => 'order'],
            Request::create('/'),
        );

        $this->assertStringContainsString('"panel": "test"', $resolved);
    }
}

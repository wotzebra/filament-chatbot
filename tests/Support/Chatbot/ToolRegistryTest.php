<?php

namespace Wotz\FilamentChatbot\Tests\Support\Chatbot;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use stdClass;
use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;
use Wotz\FilamentChatbot\Tests\Fakes\ExampleTool;
use Wotz\FilamentChatbot\Tests\TestCase;

class ToolRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filament-chatbot.tools', []);
    }

    #[Test]
    public function it_resolves_tools_from_config(): void
    {
        config()->set('filament-chatbot.tools', [ExampleTool::class]);

        $tools = app(ToolRegistry::class)->resolveTools();

        $this->assertCount(1, $tools);
        $this->assertInstanceOf(ExampleTool::class, $tools[0]);
    }

    #[Test]
    public function it_resolves_extra_tools_passed_at_call_time(): void
    {
        $tools = app(ToolRegistry::class)->resolveTools([ExampleTool::class]);

        $this->assertCount(1, $tools);
        $this->assertInstanceOf(ExampleTool::class, $tools[0]);
    }

    #[Test]
    public function it_merges_config_tools_with_extra_tools_without_duplicates(): void
    {
        config()->set('filament-chatbot.tools', [ExampleTool::class]);

        $tools = app(ToolRegistry::class)->resolveTools([ExampleTool::class]);

        $this->assertCount(1, $tools);
        $this->assertInstanceOf(ExampleTool::class, $tools[0]);
    }

    #[Test]
    public function it_throws_when_a_configured_tool_does_not_implement_the_tool_contract(): void
    {
        config()->set('filament-chatbot.tools', [stdClass::class]);

        $this->expectException(RuntimeException::class);

        app(ToolRegistry::class)->resolveTools();
    }
}

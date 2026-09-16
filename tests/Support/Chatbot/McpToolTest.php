<?php

namespace Wotz\FilamentChatbot\Tests\Support\Chatbot;

use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Support\Chatbot\McpTool;
use Wotz\FilamentChatbot\Tests\Fakes\ValidatingMcpTool;
use Wotz\FilamentChatbot\Tests\TestCase;

class McpToolTest extends TestCase
{
    #[Test]
    public function it_returns_the_structured_content_of_the_mcp_tool(): void
    {
        $tool = new McpTool(new ValidatingMcpTool);

        $this->assertSame('{"code":"ABC"}', $tool->handle(new Request(['code' => 'ABC'])));
    }

    #[Test]
    public function it_turns_a_validation_failure_into_a_tool_error_instead_of_throwing(): void
    {
        $tool = new McpTool(new ValidatingMcpTool);

        $result = $tool->handle(new Request([]));

        $this->assertStringStartsWith('MCP tool error: Invalid arguments.', $result);
        $this->assertStringContainsString('code: You must specify a code.', $result);
    }
}

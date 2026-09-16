<?php

namespace Wotz\FilamentChatbot\Tests\Fakes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('An MCP server tool used in tests.')]
class ExampleMcpTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::text('mcp result');
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

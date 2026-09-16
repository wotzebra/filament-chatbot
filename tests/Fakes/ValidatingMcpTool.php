<?php

namespace Wotz\FilamentChatbot\Tests\Fakes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('An MCP server tool that validates its input.')]
class ValidatingMcpTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->validate([
            'code' => 'required|string|min:3',
        ], [
            'code.required' => 'You must specify a code.',
        ]);

        return Response::structured(['code' => $request->string('code')->toString()]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->required(),
        ];
    }
}

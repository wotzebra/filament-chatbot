<?php

namespace Wotz\FilamentChatbot\Tests\Fakes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ExampleTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Example tool';
    }

    public function handle(Request $request): Stringable|string
    {
        return 'ok';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

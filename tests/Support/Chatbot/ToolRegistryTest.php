<?php

use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;
use Wotz\FilamentChatbot\Tests\Fakes\ExampleTool;

beforeEach(function () {
    config()->set('filament-chatbot.tools', []);
});

it('resolves tools from config', function () {
    config()->set('filament-chatbot.tools', [ExampleTool::class]);

    $tools = app(ToolRegistry::class)->resolveTools();

    expect($tools)->toHaveCount(1)
        ->and($tools[0])->toBeInstanceOf(ExampleTool::class);
});

it('resolves extra tools passed at call time', function () {
    $tools = app(ToolRegistry::class)->resolveTools([ExampleTool::class]);

    expect($tools)->toHaveCount(1)
        ->and($tools[0])->toBeInstanceOf(ExampleTool::class);
});

it('merges config tools with extra tools without duplicates', function () {
    config()->set('filament-chatbot.tools', [ExampleTool::class]);

    $tools = app(ToolRegistry::class)->resolveTools([ExampleTool::class]);

    expect($tools)->toHaveCount(1)
        ->and($tools[0])->toBeInstanceOf(ExampleTool::class);
});

it('throws when a configured tool does not implement the tool contract', function () {
    config()->set('filament-chatbot.tools', [stdClass::class]);

    expect(fn () => app(ToolRegistry::class)->resolveTools())
        ->toThrow(RuntimeException::class);
});

<?php

use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;
use Wotz\FilamentChatbot\Tests\Fakes\ExampleTool;

beforeEach(function () {
    config()->set('filament-chatbot.tools', []);
    app(ToolRegistry::class)->withTools([]);
});

it('reads instructions, provider, model and timeout from config', function () {
    config()->set('filament-chatbot.instructions', $instructions = fake()->sentence());
    config()->set('filament-chatbot.provider', $provider = fake()->word());
    config()->set('filament-chatbot.model', $model = fake()->slug());
    config()->set('filament-chatbot.timeout', $timeout = fake()->numberBetween(10, 120));

    $agent = app(Assistant::class);

    expect((string) $agent->instructions())->toBe($instructions)
        ->and($agent->provider())->toBe($provider)
        ->and($agent->model())->toBe($model)
        ->and($agent->timeout())->toBe($timeout);
});

it('deduplicates tools that appear in both config and the registry', function () {
    config()->set('filament-chatbot.tools', [ExampleTool::class]);
    app(ToolRegistry::class)->withTools([ExampleTool::class]);

    $tools = app(Assistant::class)->tools();

    expect($tools)->toHaveCount(1)
        ->and($tools[0])->toBeInstanceOf(ExampleTool::class);
});

<?php

use Illuminate\Support\Facades\Context;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Tests\Fakes\ExampleTool;

beforeEach(function () {
    config()->set('filament-chatbot.tools', []);
    Context::forgetHidden('chatbot.context');
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

it('deduplicates tools that appear in both config and the per-agent extras', function () {
    config()->set('filament-chatbot.tools', [ExampleTool::class]);

    $tools = app(Assistant::class)
        ->withExtraTools([ExampleTool::class])
        ->tools();

    expect($tools)->toHaveCount(1)
        ->and($tools[0])->toBeInstanceOf(ExampleTool::class);
});

it('appends the resolved context from Laravel Context to the instructions', function () {
    config()->set('filament-chatbot.instructions', 'Base instructions.');

    Context::addHidden('chatbot.context', 'Current page context: {...}');

    expect((string) app(Assistant::class)->instructions())
        ->toBe("Base instructions.\n\nCurrent page context: {...}");
});

it('returns only the context when base instructions are empty', function () {
    config()->set('filament-chatbot.instructions', '');

    Context::addHidden('chatbot.context', 'Context only');

    expect((string) app(Assistant::class)->instructions())->toBe('Context only');
});

it('returns only the base instructions when no context is resolved', function () {
    config()->set('filament-chatbot.instructions', 'Base instructions.');

    expect((string) app(Assistant::class)->instructions())->toBe('Base instructions.');
});

<?php

namespace Wotz\FilamentChatbot\Tests\Agents;

use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Tests\Fakes\ExampleTool;
use Wotz\FilamentChatbot\Tests\TestCase;

class AssistantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filament-chatbot.tools', []);

        Context::forgetHidden('chatbot.context');
    }

    #[Test]
    public function it_reads_instructions_provider_model_and_timeout_from_config(): void
    {
        config()->set('filament-chatbot.instructions', $instructions = fake()->sentence());
        config()->set('filament-chatbot.provider', $provider = fake()->word());
        config()->set('filament-chatbot.model', $model = fake()->slug());
        config()->set('filament-chatbot.timeout', $timeout = fake()->numberBetween(10, 120));

        $agent = app(Assistant::class);

        $this->assertSame($instructions, (string) $agent->instructions());
        $this->assertSame($provider, $agent->provider());
        $this->assertSame($model, $agent->model());
        $this->assertSame($timeout, $agent->timeout());
    }

    #[Test]
    public function it_deduplicates_tools_that_appear_in_both_config_and_the_per_agent_extras(): void
    {
        config()->set('filament-chatbot.tools', [ExampleTool::class]);

        $tools = app(Assistant::class)
            ->withExtraTools([ExampleTool::class])
            ->tools();

        $this->assertCount(1, $tools);
        $this->assertInstanceOf(ExampleTool::class, $tools[0]);
    }

    #[Test]
    public function it_appends_the_resolved_context_from_laravel_context_to_the_instructions(): void
    {
        config()->set('filament-chatbot.instructions', 'Base instructions.');

        Context::addHidden('chatbot.context', 'Current page context: {...}');

        $this->assertSame(
            "Base instructions.\n\nCurrent page context: {...}",
            (string) app(Assistant::class)->instructions(),
        );
    }

    #[Test]
    public function it_returns_only_the_context_when_base_instructions_are_empty(): void
    {
        config()->set('filament-chatbot.instructions', '');

        Context::addHidden('chatbot.context', 'Context only');

        $this->assertSame('Context only', (string) app(Assistant::class)->instructions());
    }

    #[Test]
    public function it_returns_only_the_base_instructions_when_no_context_is_resolved(): void
    {
        config()->set('filament-chatbot.instructions', 'Base instructions.');

        $this->assertSame('Base instructions.', (string) app(Assistant::class)->instructions());
    }
}

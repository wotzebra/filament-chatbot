<?php

namespace Wotz\FilamentChatbot\Tests\Filament\Plugins;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Support\Chatbot\ContextResolver;
use Wotz\FilamentChatbot\Tests\Fakes\ExampleTool;
use Wotz\FilamentChatbot\Tests\TestCase;

class ChatbotPluginTest extends TestCase
{
    #[Test]
    public function it_returns_sensible_defaults_for_chatbot_ui_settings(): void
    {
        $plugin = ChatbotPlugin::make();

        $this->assertSame('AI Assistant', $plugin->getBotName());
        $this->assertSame('Hello! How can I help you?', $plugin->getWelcomeMessage());
        $this->assertSame('Open chatbot', $plugin->getButtonText());
        $this->assertSame('heroicon-o-chat-bubble-left-right', $plugin->getButtonIcon());
        $this->assertSame('400px', $plugin->getChatWidth());
        $this->assertSame('600px', $plugin->getChatHeight());
        $this->assertNull($plugin->getLogoUrl());
    }

    #[Test]
    public function it_reads_agent_provider_model_and_enabled_from_config_as_fallback(): void
    {
        config()->set('filament-chatbot.agent', Assistant::class);
        config()->set('filament-chatbot.provider', $provider = fake()->word());
        config()->set('filament-chatbot.model', $model = fake()->slug());
        config()->set('filament-chatbot.enabled', false);

        $plugin = ChatbotPlugin::make();

        $this->assertSame(Assistant::class, $plugin->getAgentClass());
        $this->assertSame($provider, $plugin->getProvider());
        $this->assertSame($model, $plugin->getModel());
        $this->assertFalse($plugin->isEnabled());
    }

    #[Test]
    public function it_stores_panel_tools_configured_through_the_plugin(): void
    {
        $plugin = ChatbotPlugin::make()->tools([ExampleTool::class]);

        $this->assertSame([ExampleTool::class], $plugin->getTools());
    }

    #[Test]
    public function it_throws_when_no_agent_is_configured(): void
    {
        config()->set('filament-chatbot.agent', null);

        $this->expectException(RuntimeException::class);

        ChatbotPlugin::make()->getAgentClass();
    }

    #[Test]
    public function it_defaults_is_enabled_to_an_auth_check_when_config_is_null(): void
    {
        config()->set('filament-chatbot.enabled', null);

        $plugin = ChatbotPlugin::make();

        $this->assertFalse($plugin->isEnabled());

        $user = new User;
        $user->id = fake()->randomNumber();
        auth()->setUser($user);

        $this->assertTrue($plugin->isEnabled());
    }

    #[Test]
    #[DataProvider('setterProvider')]
    public function it_resolves_setters_from_a_raw_value_or_closure(string $setter, string $getter, mixed $value): void
    {
        $this->assertSame($value, ChatbotPlugin::make()->$setter($value)->$getter());
        $this->assertSame($value, ChatbotPlugin::make()->$setter(fn () => $value)->$getter());
    }

    public static function setterProvider(): array
    {
        return [
            'agent' => ['agent', 'getAgentClass', Assistant::class],
            'botName' => ['botName', 'getBotName', 'My Bot'],
            'welcomeMessage' => ['welcomeMessage', 'getWelcomeMessage', 'Welcome'],
            'buttonText' => ['buttonText', 'getButtonText', 'Open chat'],
            'buttonIcon' => ['buttonIcon', 'getButtonIcon', 'heroicon-o-bell'],
            'chatWidth' => ['chatWidth', 'getChatWidth', '500px'],
            'chatHeight' => ['chatHeight', 'getChatHeight', '700px'],
            'provider' => ['provider', 'getProvider', 'openai'],
            'model' => ['model', 'getModel', 'gpt-4'],
            'logoUrl' => ['logoUrl', 'getLogoUrl', '/path/to/logo.png'],
            'conversationKey' => ['conversationKey', 'getConversationKey', 'custom_key'],
        ];
    }

    #[Test]
    public function it_resolves_is_enabled_from_a_closure(): void
    {
        $this->assertTrue(ChatbotPlugin::make()->enabled(fn () => true)->isEnabled());
        $this->assertFalse(ChatbotPlugin::make()->enabled(fn () => false)->isEnabled());
    }

    #[Test]
    public function it_defaults_the_context_resolver_to_the_context_resolver_class(): void
    {
        $this->assertInstanceOf(ContextResolver::class, ChatbotPlugin::make()->getContextResolver());
    }

    #[Test]
    public function it_accepts_a_closure_as_context_resolver(): void
    {
        $plugin = ChatbotPlugin::make()
            ->contextResolver(fn (array $context, Request $request) => 'resolved: ' . json_encode($context));

        $resolved = ($plugin->getContextResolver())(['foo' => 'bar'], Request::create('/'));

        $this->assertSame('resolved: {"foo":"bar"}', $resolved);
    }

    #[Test]
    public function it_accepts_a_class_string_as_context_resolver(): void
    {
        $plugin = ChatbotPlugin::make()->contextResolver(ContextResolver::class);

        $this->assertInstanceOf(ContextResolver::class, $plugin->getContextResolver());
    }

    #[Test]
    public function it_defaults_the_conversation_session_key_to_the_current_panel_id(): void
    {
        $panelId = filament()->getCurrentPanel()->getId();

        $this->assertSame(
            "ai_chatbot_conversation_id_{$panelId}",
            ChatbotPlugin::make()->getConversationKey(),
        );
    }
}

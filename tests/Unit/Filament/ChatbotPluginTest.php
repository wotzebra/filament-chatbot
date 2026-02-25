<?php

use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Tests\Fakes\ExampleTool;

it('returns sensible defaults for chatbot ui settings', function () {
    $plugin = ChatbotPlugin::make();

    expect($plugin->getBotName())->toBe('AI Assistent')
        ->and($plugin->getWelcomeMessage())->toBe('Hello! How can i help you?')
        ->and($plugin->getButtonText())->toBe('Open chatbot')
        ->and($plugin->getButtonIcon())->toBe('heroicon-o-chat-bubble-left-right')
        ->and($plugin->getChatWidth())->toBe('400px')
        ->and($plugin->getChatHeight())->toBe('600px');
});

it('reads agent and ui settings from config as fallback', function () {
    config()->set('filament-chatbot.agent', \Wotz\FilamentChatbot\Agents\Assistant::class);
    config()->set('filament-chatbot.provider', $provider = fake()->word());
    config()->set('filament-chatbot.model', $model = fake()->slug());
    config()->set('filament-chatbot.enabled', false);
    config()->set('filament-chatbot.bot_name', $botName = fake()->name());
    config()->set('filament-chatbot.welcome_message', $welcomeMessage = fake()->sentence());
    config()->set('filament-chatbot.button_text', $buttonText = fake()->words(2, true));
    config()->set('filament-chatbot.button_icon', $buttonIcon = fake()->slug());
    config()->set('filament-chatbot.chat_width', $chatWidth = fake()->numberBetween(200, 600) . 'px');
    config()->set('filament-chatbot.chat_height', $chatHeight = fake()->numberBetween(400, 800) . 'px');

    $plugin = ChatbotPlugin::make();

    expect($plugin->getAgentClass())->toBe(\Wotz\FilamentChatbot\Agents\Assistant::class)
        ->and($plugin->getProvider())->toBe($provider)
        ->and($plugin->getModel())->toBe($model)
        ->and($plugin->isEnabled())->toBeFalse()
        ->and($plugin->getBotName())->toBe($botName)
        ->and($plugin->getWelcomeMessage())->toBe($welcomeMessage)
        ->and($plugin->getButtonText())->toBe($buttonText)
        ->and($plugin->getButtonIcon())->toBe($buttonIcon)
        ->and($plugin->getChatWidth())->toBe($chatWidth)
        ->and($plugin->getChatHeight())->toBe($chatHeight);
});

it('uses fluent configured values and respects enabled false', function () {
    $plugin = ChatbotPlugin::make()
        ->enabled(false)
        ->botName($botName = fake()->name())
        ->welcomeMessage($welcomeMessage = fake()->sentence())
        ->buttonText($buttonText = fake()->words(2, true))
        ->buttonIcon($buttonIcon = fake()->slug())
        ->chatWidth($chatWidth = fake()->numberBetween(200, 600) . 'px')
        ->chatHeight($chatHeight = fake()->numberBetween(400, 800) . 'px');

    expect($plugin->isEnabled())->toBeFalse()
        ->and($plugin->getBotName())->toBe($botName)
        ->and($plugin->getWelcomeMessage())->toBe($welcomeMessage)
        ->and($plugin->getButtonText())->toBe($buttonText)
        ->and($plugin->getButtonIcon())->toBe($buttonIcon)
        ->and($plugin->getChatWidth())->toBe($chatWidth)
        ->and($plugin->getChatHeight())->toBe($chatHeight);
});

it('stores panel tools configured through the plugin', function () {
    $plugin = ChatbotPlugin::make()->tools([ExampleTool::class]);

    expect($plugin->getTools())->toBe([ExampleTool::class]);
});

it('throws a RuntimeException when no agent is configured', function () {
    config()->set('filament-chatbot.agent', null);

    expect(fn () => ChatbotPlugin::make()->getAgentClass())
        ->toThrow(RuntimeException::class);
});

it('returns auth check as default for isEnabled when config is null', function () {
    config()->set('filament-chatbot.enabled', null);

    $plugin = ChatbotPlugin::make();

    expect($plugin->isEnabled())->toBeFalse();

    $user = new \Illuminate\Foundation\Auth\User;
    $user->id = fake()->randomNumber();
    auth()->setUser($user);

    expect($plugin->isEnabled())->toBeTrue();
});

it('resolves isEnabled via closure', function () {
    expect(ChatbotPlugin::make()->enabled(fn () => true)->isEnabled())->toBeTrue();
    expect(ChatbotPlugin::make()->enabled(fn () => false)->isEnabled())->toBeFalse();
});

it('resolves agent via closure', function () {
    $plugin = ChatbotPlugin::make()->agent(fn () => \Wotz\FilamentChatbot\Agents\Assistant::class);

    expect($plugin->getAgentClass())->toBe(\Wotz\FilamentChatbot\Agents\Assistant::class);
});

it('resolves botName via closure', function () {
    $name = fake()->name();
    $plugin = ChatbotPlugin::make()->botName(fn () => $name);

    expect($plugin->getBotName())->toBe($name);
});

it('resolves welcomeMessage via closure', function () {
    $message = fake()->sentence();
    $plugin = ChatbotPlugin::make()->welcomeMessage(fn () => $message);

    expect($plugin->getWelcomeMessage())->toBe($message);
});

it('resolves provider via closure', function () {
    $provider = fake()->word();
    $plugin = ChatbotPlugin::make()->provider(fn () => $provider);

    expect($plugin->getProvider())->toBe($provider);
});

it('resolves model via closure', function () {
    $model = fake()->slug();
    $plugin = ChatbotPlugin::make()->model(fn () => $model);

    expect($plugin->getModel())->toBe($model);
});

it('returns null logo url by default', function () {
    expect(ChatbotPlugin::make()->getLogoUrl())->toBeNull();
});

it('resolves logoUrl from a value', function () {
    $plugin = ChatbotPlugin::make()->logoUrl($url = '/' . fake()->slug() . '/logo.png');

    expect($plugin->getLogoUrl())->toBe($url);
});

it('resolves logoUrl via closure', function () {
    $url = '/' . fake()->slug() . '/logo.png';
    $plugin = ChatbotPlugin::make()->logoUrl(fn () => $url);

    expect($plugin->getLogoUrl())->toBe($url);
});

it('resolves conversationKey from an explicit value', function () {
    $plugin = ChatbotPlugin::make()->conversationKey($key = fake()->slug());

    expect($plugin->getConversationKey())->toBe($key);
});

it('resolves conversationKey via closure', function () {
    $key = fake()->slug();
    $plugin = ChatbotPlugin::make()->conversationKey(fn () => $key);

    expect($plugin->getConversationKey())->toBe($key);
});

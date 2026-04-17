<?php

use Illuminate\Foundation\Auth\User;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Tests\Fakes\ExampleTool;

it('returns sensible defaults for chatbot ui settings', function () {
    $plugin = ChatbotPlugin::make();

    expect($plugin->getBotName())->toBe('AI Assistant')
        ->and($plugin->getWelcomeMessage())->toBe('Hello! How can I help you?')
        ->and($plugin->getButtonText())->toBe('Open chatbot')
        ->and($plugin->getButtonIcon())->toBe('heroicon-o-chat-bubble-left-right')
        ->and($plugin->getChatWidth())->toBe('400px')
        ->and($plugin->getChatHeight())->toBe('600px')
        ->and($plugin->getLogoUrl())->toBeNull();
});

it('reads agent, provider, model and enabled from config as fallback', function () {
    config()->set('filament-chatbot.agent', Assistant::class);
    config()->set('filament-chatbot.provider', $provider = fake()->word());
    config()->set('filament-chatbot.model', $model = fake()->slug());
    config()->set('filament-chatbot.enabled', false);

    $plugin = ChatbotPlugin::make();

    expect($plugin->getAgentClass())->toBe(Assistant::class)
        ->and($plugin->getProvider())->toBe($provider)
        ->and($plugin->getModel())->toBe($model)
        ->and($plugin->isEnabled())->toBeFalse();
});

it('stores panel tools configured through the plugin', function () {
    $plugin = ChatbotPlugin::make()->tools([ExampleTool::class]);

    expect($plugin->getTools())->toBe([ExampleTool::class]);
});

it('throws when no agent is configured', function () {
    config()->set('filament-chatbot.agent', null);

    expect(fn () => ChatbotPlugin::make()->getAgentClass())
        ->toThrow(RuntimeException::class);
});

it('defaults isEnabled to an auth check when config is null', function () {
    config()->set('filament-chatbot.enabled', null);

    $plugin = ChatbotPlugin::make();

    expect($plugin->isEnabled())->toBeFalse();

    $user = new User;
    $user->id = fake()->randomNumber();
    auth()->setUser($user);

    expect($plugin->isEnabled())->toBeTrue();
});

it('resolves setters from a raw value or closure', function (string $setter, string $getter, mixed $value) {
    expect(ChatbotPlugin::make()->$setter($value)->$getter())->toBe($value);
    expect(ChatbotPlugin::make()->$setter(fn () => $value)->$getter())->toBe($value);
})->with([
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
]);

it('resolves isEnabled from a closure', function () {
    expect(ChatbotPlugin::make()->enabled(fn () => true)->isEnabled())->toBeTrue()
        ->and(ChatbotPlugin::make()->enabled(fn () => false)->isEnabled())->toBeFalse();
});

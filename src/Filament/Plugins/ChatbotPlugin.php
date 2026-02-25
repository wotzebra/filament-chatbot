<?php

namespace Wotz\FilamentChatbot\Filament\Plugins;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use RuntimeException;

class ChatbotPlugin implements Plugin
{
    protected array $tools = [];

    protected bool|Closure|null $enabled = null;

    protected string|Closure|null $conversationKey = null;

    protected string|Closure|null $agent = null;

    protected string|array|Closure|null $provider = null;

    protected string|Closure|null $model = null;

    protected string|Closure|null $botName = null;

    protected string|Closure|null $welcomeMessage = null;

    protected string|Closure|null $buttonText = null;

    protected string|Closure|null $buttonIcon = null;

    protected string|Closure|null $chatWidth = null;

    protected string|Closure|null $chatHeight = null;

    protected string|Closure|null $logoUrl = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'chatbot';
    }

    public function tools(array $tools): static
    {
        $this->tools = $tools;

        return $this;
    }

    public function getTools(): array
    {
        return $this->tools;
    }

    public function conversationKey(string|Closure $key): static
    {
        $this->conversationKey = $key;

        return $this;
    }

    public function getConversationKey(): string
    {
        return (string) $this->resolveProp(
            $this->conversationKey ?? fn () => 'ai_chatbot_conversation_id_' . filament()->getCurrentPanel()->getId(),
        );
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(
            PanelsRenderHook::BODY_END,
            fn () => view('filament-chatbot::components.filament-chatbot-widget'),
        );
    }

    public function boot(Panel $panel): void {}

    public function agent(string|Closure $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getAgentClass(): string
    {
        $resolved = $this->resolveProp($this->agent, config('filament-chatbot.agent'));

        if (! is_string($resolved) || $resolved === '') {
            throw new RuntimeException('No agent configured for ChatbotPlugin. Call ->agent(MyAgent::class) when registering the plugin.');
        }

        return $resolved;
    }

    public function provider(string|array|Closure|null $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): string|array|null
    {
        return $this->resolveProp($this->provider, config('filament-chatbot.provider'));
    }

    public function model(string|Closure|null $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->resolveProp($this->model, config('filament-chatbot.model'));
    }

    public function enabled(bool|Closure $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isEnabled(): bool
    {
        if (! is_null($this->enabled)) {
            return is_callable($this->enabled) ? ($this->enabled)() : $this->enabled;
        }

        $configEnabled = config('filament-chatbot.enabled');

        return is_null($configEnabled) ? auth()->check() : (bool) $configEnabled;
    }

    public function logoUrl(string|Closure|null $url): static
    {
        $this->logoUrl = $url;

        return $this;
    }

    public function getLogoUrl(): ?string
    {
        return $this->resolveProp($this->logoUrl);
    }

    public function botName(string|Closure $name): static
    {
        $this->botName = $name;

        return $this;
    }

    public function getBotName(): string
    {
        return (string) $this->resolveProp($this->botName, config('filament-chatbot.bot_name'));
    }

    public function welcomeMessage(string|Closure $message): static
    {
        $this->welcomeMessage = $message;

        return $this;
    }

    public function getWelcomeMessage(): string
    {
        return (string) $this->resolveProp($this->welcomeMessage, config('filament-chatbot.welcome_message'));
    }

    public function buttonText(string|Closure $text): static
    {
        $this->buttonText = $text;

        return $this;
    }

    public function getButtonText(): string
    {
        return (string) $this->resolveProp($this->buttonText, config('filament-chatbot.button_text'));
    }

    public function buttonIcon(string|Closure $icon): static
    {
        $this->buttonIcon = $icon;

        return $this;
    }

    public function getButtonIcon(): string
    {
        return (string) $this->resolveProp($this->buttonIcon, config('filament-chatbot.button_icon'));
    }

    public function chatWidth(string|Closure $width): static
    {
        $this->chatWidth = $width;

        return $this;
    }

    public function getChatWidth(): string
    {
        return (string) $this->resolveProp($this->chatWidth, config('filament-chatbot.chat_width'));
    }

    public function chatHeight(string|Closure $height): static
    {
        $this->chatHeight = $height;

        return $this;
    }

    public function getChatHeight(): string
    {
        return (string) $this->resolveProp($this->chatHeight, config('filament-chatbot.chat_height'));
    }

    protected function resolveProp(mixed $value, mixed $default = null): mixed
    {
        return is_callable($value) ? ($value)() : ($value ?? $default);
    }
}

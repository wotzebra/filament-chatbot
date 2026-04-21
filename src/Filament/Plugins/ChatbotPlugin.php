<?php

namespace Wotz\FilamentChatbot\Filament\Plugins;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use RuntimeException;
use Wotz\FilamentChatbot\Support\Chatbot\ContextResolver;

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

    protected string|Closure|null $userModel = null;

    protected string|Closure|null $contextResolver = null;

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
            $this->conversationKey ?? fn (): string => 'ai_chatbot_conversation_id_' . $this->resolveCurrentPanelId(),
        );
    }

    public function getActiveStreamsSessionKey(): string
    {
        return $this->getConversationKey() . '_active_streams';
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(
            $this->resolveBodyEndRenderHook(),
            fn (): View => view('filament-chatbot::components.filament-chatbot-widget'),
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
        return (string) $this->resolveProp($this->botName, 'AI Assistant');
    }

    public function welcomeMessage(string|Closure $message): static
    {
        $this->welcomeMessage = $message;

        return $this;
    }

    public function getWelcomeMessage(): string
    {
        return (string) $this->resolveProp($this->welcomeMessage, 'Hello! How can I help you?');
    }

    public function buttonText(string|Closure $text): static
    {
        $this->buttonText = $text;

        return $this;
    }

    public function getButtonText(): string
    {
        return (string) $this->resolveProp($this->buttonText, 'Open chatbot');
    }

    public function buttonIcon(string|Closure $icon): static
    {
        $this->buttonIcon = $icon;

        return $this;
    }

    public function getButtonIcon(): string
    {
        return (string) $this->resolveProp($this->buttonIcon, 'heroicon-o-chat-bubble-left-right');
    }

    public function chatWidth(string|Closure $width): static
    {
        $this->chatWidth = $width;

        return $this;
    }

    public function getChatWidth(): string
    {
        return (string) $this->resolveProp($this->chatWidth, '400px');
    }

    public function chatHeight(string|Closure $height): static
    {
        $this->chatHeight = $height;

        return $this;
    }

    public function getChatHeight(): string
    {
        return (string) $this->resolveProp($this->chatHeight, '600px');
    }

    public function userModel(string|Closure $userModel): static
    {
        $this->userModel = $userModel;

        return $this;
    }

    public function getUserModel(): string
    {
        return (string) $this->resolveProp(
            $this->userModel,
            config('filament-chatbot.user_model', config('auth.providers.users.model')),
        );
    }

    public function contextResolver(string|Closure|null $resolver): static
    {
        $this->contextResolver = $resolver;

        return $this;
    }

    public function getContextResolver(): callable
    {
        $resolver = $this->contextResolver;

        if ($resolver instanceof Closure) {
            return $resolver;
        }

        if (is_string($resolver) && $resolver !== '') {
            return app($resolver);
        }

        return app(ContextResolver::class);
    }

    protected function resolveProp(mixed $value, mixed $default = null): mixed
    {
        return is_callable($value) ? ($value)() : ($value ?? $default);
    }

    protected function resolveCurrentPanelId(): string
    {
        return filament()->getCurrentPanel()?->getId() ?? 'default';
    }

    protected function resolveBodyEndRenderHook(): string
    {
        return PanelsRenderHook::BODY_END;
    }
}

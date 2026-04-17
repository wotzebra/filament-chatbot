<?php

namespace Wotz\FilamentChatbot\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ChatbotResourcePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'chatbot-resources';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__ . '/../Resources',
            for: 'Wotz\\FilamentChatbot\\Filament\\Resources',
        );
    }

    public function boot(Panel $panel): void {}
}

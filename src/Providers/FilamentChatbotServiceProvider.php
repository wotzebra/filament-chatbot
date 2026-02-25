<?php

namespace Wotz\FilamentChatbot\Providers;

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wotz\FilamentChatbot\Http\Controllers\ChatStreamController;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;

class FilamentChatbotServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-chatbot')
            ->hasConfigFile('filament-chatbot')
            ->hasViews()
            ->hasMigration('create_agent_conversations_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ToolRegistry::class);
    }

    public function packageBooted(): void
    {
        Livewire::component('chatbot-widget', ChatbotWidget::class);

        Route::get((string) config('filament-chatbot.route.path'), ChatStreamController::class)
            ->middleware(config('filament-chatbot.route.middleware', ['auth', 'web']))
            ->name((string) config('filament-chatbot.route.name'));
    }
}

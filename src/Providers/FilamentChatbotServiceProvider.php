<?php

namespace Wotz\FilamentChatbot\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Http\Controllers\ChatStreamController;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Services\ChatConfig;
use Wotz\FilamentChatbot\Services\ChatManager;
use Wotz\FilamentChatbot\Streaming\TransportManager;
use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;

class FilamentChatbotServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-chatbot')
            ->setBasePath(__DIR__ . '/../')
            ->hasConfigFile('filament-chatbot')
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_agent_conversations_table')
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->endWith(function (InstallCommand $command): void {
                        $command->callSilently('filament:assets');

                        $command->info('Filament assets have been published.');
                    });
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(ChatManager::class, fn ($app): ChatManager => new ChatManager(
            ChatConfig::fromConfig(),
        ));

        $this->app->singleton(TransportManager::class);
        $this->app->bind(
            StreamTransport::class,
            fn ($app): StreamTransport => $app->make(TransportManager::class)->driver(),
        );
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-chatbot', __DIR__ . '/../../resources/dist/filament-chatbot.css')
                ->loadedOnRequest(),
        ], 'wotz/filament-chatbot');

        Livewire::component('chatbot-widget', ChatbotWidget::class);

        Route::post('ai/chatbot/stream', ChatStreamController::class)
            ->middleware(config('filament-chatbot.route_middleware', ['auth', 'web']))
            ->name('chatbot.stream');

        if (config('filament-chatbot.stream.transport') === 'websocket') {
            $this->loadChannelsFrom(__DIR__ . '/../../routes/channels.php');
        }
    }

    protected function loadChannelsFrom(string $path): void
    {
        if (file_exists($path)) {
            require $path;
        }
    }
}

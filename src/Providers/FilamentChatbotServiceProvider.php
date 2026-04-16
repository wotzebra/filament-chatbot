<?php

namespace Wotz\FilamentChatbot\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
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
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-chatbot', __DIR__ . '/../../resources/dist/filament-chatbot.css')
                ->loadedOnRequest(),
        ], 'wotz/filament-chatbot');

        Livewire::component('chatbot-widget', ChatbotWidget::class);

        Route::get('ai/chatbot/stream', ChatStreamController::class)
            ->middleware(config('filament-chatbot.route_middleware', ['auth', 'web']))
            ->name('chatbot.stream');
    }
}

# Installation

## Requirements

- PHP 8.3+
- Filament 4.x or 5.x
- [Laravel AI](https://github.com/laravel/ai) (installed automatically as a dependency)

## Install the package

```bash
composer require wotz/filament-chatbot
```

Run the install command. This publishes the config file and migrations, and optionally runs the migrations:

```bash
php artisan filament-chatbot:install
```

Configure your AI provider in `config/ai.php` by adding the API key for your chosen provider. The chatbot uses whichever provider is set as `default` in that file.

## Register the Filament plugin

In your panel provider:

```php
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(ChatbotPlugin::make());
```

That is enough to render the widget on every page of the panel. Continue with the [Plugin API](plugin-api.md) to customize it.

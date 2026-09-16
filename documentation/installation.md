# Installation

## Requirements

- PHP 8.3+
- Filament 3.2+ (this branch) — use `master` for Filament 4.x / 5.x
- [Laravel AI](https://github.com/laravel/ai) (installed automatically as a dependency)

## Install the package

This branch is not tagged; require it through the VCS repository:

```bash
composer config repositories.filament-chatbot vcs https://github.com/wotzebra/filament-chatbot
composer require wotz/filament-chatbot:dev-feature/filament-v3
```

Run the install command. This publishes the config file and migrations, and optionally runs the migrations:

```bash
php artisan filament-chatbot:install
```

Configure your AI provider in `config/ai.php` by adding the API key for your chosen provider. The chatbot uses whichever provider is set as `default` in that file.

## Add the views to your Filament theme

On Filament v3 the widget's Tailwind classes are compiled by your panel's [custom theme](https://filamentphp.com/docs/3.x/panels/themes#creating-a-custom-theme), like other Filament v3 plugins. Add the package views to the `content` array of your theme's `tailwind.config.js` and rebuild:

```js
content: [
    // ...
    './vendor/wotz/filament-chatbot/resources/views/**/*.blade.php',
]
```

The package only ships its own small stylesheet (bubbles, tints, scrollbars), registered through `FilamentAsset`.

## Register the Filament plugin

In your panel provider:

```php
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(ChatbotPlugin::make());
```

That is enough to render the widget on every page of the panel. Continue with the [Plugin API](plugin-api.md) to customize it.

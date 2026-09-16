# Filament Chatbot

A Filament plugin that adds a floating, streaming chatbot widget to your panel using Laravel AI.

> This is the **Filament v3** branch (`feature/filament-v3`). Filament v4 and v5 are supported on `master`.

## Requirements

- PHP 8.3+
- Filament 3.2+ (this branch) — use `master` for Filament 4.x / 5.x
- [Laravel AI](https://github.com/laravel/ai) (installed automatically as a dependency)

## Quick start

```bash
composer config repositories.filament-chatbot vcs https://github.com/wotzebra/filament-chatbot
composer require wotz/filament-chatbot:dev-feature/filament-v3
php artisan filament-chatbot:install
```

Configure your AI provider in `config/ai.php`, then register the plugin in your Filament panel provider:

```php
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(ChatbotPlugin::make());
```

## Add the views to your Filament theme

On Filament v3 the widget's Tailwind classes are compiled by your panel's [custom theme](https://filamentphp.com/docs/3.x/panels/themes#creating-a-custom-theme), like other Filament v3 plugins. Add the package views to the `content` array of your theme's `tailwind.config.js` and rebuild:

```js
content: [
    // ...
    './vendor/wotz/filament-chatbot/resources/views/**/*.blade.php',
]
```

The package only ships its own small stylesheet (bubbles, tints, scrollbars), registered through `FilamentAsset`.

## Documentation

Full documentation lives in [`documentation/`](documentation/index.md):

- [Installation](documentation/installation.md)
- [Plugin API](documentation/plugin-api.md)
- [Configuration reference](documentation/configuration.md)
- [Custom agent](documentation/agents.md)
- [Tools](documentation/tools.md)
- [Conversations](documentation/conversations.md)
- [Page context](documentation/page-context.md)
- [UI customization](documentation/ui-customization.md)
- [Streaming (HTTP / WebSocket)](documentation/streaming.md)

## Project links

- [Changelog](CHANGELOG.md)
- [Upgrade guide](UPGRADING.md)
- [Contributing](CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)

## Security

If you discover a security vulnerability, please email `info@codedor.be` instead of opening a public issue.

## License

MIT. See [LICENSE.md](LICENSE.md).

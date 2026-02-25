# Filament Chatbot

A Filament plugin (v3, v4, v5) that adds a floating, streaming chatbot widget to your panel using Laravel AI.

## Documentation

For detailed setup instructions and the complete reference, see the full documentation:


- [Installation](docs/index.md#installation)
- [Register the Filament plugin](docs/index.md#register-the-filament-plugin)
- [Plugin API](docs/index.md#plugin-api)
- [Configuration reference](docs/index.md#configuration-reference)
- [Custom agent](docs/index.md#custom-agent)
- [Tools](docs/index.md#tools)
- [Conversations and persistence](docs/index.md#conversations-and-persistence)

**Full documentation:** [docs/index.md](docs/index.md)

## Quick Start

Install the package:

```bash
composer require wotz/filament-chatbot
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag="filament-chatbot-migrations"
php artisan migrate
```

Register the plugin in your Filament panel provider:

```php
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(ChatbotPlugin::make());
```

Optional: publish config:

```bash
php artisan vendor:publish --tag="filament-chatbot-config"
```

## Project Links

- [Changelog](CHANGELOG.md)
- [Upgrade guide](UPGRADING.md)
- [Contributing](CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)

## Security

If you discover a security vulnerability, please email `info@codedor.be` instead of opening a public issue.

## License

MIT. See [LICENSE.md](LICENSE.md).

# Filament Chatbot

A Filament plugin (v4, v5) that adds a floating, streaming chatbot widget to your panel using Laravel AI.

## Documentation

For detailed setup instructions and the complete reference, see the full documentation:

- [Installation](docs/index.md#installation)
- [Register the Filament plugin](docs/index.md#register-the-filament-plugin)
- [Plugin API](docs/index.md#plugin-api)
- [Configuration reference](docs/index.md#configuration-reference)
- [Custom agent](docs/index.md#custom-agent)
- [Tools](docs/index.md#tools)
- [Conversations and persistence](docs/index.md#conversations-and-persistence)
- [Page context](docs/index.md#page-context)
- [Conversation resource](docs/index.md#conversation-resource)
- [Stream transport (HTTP / WebSocket)](docs/index.md#stream-transport)

**Full documentation:** [docs/index.md](docs/index.md)

## Requirements

- PHP 8.3+
- Filament 4.x or 5.x
- [Laravel AI](https://github.com/laravel/ai) (installed automatically as a dependency)

## Quick Start

Install the package:

```bash
composer require wotz/filament-chatbot
```

Run the install command. This publishes the config file and migrations, and optionally runs the migrations:

```bash
php artisan filament-chatbot:install
```

Configure your AI provider in `config/ai.php` by adding the API key for your chosen provider. The chatbot uses whichever provider is set as `default` in that file.

Register the plugin in your Filament panel provider:

```php
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(ChatbotPlugin::make());
```

## Stream Transport

By default the chatbot streams AI responses back to the browser over HTTP using Server-Sent Events. For setups where SSE is buffered by an intermediate proxy, or when you want realtime multi-tab synchronization, you can switch to a WebSocket transport powered by Laravel Reverb broadcasting.

The transport is selected once via configuration and the rest of the package (controller, Livewire widget, Alpine handler) adapts automatically. There are no parallel codepaths in your application code.

### HTTP (default)

Zero configuration. The widget POSTs to `ai/chatbot/stream` and reads the SSE response body in the browser.

### WebSocket via Laravel Reverb

1. Install Reverb and a queue worker capable broadcasting driver in the consuming app:

   ```bash
   composer require laravel/reverb
   php artisan reverb:install
   ```

2. Set the chatbot transport and broadcasting connection in `.env`:

   ```dotenv
   FILAMENT_CHATBOT_STREAM_TRANSPORT=websocket
   BROADCAST_CONNECTION=reverb
   QUEUE_CONNECTION=database
   ```

   > Warning: leaving `QUEUE_CONNECTION=sync` will block the HTTP request for the full duration of the agent stream because the broadcasting job runs inline. Use `database`, `redis`, or any non-sync driver in WebSocket mode.

3. Bootstrap Laravel Echo + the Reverb client in your application's frontend (`resources/js/bootstrap.js`):

   ```js
   import Echo from 'laravel-echo';
   import Pusher from 'pusher-js';

   window.Pusher = Pusher;

   window.Echo = new Echo({
       broadcaster: 'reverb',
       key: import.meta.env.VITE_REVERB_APP_KEY,
       wsHost: import.meta.env.VITE_REVERB_HOST,
       wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
       wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
       forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
       enabledTransports: ['ws', 'wss'],
   });
   ```

4. Run the Reverb server and a queue worker in addition to the usual web server:

   ```bash
   php artisan reverb:start
   php artisan queue:work
   ```

See the [Laravel Broadcasting documentation](https://laravel.com/docs/broadcasting) for the underlying mechanics.

### Configuration

| Variable | Config key | Default | Description |
|---|---|---|---|
| `FILAMENT_CHATBOT_STREAM_TRANSPORT` | `stream.transport` | `http` | `http` for SSE, `websocket` for Reverb broadcasting |
| `FILAMENT_CHATBOT_BROADCASTING_QUEUE` | `stream.websocket.queue` | `null` | Queue used by the streaming job. Broadcasts go through the default `BROADCAST_CONNECTION` |

### Trade-offs

| Concern | HTTP (SSE) | WebSocket (Reverb) |
|---|---|---|
| First-token latency | Lowest, single request | Slight overhead (job dispatch + channel connect) |
| Infrastructure | None beyond the web server | Requires Reverb server + queue worker |
| Proxy / load balancer compatibility | Can be buffered or terminated by proxies | Persistent connection, generally proxy-friendly |
| Multi-tab synchronization | Each tab streams independently | Tabs subscribed to the same conversation channel see the same stream |
| Background pushes (outside web request) | Not possible | Supported, any code can broadcast on the channel |

### Authorization

Streams are delivered on the private channel `chatbot.conversation.{conversationId}`. Authorization is registered automatically when the WebSocket transport is active and only allows the conversation owner to subscribe.

## Project Links

- [Changelog](CHANGELOG.md)
- [Upgrade guide](UPGRADING.md)
- [Contributing](CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)

## Security

If you discover a security vulnerability, please email `info@codedor.be` instead of opening a public issue.

## License

MIT. See [LICENSE.md](LICENSE.md).

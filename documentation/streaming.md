# Streaming

The chatbot streams AI responses to the browser over either HTTP (Server-Sent Events, the default) or WebSocket (via Laravel Reverb broadcasting). One configuration value picks the transport for both the controller and the Livewire widget. There are no parallel codepaths in your application code.

## Streaming endpoint

Responses are streamed over a POST endpoint registered automatically by the service provider at `ai/chatbot/stream` with route name `chatbot.stream`.

The middleware applied to this endpoint can be customized in the config file:

```php
// config/filament-chatbot.php
'route_middleware' => ['auth', 'web'],
```

The default `auth` middleware ensures only authenticated users can send messages. You may add additional middleware such as `throttle` or custom guards:

```php
'route_middleware' => ['auth', 'web', 'throttle:30,1'],
```

The endpoint validates conversation ownership. Users can only stream responses for their own conversations.

## Switching transport

Set the transport in `.env` (or `config/filament-chatbot.php`):

```dotenv
FILAMENT_CHATBOT_STREAM_TRANSPORT=http        # default
FILAMENT_CHATBOT_STREAM_TRANSPORT=websocket   # broadcast via Reverb
```

In WebSocket mode the controller dispatches a queued job that broadcasts each agent event on the private channel `chatbot.conversation.{conversationId}`, and the widget subscribes to that channel via Laravel Echo.

## HTTP (default)

Zero configuration. The widget POSTs to `ai/chatbot/stream` and reads the SSE response body in the browser.

## WebSocket via Laravel Reverb

1. Install Reverb in the consuming app:

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

   Filament panels do not load the app's `resources/js/app.js` by default. If your Echo bootstrap lives there, inject it into the panel via a render hook:

   ```php
   use Filament\View\PanelsRenderHook;
   use Illuminate\Support\Facades\Blade;

   $panel->renderHook(
       PanelsRenderHook::BODY_END,
       fn (): string => Blade::render("@vite(['resources/js/app.js'])"),
   );
   ```

4. Run the Reverb server and a queue worker alongside the usual web server:

   ```bash
   php artisan reverb:start
   php artisan queue:work
   ```

See the [Laravel Broadcasting documentation](https://laravel.com/docs/broadcasting) for the underlying mechanics.

## Environment variables

| Variable | Config key | Default | Description |
|---|---|---|---|
| `FILAMENT_CHATBOT_STREAM_TRANSPORT` | `stream.transport` | `http` | `http` for SSE, `websocket` for Reverb broadcasting |
| `FILAMENT_CHATBOT_BROADCASTING_CONNECTION` | `stream.websocket.connection` | `null` | Broadcasting connection name. `null` uses your default `BROADCAST_CONNECTION` |
| `FILAMENT_CHATBOT_BROADCASTING_QUEUE` | `stream.websocket.queue` | `null` | Queue used by the streaming job. `null` falls back to the connection default |

## Authorization

Streams are delivered on the private channel `chatbot.conversation.{conversationId}`. Authorization is registered automatically when the WebSocket transport is active and only allows the conversation owner to subscribe.

## Trade-offs

| Concern | HTTP (SSE) | WebSocket (Reverb) |
|---|---|---|
| First-token latency | Lowest, single request | Slight overhead (job dispatch + channel connect) |
| Infrastructure | None beyond the web server | Requires Reverb server + queue worker |
| Proxy / load balancer compatibility | Can be buffered or terminated by proxies | Persistent connection, generally proxy-friendly |
| Multi-tab synchronization | Each tab streams independently | Tabs subscribed to the same channel see the same stream |
| Background pushes (outside web request) | Not possible | Supported, any code can broadcast on the channel |

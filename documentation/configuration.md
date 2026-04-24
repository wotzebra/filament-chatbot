# Configuration reference

Publish the config file to set application-wide defaults:

```bash
php artisan vendor:publish --tag="filament-chatbot-config"
```

All values can be overridden per panel using the [Plugin API](plugin-api.md).

## Supported environment variables

| Variable | Config key | Default | Description |
|---|---|---|---|
| `FILAMENT_CHATBOT_ENABLED` | `enabled` | `null` | `null` = authenticated users only, `true` = always, `false` = never |
| `FILAMENT_CHATBOT_PROVIDER` | `provider` | `null` | Falls back to the `default` provider in `config/ai.php` |
| `FILAMENT_CHATBOT_MODEL` | `model` | `gpt-4o-mini` | The AI model to use |
| `FILAMENT_CHATBOT_TIMEOUT` | `timeout` | `60` | Request timeout in seconds |
| `FILAMENT_CHATBOT_STREAM_TRANSPORT` | `stream.transport` | `http` | `http` for SSE, `websocket` for Reverb broadcasting. See [Streaming](streaming.md) |

See the published `config/filament-chatbot.php` file for the full list of available options.

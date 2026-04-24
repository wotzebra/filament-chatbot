# Custom agent

The default agent (`Assistant`) reads its configuration from `config/filament-chatbot.php`. You can replace it with your own agent class to change behavior, add custom instructions, or use different tools per context.

## Extending the default Assistant

For most use cases, the simplest approach is to extend the built-in `Assistant` class and only override what you need:

```php
use Wotz\FilamentChatbot\Agents\Assistant;

class SupportAgent extends Assistant
{
    public function instructions(): string
    {
        return 'You are a support agent. Only answer questions about our product.';
    }
}
```

All other behavior (tools, provider, model, timeout, conversation memory) is inherited from `Assistant` and continues to read from the config file.

## Building a fully custom agent

If you need full control, you can build an agent from scratch using the Laravel AI package. Refer to the [Laravel AI SDK - Agents documentation](https://laravel.com/docs/12.x/ai-sdk#agents) for all available contracts, traits, and configuration options.

A custom agent compatible with this package should implement `Conversational` (via the `RemembersConversations` trait) to enable conversation memory. The package will continue the active conversation for each request; without that contract, each message is handled independently.

It should also use the `UsesToolsFromConfig` trait in its `tools()` method to ensure both globally and locally registered tools are included:

```php
use Wotz\FilamentChatbot\Agents\Concerns\UsesToolsFromConfig;

class SupportAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations, UsesToolsFromConfig;
    // ...
}
```

## Registering a custom agent

Set the agent globally in config:

```php
// config/filament-chatbot.php
'agent' => \App\Ai\Agents\SupportAgent::class,
```

Or per panel via the plugin:

```php
ChatbotPlugin::make()->agent(\App\Ai\Agents\SupportAgent::class)
```

See [Page Context](page-context.md#custom-agents-and-context) if your custom agent also needs to pick up page context.

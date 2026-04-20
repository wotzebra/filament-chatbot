# Filament Chatbot Documentation

## Introduction

`wotz/filament-chatbot` integrates a streaming AI chatbot directly into Filament panels.

It uses Laravel AI for model/provider integration, persists conversation data, and exposes a panel plugin API to customize behavior and UI.

Supported Filament versions: `4.x` and `5.x`.

## Installation

```bash
composer require wotz/filament-chatbot
```

Run the install command. This publishes the config file and migrations, and optionally runs the migrations:

```bash
php artisan filament-chatbot:install
```

## Register the Filament Plugin

In your panel provider:

```php
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(ChatbotPlugin::make());
```

## Plugin API

`ChatbotPlugin` supports fluent configuration per panel. Every option accepts a `Closure` in addition to a static value, which is evaluated at runtime. This allows you to base configuration on the authenticated user, tenant, or any other runtime context.

```php
ChatbotPlugin::make()
    ->enabled(fn () => auth()->user()->hasFeature('chatbot'))
    ->botName(fn () => 'Assistant for ' . auth()->user()->company_name)
    ->agent(fn () => auth()->user()->isPremium() ? PremiumAgent::class : BasicAgent::class)
```

Available methods:

| Method | Type | Description |
|---|---|---|
| `enabled()` | `bool\|Closure` | Show or hide the chatbot |
| `agent()` | `string\|Closure` | Agent class to use |
| `provider()` | `string\|array\|Closure\|null` | AI provider override |
| `model()` | `string\|Closure\|null` | Model override |
| `tools()` | `array` | Per-panel tool classes |
| `conversationKey()` | `string\|Closure` | Session key for conversation ID |
| `botName()` | `string\|Closure` | Name shown in widget header |
| `welcomeMessage()` | `string\|Closure` | Supports markdown |
| `buttonText()` | `string\|Closure` | Floating button label |
| `buttonIcon()` | `string\|Closure` | Heroicon name for the button |
| `chatWidth()` | `string\|Closure` | Any CSS value (px, rem, …) |
| `chatHeight()` | `string\|Closure` | Any CSS value (px, rem, …) |
| `logoUrl()` | `string\|Closure\|null` | Custom logo shown next to bot messages |
| `userModel()` | `string\|Closure` | Eloquent model for the conversation user (defaults to auth provider model) |
| `contextResolver()` | `string\|Closure\|null` | Resolver that turns page context into instruction text (see [Page Context](#page-context)) |

### The `enabled` option

The `enabled` option controls widget visibility and has three meaningful states:

- `true` - always render the widget (including for guests)
- `false` - never render the widget
- `null` - only render for authenticated users (default fallback when not configured)

## Configuration Reference

Publish the config file to set application-wide defaults:

```bash
php artisan vendor:publish --tag="filament-chatbot-config"
```

All values can be overridden per panel using the `ChatbotPlugin` fluent API.

### Supported environment variables

| Variable | Config key | Default | Description |
|---|---|---|---|
| `FILAMENT_CHATBOT_ENABLED` | `enabled` | `null` | `null` = authenticated users only, `true` = always, `false` = never |
| `FILAMENT_CHATBOT_PROVIDER` | `provider` | `null` | Falls back to the `default` provider in `config/ai.php` |
| `FILAMENT_CHATBOT_MODEL` | `model` | `gpt-4o-mini` | The AI model to use |
| `FILAMENT_CHATBOT_TIMEOUT` | `timeout` | `60` | Request timeout in seconds |

See the published `config/filament-chatbot.php` file for the full list of available options.

## Custom Agent

The default agent (`Assistant`) reads its configuration from `config/filament-chatbot.php`. You can replace it with your own agent class to change behavior, add custom instructions, or use different tools per context.

### Extending the default Assistant

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

### Building a fully custom agent

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

### Registering a custom agent

Set the agent globally in config:

```php
// config/filament-chatbot.php
'agent' => \App\Ai\Agents\SupportAgent::class,
```

Or per panel via the plugin:

```php
ChatbotPlugin::make()->agent(\App\Ai\Agents\SupportAgent::class)
```

## Tools

Tools allow the chatbot to perform actions during a conversation, such as looking up data, calling APIs, or executing logic. Each tool is a class that implements `Laravel\Ai\Contracts\Tool`.

Tools are instantiated through the service container, so constructor dependencies are automatically injected.

### Creating a tool class

Generate a tool with Laravel AI:

```bash
php artisan make:tool LookupOrderTool
```

Implement the required methods in the generated class:

- `description()` - explains to the AI what the tool does
- `schema()` - defines the input parameters the AI must provide
- `handle()` - executes the tool and returns the result

Official reference: [Laravel AI SDK - Tools](https://laravel.com/docs/12.x/ai-sdk#tools)

### Global tool registration

Global tools are available across all panels. Register them in `config/filament-chatbot.php` under a `tools` key:

```php
// config/filament-chatbot.php

'tools' => [
    \App\AI\Tools\LookupOrderTool::class,
    \App\AI\Tools\FetchProductTool::class,
],
```

These tools are always loaded, regardless of which panel the chatbot is used in.

### Local (per-panel) tool registration

Local tools are registered on a specific panel's plugin instance. They are merged with any globally configured tools:

```php
// App\Providers\Filament\AdminPanelProvider

use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

$panel->plugin(
    ChatbotPlugin::make()->tools([
        \App\AI\Tools\AdminReportTool::class,
    ])
);
```

Tools registered this way are only available in that specific panel. If the same tool class is registered both globally and locally, it will only be passed to the agent once.

## Conversations and Persistence

The package creates and uses two database tables:

- `agent_conversations` - one row per conversation, linked to a user
- `agent_conversation_messages` - all messages (user + assistant) belonging to a conversation

The messages table also stores internal entries such as tool calls and tool results. These are persisted for context reconstruction, but only messages with role `user` or `assistant` are shown in the widget UI.

### How the session tracks conversations

When a user sends their first message, the package creates a new conversation record and stores its UUID in the session. On subsequent page loads, the widget reads this UUID from the session and restores the conversation history from the database.

The session key used to store the conversation ID defaults to:

```
ai_chatbot_conversation_id_{panelId}
```

So for a panel with ID `admin`, the session key would be `ai_chatbot_conversation_id_admin`.

### Per-panel conversations (default)

Because each panel has a unique ID, every panel gets its own independent conversation by default. A user switching from the `admin` panel to the `customer` panel will start a fresh conversation there.

### Sharing a conversation across panels

If you want multiple panels to share the same conversation, configure the same `conversationKey` on each panel's plugin:

```php
// App\Providers\Filament\AdminPanelProvider
ChatbotPlugin::make()
    ->conversationKey('shared_ai_conversation')
```

```php
// App\Providers\Filament\CustomerPanelProvider
ChatbotPlugin::make()
    ->conversationKey('shared_ai_conversation')
```

Both panels will now read from and write to the same session key, meaning conversation history is preserved when navigating between them.

You may also use a `Closure` to compute the key dynamically at runtime:

```php
ChatbotPlugin::make()
    ->conversationKey(fn () => 'ai_conversation_' . auth()->user()->account_id)
```

## Page Context

The chatbot can be made aware of the page the user is currently looking at. When context is available, it is forwarded with every streamed message and appended to the agent's instructions, so answers can reference the record or screen the user is on without the user having to describe it.

### How it works

1. A Livewire component on the page uses the `InteractsWithChatbot` trait.
2. If the component implements `HasChatbotContext`, that explicit payload is used.
3. Otherwise, the trait falls back to the current Eloquent record from `getRecord()` or `$record`, if available.
4. On boot, the trait dispatches a `chatbot:context-updated` browser event containing the context array.
5. The chatbot widget listens for that event and stores the payload on its `pageContext` property.
6. When the user submits a question, the payload is POSTed alongside the message to the streaming endpoint.
7. The configured context resolver converts the raw array into an instruction string, which is attached to the request via the `chatbot.context` hidden context key.
8. The agent reads that value and appends it to its instructions for the current turn.

When the fallback record-based context is used, the payload comes from the model's `toArray()` output. That means already-loaded relations are included, but the package does not automatically eager load additional relations for you.

### Automatic context on Filament record pages

For most Filament `ViewRecord`, `EditRecord`, and similar pages, using the trait is enough:

```php
use Filament\Resources\Pages\ViewRecord;
use Wotz\FilamentChatbot\Livewire\Concerns\InteractsWithChatbot;

class ViewOrder extends ViewRecord
{
    use InteractsWithChatbot;
}
```

If the page has a current record, the chatbot receives that record as context automatically.

### Exposing context from a Livewire component

If you need a custom payload instead of the automatic record fallback, implement the `HasChatbotContext` contract and return an associative array describing the current page:

```php
use Livewire\Component;
use Wotz\FilamentChatbot\Contracts\HasChatbotContext;
use Wotz\FilamentChatbot\Livewire\Concerns\InteractsWithChatbot;

class OrderDetails extends Component implements HasChatbotContext
{
    use InteractsWithChatbot;

    public Order $order;

    public function chatbotContext(): array
    {
        return [
            'type' => 'order',
            'id' => $this->order->id,
            'number' => $this->order->number,
            'status' => $this->order->status,
        ];
    }
}
```

Returning an empty array disables context for that page: the resolver will short-circuit and the agent will receive its unmodified instructions.

### The default context resolver

The default `ContextResolver` wraps the raw context array as pretty-printed JSON under a `record` key, and adds page metadata (the current Filament panel id, and the `Referer` URL of the stream request) under a `page` key:

```
Current page context:
{
    "page": {
        "panel": "admin",
        "url": "https://example.test/admin/orders/42"
    },
    "record": {
        "type": "order",
        "id": 42,
        "number": "O-42",
        "status": "paid"
    }
}
```

### Using a custom resolver

Provide a closure or class-string to `contextResolver()` on the plugin to control the final instruction text. The resolver receives the raw context array and the current request, and must return a string (or `null` to skip context for this turn):

```php
use Illuminate\Http\Request;

ChatbotPlugin::make()
    ->contextResolver(function (array $context, Request $request): ?string {
        if ($context === []) {
            return null;
        }

        return 'The user is currently viewing: ' . json_encode($context);
    });
```

A class-string is resolved through the service container, so constructor dependencies are injected:

```php
ChatbotPlugin::make()->contextResolver(\App\AI\MyContextResolver::class);
```

### Custom agents and context

The built-in `Assistant` agent already merges the resolved context into its instructions. For a custom agent to pick up the same behavior, use the `ComposesInstructionsWithContext` trait and pass your base instructions through `composeInstructions()`:

```php
use Wotz\FilamentChatbot\Agents\Concerns\ComposesInstructionsWithContext;

class SupportAgent implements Agent, Conversational, HasTools
{
    use ComposesInstructionsWithContext;
    use Promptable;
    use RemembersConversations;
    use UsesToolsFromConfig;

    public function instructions(): string
    {
        return $this->composeInstructions('You are a support agent. Only answer questions about our product.');
    }
}
```

If you prefer to read the resolved context yourself, it is available via `Illuminate\Support\Facades\Context::getHidden('chatbot.context')` during the stream request.

## Conversation Resource

The package includes a Filament resource that lists all conversations for the authenticated user. It is registered automatically when the plugin is active.

The resource is available at `/conversations` in your panel and shows:

- Conversation title
- User name
- Message count
- Created / updated timestamps

Clicking a conversation opens a fullscreen view with the complete message history. From the chat widget, users can also click the expand button to open the current conversation in this fullscreen view.

Conversations are scoped to the authenticated user. Each user can only see and access their own conversations.

## UI Customization

### Welcome message

The welcome message supports markdown, so you can use bold text, links, lists, and other formatting:

```php
ChatbotPlugin::make()
    ->welcomeMessage('Hello! I can help you with:\n- **Orders**\n- **Returns**\n- **Account questions**')
```

### Logo

By default, bot messages show a sparkle icon. You can replace this with a custom image:

```php
ChatbotPlugin::make()
    ->logoUrl(asset('images/bot-avatar.png'))
```

### Window dimensions and position

The chat window defaults to 400×600px and appears in the bottom-right corner. Users can toggle the position to the left side. This preference is persisted in the session.

```php
ChatbotPlugin::make()
    ->chatWidth('500px')
    ->chatHeight('700px')
```

## Streaming Endpoint

Responses are streamed over HTTP via a POST endpoint. The streaming route is registered automatically by the service provider at `ai/chatbot/stream` with route name `chatbot.stream`.

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

## Testing

```bash
vendor/bin/pest
```

## Related Docs

- [README](../README.md)
- [CHANGELOG](../CHANGELOG.md)
- [UPGRADING](../UPGRADING.md)
- [CONTRIBUTING](../CONTRIBUTING.md)

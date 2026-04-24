# Page context

The chatbot can be made aware of the page the user is currently looking at. When context is available, it is forwarded with every streamed message and appended to the agent's instructions, so answers can reference the record or screen the user is on without the user having to describe it.

## How it works

1. A Livewire component on the page uses the `InteractsWithChatbot` trait.
2. If the component implements `HasChatbotContext`, that explicit payload is used.
3. Otherwise, the trait falls back to the current Eloquent record from `getRecord()` or `$record`, if available.
4. On boot, the trait dispatches a `chatbot:context-updated` browser event containing the context array.
5. The chatbot widget listens for that event and stores the payload on its `pageContext` property.
6. When the user submits a question, the payload is POSTed alongside the message to the streaming endpoint.
7. The configured context resolver converts the raw array into an instruction string, which is attached to the request via the `chatbot.context` hidden context key.
8. The agent reads that value and appends it to its instructions for the current turn.

When the fallback record-based context is used, the payload comes from the model's `toArray()` output. That means already-loaded relations are included, but the package does not automatically eager load additional relations for you.

## Automatic context on Filament record pages

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

## Exposing context from a Livewire component

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

## The default context resolver

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

## Using a custom resolver

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

## Custom agents and context

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

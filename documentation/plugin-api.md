# Plugin API

`ChatbotPlugin` supports fluent configuration per panel. Every option accepts a `Closure` in addition to a static value, which is evaluated at runtime. This allows you to base configuration on the authenticated user, tenant, or any other runtime context.

```php
ChatbotPlugin::make()
    ->enabled(fn () => auth()->user()->hasFeature('chatbot'))
    ->botName(fn () => 'Assistant for ' . auth()->user()->company_name)
    ->agent(fn () => auth()->user()->isPremium() ? PremiumAgent::class : BasicAgent::class)
```

## Available methods

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
| `contextResolver()` | `string\|Closure\|null` | Resolver that turns page context into instruction text (see [Page Context](page-context.md)) |

## The `enabled` option

The `enabled` option controls widget visibility and has three meaningful states:

- `true` - always render the widget (including for guests)
- `false` - never render the widget
- `null` - only render for authenticated users (default fallback when not configured)

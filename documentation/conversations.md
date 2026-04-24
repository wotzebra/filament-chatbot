# Conversations and persistence

The package creates and uses two database tables:

- `agent_conversations` - one row per conversation, linked to a user
- `agent_conversation_messages` - all messages (user + assistant) belonging to a conversation

The messages table also stores internal entries such as tool calls and tool results. These are persisted for context reconstruction, but only messages with role `user` or `assistant` are shown in the widget UI.

## How the session tracks conversations

When a user sends their first message, the package creates a new conversation record and stores its UUID in the session. On subsequent page loads, the widget reads this UUID from the session and restores the conversation history from the database.

The session key used to store the conversation ID defaults to:

```
ai_chatbot_conversation_id_{panelId}
```

So for a panel with ID `admin`, the session key would be `ai_chatbot_conversation_id_admin`.

## Per-panel conversations (default)

Because each panel has a unique ID, every panel gets its own independent conversation by default. A user switching from the `admin` panel to the `customer` panel will start a fresh conversation there.

## Sharing a conversation across panels

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

## Conversation resource

The package includes a Filament resource that lists all conversations for the authenticated user. It is registered automatically when the plugin is active.

The resource is available at `/conversations` in your panel and shows:

- Conversation title
- User name
- Message count
- Created / updated timestamps

Clicking a conversation opens a fullscreen view with the complete message history. From the chat widget, users can also click the expand button to open the current conversation in this fullscreen view.

Conversations are scoped to the authenticated user. Each user can only see and access their own conversations.

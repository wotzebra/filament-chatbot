<?php

use Wotz\FilamentChatbot\Agents\Assistant;

return [
    /*
    |--------------------------------------------------------------------------
    | Chatbot Enabled
    |--------------------------------------------------------------------------
    |
    | This value determines whether the chatbot widget is rendered within
    | Filament panels. When set to null, the widget will only be shown to
    | authenticated users. You may override this per panel via the plugin.
    |
    */

    'enabled' => env('FILAMENT_CHATBOT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Agent
    |--------------------------------------------------------------------------
    |
    | This is the agent class used by the chatbot when no agent is explicitly
    | configured on the panel's ChatbotPlugin. Each agent class may define
    | its own provider, model, and instructions, or fall back to the values
    | configured below.
    |
    */

    'agent' => Assistant::class,

    /*
    |--------------------------------------------------------------------------
    | AI Provider & Model
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default AI provider and model used by agent
    | classes that read their configuration from this file. You may override
    | these per panel by calling provider() and model() on the ChatbotPlugin.
    |
    */

    'provider' => env('FILAMENT_CHATBOT_PROVIDER', 'openai'),
    'model' => env('FILAMENT_CHATBOT_MODEL', 'gpt-4o-mini'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | This value controls how long the chatbot will wait for a response from
    | the AI provider before timing out, expressed in seconds. Increase this
    | if you are using slower models or complex tool-calling agents.
    |
    */

    'timeout' => (int) env('FILAMENT_CHATBOT_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | System Instructions
    |--------------------------------------------------------------------------
    |
    | The default system prompt sent to the AI model on every request. This
    | defines the assistant's behavior, persona, and scope. Agent classes may
    | use this value or define their own instructions independently.
    |
    */

    'instructions' => '',

    /*
    |--------------------------------------------------------------------------
    | Bot Name
    |--------------------------------------------------------------------------
    |
    | The name displayed in the chatbot widget header. This value will be
    | shown to users as the identity of the assistant they are talking to.
    | You may override this per panel using the ChatbotPlugin fluent API.
    |
    */

    'bot_name' => 'AI Assistent',

    /*
    |--------------------------------------------------------------------------
    | Welcome Message
    |--------------------------------------------------------------------------
    |
    | This message is shown to the user when they open the chatbot for the
    | first time or start a new conversation. You are free to customize it
    | to suit the tone and purpose of your application's assistant.
    |
    */

    'welcome_message' => 'Hello! How can i help you?',

    /*
    |--------------------------------------------------------------------------
    | Toggle Button
    |--------------------------------------------------------------------------
    |
    | Here you may configure the floating button that opens the chatbot. You
    | may set its label text and the Heroicon used as the button icon. Both
    | values can be overridden per panel via the ChatbotPlugin fluent API.
    |
    */

    'button_text' => 'Open chatbot',
    'button_icon' => 'heroicon-o-chat-bubble-left-right',

    /*
    |--------------------------------------------------------------------------
    | Chat Window Dimensions
    |--------------------------------------------------------------------------
    |
    | These values control the width and height of the floating chat window.
    | Any valid CSS size value is accepted (px, rem, etc.). You may override
    | these per panel using the ChatbotPlugin chatWidth() and chatHeight().
    |
    */

    'chat_width' => '400px',
    'chat_height' => '600px',

    /*
    |--------------------------------------------------------------------------
    | Stream Route
    |--------------------------------------------------------------------------
    |
    | The chatbot streams AI responses over HTTP using a dedicated route. Here
    | you may configure the route name, path, and middleware that should be
    | applied to protect and identify the streaming endpoint.
    |
    */

    'route' => [
        'name' => 'chatbot.stream',
        'path' => 'ai/chatbot/stream/{token}',
        'middleware' => ['auth', 'web'],
    ],
];

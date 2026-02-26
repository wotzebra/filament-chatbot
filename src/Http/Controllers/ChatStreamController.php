<?php

namespace Wotz\FilamentChatbot\Http\Controllers;

use Laravel\Ai\Contracts\Conversational;
use Wotz\FilamentChatbot\Http\Requests\ChatStreamRequest;
use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;

class ChatStreamController
{
    public function __invoke(string $token, ChatStreamRequest $request): mixed
    {
        set_time_limit(0);

        $chatbot = filament('chatbot');

        app(ToolRegistry::class)->withTools($chatbot->getTools());

        $agent = new ($chatbot->getAgentClass());

        return ($agent instanceof Conversational
            ? $agent->continue($request->conversationId(), as: auth()->user())
            : $agent
        )->stream($request->message(), provider: $chatbot->getProvider(), model: $chatbot->getModel());
    }
}

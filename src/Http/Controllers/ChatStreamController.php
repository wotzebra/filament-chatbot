<?php

namespace Wotz\FilamentChatbot\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Wotz\FilamentChatbot\Facades\Chat;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Http\Requests\ChatStreamRequest;

class ChatStreamController
{
    public function __invoke(ChatStreamRequest $request): StreamedResponse
    {
        set_time_limit(300);

        abort_unless(Chat::ownedBy($request->conversationId(), auth()->user()), 403);

        /** @var ChatbotPlugin $chatbot */
        $chatbot = filament('chatbot');

        $resolver = $chatbot->getContextResolver();

        return Chat::for($request->conversationId())
            ->as(auth()->user())
            ->withAgent($chatbot->getAgentClass())
            ->withProvider($chatbot->getProvider())
            ->withModel($chatbot->getModel())
            ->withTools($chatbot->getTools())
            ->withContext($resolver($request->context(), $request))
            ->stream($request->message());
    }
}

<?php

namespace Wotz\FilamentChatbot\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Conversational;
use Wotz\FilamentChatbot\Http\Requests\ChatStreamRequest;
use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;

class ChatStreamController
{
    public function __invoke(ChatStreamRequest $request): mixed
    {
        set_time_limit(300);

        $conversationId = $request->conversationId();

        $exists = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', auth()->id())
            ->exists();

        abort_unless($exists, 403);

        $chatbot = filament('chatbot');

        app(ToolRegistry::class)->withTools($chatbot->getTools());

        $agent = new ($chatbot->getAgentClass());

        return ($agent instanceof Conversational
            ? $agent->continue($conversationId, as: auth()->user())
            : $agent
        )->stream($request->message(), provider: $chatbot->getProvider(), model: $chatbot->getModel());
    }
}

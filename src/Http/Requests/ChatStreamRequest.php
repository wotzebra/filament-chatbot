<?php

namespace Wotz\FilamentChatbot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatStreamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string'],
            'conversation_id' => ['required', 'string'],
        ];
    }

    public function message(): string
    {
        return trim($this->string('message'));
    }

    public function conversationId(): string
    {
        return $this->string('conversation_id');
    }
}

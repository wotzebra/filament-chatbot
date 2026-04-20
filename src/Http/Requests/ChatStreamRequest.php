<?php

namespace Wotz\FilamentChatbot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatStreamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
            'conversation_id' => ['required', 'string'],
            'context' => ['nullable', 'array'],
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

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return (array) $this->input('context', []);
    }
}

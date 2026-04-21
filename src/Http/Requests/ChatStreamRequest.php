<?php

namespace Wotz\FilamentChatbot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Wotz\FilamentChatbot\Streaming\TransportManager;

class ChatStreamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
            'conversation_id' => ['required', 'string'],
            'context' => ['nullable', 'array'],
            'transport' => ['nullable', 'string', Rule::in(app(TransportManager::class)->supported())],
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

    public function transport(): ?string
    {
        $transport = trim((string) $this->input('transport', ''));

        return $transport !== '' ? $transport : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return (array) $this->input('context', []);
    }
}

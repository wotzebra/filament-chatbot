<div
    id="chatgpt-agent-window"
    x-data="{}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-chatbot', package: 'wotz/filament-chatbot'))]">
    {{-- Toggle button: only visible when panel is hidden --}}
    <div @class([
        'fixed bottom-4 right-4 z-50 cursor-pointer',
        'hidden' => ! $panelHidden,
    ])>
        <x-filament::button wire:click="togglePanel" id="btn-chat" :icon="$buttonIcon" color="primary">
            {{ $buttonText }}
        </x-filament::button>
    </div>

    {{-- Chat window --}}
    <div
        @class([
            'fixed bottom-0 z-50 flex flex-col overflow-hidden border border-gray-200/90 bg-white/95 shadow-2xl backdrop-blur-sm',
            'left-0 rounded-t-xl' => $winPosition === 'left',
            'right-0 rounded-tl-xl' => $winPosition !== 'left',
            'hidden' => $panelHidden,
        ])
        style="{{ $winWidth }} {{ $winHeight }}"
        id="chat-window">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-gray-200/90 bg-white/82 px-4 py-3 backdrop-blur-lg">
            <div class="flex min-w-0 items-center gap-3">
                @if ($logoUrl && $logoUrl !== '')
                    <img src="{{ $logoUrl }}" alt="{{ $name }}" class="h-7 w-7 shrink-0 rounded-full object-cover">
                @else
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_15%,white)] text-[var(--color-primary-700,#075748)]">
                        <x-heroicon-m-sparkles class="h-3.5 w-3.5" />
                    </div>
                @endif

                <div class="flex min-w-0 flex-col">
                    <span class="text-[0.92rem] leading-none font-bold text-gray-900">{{ $name }}</span>
                    <span class="hidden truncate text-xs leading-5 text-gray-500 sm:block">{{ __('Ask a quick question or continue the conversation.') }}</span>
                </div>
            </div>

            <div class="flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50/85 p-0.5">
                <x-filament::icon-button
                    color="gray"
                    icon="heroicon-o-document"
                    wire:click="clearChat()"
                    :label="__('filament-chatbot::chatbot.new_session')"
                    :tooltip="__('filament-chatbot::chatbot.new_session')"
                />
                @if ($conversationId)
                    <x-filament::icon-button
                        color="gray"
                        icon="heroicon-o-arrows-pointing-out"
                        :href="\Wotz\FilamentChatbot\Filament\Resources\ConversationResource::getUrl('view', ['record' => $conversationId])"
                        tag="a"
                        x-on:click="$wire.panelHidden = true"
                        :label="__('filament-chatbot::chatbot.open_fullscreen')"
                        :tooltip="__('filament-chatbot::chatbot.open_fullscreen')"
                    />
                @endif
                @if ($showPositionBtn)
                    <x-filament::icon-button
                        color="gray"
                        icon="heroicon-s-arrows-right-left"
                        wire:click="changeWinPosition()"
                        :label="__('filament-chatbot::chatbot.move_window')"
                        :tooltip="__('filament-chatbot::chatbot.move_window')"
                    />
                @endif
                <x-filament::icon-button
                    color="gray"
                    icon="heroicon-o-x-mark"
                    wire:click="togglePanel"
                    :label="__('filament-chatbot::chatbot.close_chat')"
                    :tooltip="__('filament-chatbot::chatbot.close_chat')"
                />
            </div>
        </div>

        {{-- Messages --}}
        <div id="messages" wire:scroll wire:key="chatbot-messages" class="chatbot-messages flex flex-1 flex-col gap-4 overflow-y-auto bg-[radial-gradient(circle_at_top_right,rgba(15,215,175,0.08),transparent_30%),linear-gradient(180deg,rgb(249,250,251)_0%,rgb(243,244,246)_100%)] px-4 pt-4 pb-3.5">

            @if ($messages->isEmpty() && ! $isStreaming)
                <div class="flex flex-col items-start gap-3.5 rounded-3xl border border-gray-200 bg-white/90 p-4 shadow-[0_20px_45px_-30px_rgb(15_23_42/0.35)]">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_15%,white)] text-[var(--color-primary-700,#075748)]">
                        <x-heroicon-m-sparkles class="h-4 w-4" />
                    </div>

                    <div class="flex flex-col gap-1.5 text-gray-800">
                        <div class="text-[0.95rem] font-bold">{{ $name }}</div>
                        <div class="text-sm leading-6">
                            {!! Str::markdown($welcomeMessage) !!}
                        </div>
                    </div>
                </div>
            @endif

            @foreach ($messages as $message)
                <div wire:key="chatbot-message-{{ $loop->index }}">
                    @if ($message->role === \Laravel\Ai\Messages\MessageRole::User->value)
                        <div class="flex items-start justify-end gap-2.5">
                            <div class="max-w-[75%]">
                                <div class="chatbot-bubble chatbot-bubble-user prose prose-sm prose-invert max-w-none rounded-[1.1rem_1.1rem_0.35rem_1.1rem] bg-[linear-gradient(135deg,var(--color-primary-600,#0A7B65),var(--color-primary-500,#0BA284))] px-4 py-3 text-sm leading-6 text-white shadow-[0_18px_40px_-32px_rgb(15_23_42/0.45)]">
                                    {!! Str::markdown($message->content) !!}
                                </div>
                            </div>
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-500">
                                <x-heroicon-m-user class="h-3.5 w-3.5" />
                            </div>
                        </div>
                    @else
                        <div class="flex items-start gap-2.5">
                            @if ($logoUrl && $logoUrl !== '')
                                <img src="{{ $logoUrl }}" alt="{{ $name }}" class="mt-0.5 h-7 w-7 shrink-0 rounded-full object-cover">
                            @else
                                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_15%,white)] text-[var(--color-primary-700,#075748)]">
                                    <x-heroicon-m-sparkles class="h-3.5 w-3.5" />
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="chatbot-bubble chatbot-bubble-assistant prose prose-sm max-w-none rounded-[1.1rem_1.1rem_1.1rem_0.35rem] border border-gray-200 bg-white/95 px-4 py-3 text-sm leading-6 text-gray-900 shadow-[0_18px_40px_-32px_rgb(15_23_42/0.45)]">
                                    {!! Str::markdown($message->content) !!}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($isStreaming)
                <div x-data="{
                    streamingText: '',
                    streamKey: @js(($conversationId ?? '') . ':' . md5($streamMessage)),
                    abortController: null,
                    finalized: false,
                    get sanitizedHtml() {
                        if (! this.streamingText) {
                            return '<span style=\'color: rgb(156 163 175);\'>{{ __('filament-chatbot::chatbot.thinking') }}</span>';
                        }
                        return this.streamingText
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/\n/g, '<br>');
                    },
                    cleanup() {
                        if (this.abortController) {
                            this.abortController.abort();
                            this.abortController = null;
                        }

                        if (window.__filamentChatbotStreams?.[this.streamKey]) {
                            delete window.__filamentChatbotStreams[this.streamKey];
                        }
                    },
                    finalize() {
                        if (this.finalized) {
                            return;
                        }

                        this.finalized = true;
                        this.cleanup();
                        $wire.onStreamComplete(this.streamingText);
                    },
                    async init() {
                        window.__filamentChatbotStreams ??= {};

                        if (window.__filamentChatbotStreams[this.streamKey]) {
                            return;
                        }

                        window.__filamentChatbotStreams[this.streamKey] = true;
                        this.abortController = new AbortController();

                        try {
                            const response = await fetch(@js($streamRouteBase), {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'text/event-stream',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                                },
                                body: JSON.stringify({
                                    message: @js($streamMessage),
                                    conversation_id: @js($conversationId),
                                }),
                                signal: this.abortController.signal,
                            });

                            if (!response.ok || !response.body) {
                                this.finalize();
                                return;
                            }

                            const reader = response.body.getReader();
                            const decoder = new TextDecoder();
                            let buffer = '';

                            while (true) {
                                const { done, value } = await reader.read();
                                if (done) break;

                                buffer += decoder.decode(value, { stream: true });
                                const lines = buffer.split('\n');
                                buffer = lines.pop();

                                for (const line of lines) {
                                    if (!line.startsWith('data: ')) continue;
                                    const data = line.slice(6);

                                    if (data === '[DONE]') {
                                        this.finalize();
                                        return;
                                    }

                                    try {
                                        const event = JSON.parse(data);
                                        if (event.type === 'text_delta') {
                                            this.streamingText += event.delta;
                                        }
                                        if (event.type !== 'text_delta' && typeof event.message === 'string' && event.message !== '') {
                                            this.streamingText = event.message;
                                        }
                                    } catch (err) {
                                        console.warn('Stream parse error:', err);
                                    }
                                }
                            }

                            this.finalize();
                        } catch (err) {
                            console.warn('Stream fetch error:', err);
                            this.finalize();
                        }
                    }
                }" x-init="init()" class="flex items-start gap-2.5">
                    @if ($logoUrl && $logoUrl !== '')
                        <img src="{{ $logoUrl }}" alt="{{ $name }}" class="mt-0.5 h-7 w-7 shrink-0 rounded-full object-cover">
                    @else
                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_15%,white)] text-[var(--color-primary-700,#075748)]">
                            <x-heroicon-m-sparkles class="h-3.5 w-3.5" />
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="chatbot-bubble chatbot-bubble-assistant prose prose-sm max-w-none rounded-[1.1rem_1.1rem_1.1rem_0.35rem] border border-gray-200 bg-white/95 px-4 py-3 text-sm leading-6 text-gray-900 shadow-[0_18px_40px_-32px_rgb(15_23_42/0.45)]" x-html="sanitizedHtml"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Input --}}
        <div class="border-t border-gray-200/90 bg-white/88 p-3.5 backdrop-blur-lg">
            <div class="flex items-end gap-2.5 rounded-[1.15rem] bg-white px-1 py-1 shadow-[0_18px_35px_-30px_rgb(15_23_42/0.35)]">
                <div class="min-w-0 flex-1">
                    <div class="rounded-[1rem] bg-transparent">
                        <textarea
                            x-data="{
                                resize() {
                                    $el.style.height = '40px';
                                    $el.style.height = `${$el.scrollHeight}px`;
                                },
                                collapse() {
                                    $el.style.height = '40px';
                                }
                            }"
                            x-init="resize()"
                            @input="resize()"
                            @blur="collapse()"
                            @focus="resize()"
                            :disabled="$wire.isStreaming"
                            @keydown.enter="!$event.shiftKey && !$wire.isStreaming && ($event.preventDefault(), $wire.askQuestion())"
                            wire:model.live="question"
                            placeholder="{{ __('filament-chatbot::chatbot.placeholder') }}"
                            autofocus
                            class="block max-h-[150px] w-full resize-none border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-hidden placeholder:text-gray-400 disabled:cursor-progress"
                            style="height: 40px;"
                            id="chat-input">
                        </textarea>
                    </div>
                </div>

                <div class="flex items-end">
                    <x-filament::icon-button
                        color="primary"
                        icon="heroicon-o-paper-airplane"
                        wire:click="askQuestion"
                        :disabled="empty(trim($question)) || $isStreaming"
                        :tooltip="__('filament-chatbot::chatbot.send')"
                    />
                </div>
            </div>
        </div>
    </div>

</div>

@script
    <script>
        const el = document.getElementById('messages');

        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            if (component.name === 'chatbot-widget') {
                succeed(({ snapshot, effect }) => {
                    setTimeout(() => {
                        if (el) { el.scrollTop = el.scrollHeight; }
                    }, 100);
                });
            }
        });

        document.addEventListener('livewire:initialized', function () {
            const textarea = document.querySelector('#chat-input');
            if (el) { el.scrollTop = el.scrollHeight; }
            if (textarea) { textarea.focus(); }
        });
    </script>
@endscript

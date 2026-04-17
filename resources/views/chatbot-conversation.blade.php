<div
    x-data="{}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-chatbot', package: 'wotz/filament-chatbot'))]"
    class="flex h-[calc(100vh-12rem)] flex-col overflow-hidden rounded-xl border border-gray-200/90 bg-white/95 shadow-lg">

    {{-- Messages --}}
    <div id="conversation-messages" class="chatbot-messages flex flex-1 flex-col gap-4 overflow-y-auto bg-[radial-gradient(circle_at_top_right,rgba(15,215,175,0.08),transparent_30%),linear-gradient(180deg,rgb(249,250,251)_0%,rgb(243,244,246)_100%)] px-6 py-4">

        @foreach ($messages as $message)
            <div wire:key="conv-message-{{ $loop->index }}">
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
                            <img src="{{ $logoUrl }}" alt="AI" class="mt-0.5 h-7 w-7 shrink-0 rounded-full object-cover">
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
                streamKey: @js($conversationId . ':' . md5($streamMessage)),
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
                    <img src="{{ $logoUrl }}" alt="AI" class="mt-0.5 h-7 w-7 shrink-0 rounded-full object-cover">
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
    <div class="border-t border-gray-200/90 bg-white/88 p-4 backdrop-blur-lg">
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
                        id="conversation-input">
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

@script
    <script>
        const el = document.getElementById('conversation-messages');

        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            if (component.name === 'chatbot-conversation') {
                succeed(({ snapshot, effect }) => {
                    setTimeout(() => {
                        if (el) { el.scrollTop = el.scrollHeight; }
                    }, 100);
                });
            }
        });

        document.addEventListener('livewire:initialized', function () {
            const textarea = document.querySelector('#conversation-input');
            if (el) { el.scrollTop = el.scrollHeight; }
            if (textarea) { textarea.focus(); }
        });
    </script>
@endscript
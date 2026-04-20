<div id="chatbot-messages" wire:scroll wire:key="chatbot-messages" class="chatbot-messages flex flex-1 flex-col gap-4 overflow-y-auto bg-[radial-gradient(circle_at_top_right,rgba(15,215,175,0.08),transparent_30%),linear-gradient(180deg,rgb(249,250,251)_0%,rgb(243,244,246)_100%)] {{ $standalone ? 'px-6 py-4' : 'px-4 pt-4 pb-3.5' }}">

    @if (! $standalone && $messages->isEmpty() && ! $isStreaming)
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
            @if ($message['role'] === \Laravel\Ai\Messages\MessageRole::User->value)
                <div class="flex items-start justify-end gap-2.5">
                    <div class="max-w-[75%]">
                        <div class="chatbot-bubble chatbot-bubble-user prose prose-sm prose-invert max-w-none rounded-[1.1rem_1.1rem_0.35rem_1.1rem] bg-[linear-gradient(135deg,var(--color-primary-600,#0A7B65),var(--color-primary-500,#0BA284))] px-4 py-3 text-sm leading-6 text-white shadow-[0_18px_40px_-32px_rgb(15_23_42/0.45)]">
                            {!! Str::markdown($message['content']) !!}
                        </div>
                    </div>
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-500">
                        <x-heroicon-m-user class="h-3.5 w-3.5" />
                    </div>
                </div>
            @else
                <div class="flex items-start gap-2.5">
                    @if ($logoUrl && $logoUrl !== '')
                        <img src="{{ $logoUrl }}" alt="{{ $name ?: 'AI' }}" class="mt-0.5 h-7 w-7 shrink-0 rounded-full object-cover">
                    @else
                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_15%,white)] text-[var(--color-primary-700,#075748)]">
                            <x-heroicon-m-sparkles class="h-3.5 w-3.5" />
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="chatbot-bubble chatbot-bubble-assistant prose prose-sm max-w-none rounded-[1.1rem_1.1rem_1.1rem_0.35rem] border border-gray-200 bg-white/95 px-4 py-3 text-sm leading-6 text-gray-900 shadow-[0_18px_40px_-32px_rgb(15_23_42/0.45)]">
                            {!! Str::markdown($message['content']) !!}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endforeach

    @if ($isStreaming)
        @php($streamTransport = $this->streamTransport())
        <div x-data="{
            streamingText: '',
            streamKey: @js(($conversationId ?? '') . ':' . md5($streamMessage)),
            transport: @js($streamTransport),
            abortController: null,
            echoChannel: null,
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
            appendDelta(event) {
                if (! event || typeof event !== 'object') return;
                if (event.type === 'text_delta' && typeof event.delta === 'string') {
                    this.streamingText += event.delta;
                    return;
                }
                if (event.type !== 'text_delta' && typeof event.message === 'string' && event.message !== '') {
                    this.streamingText = event.message;
                }
            },
            cleanup() {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }

                if (this.echoChannel && window.Echo) {
                    try {
                        window.Echo.leave(this.echoChannel);
                    } catch (err) {
                        console.warn('Echo leave error:', err);
                    }
                    this.echoChannel = null;
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
            async runHttp() {
                this.abortController = new AbortController();

                try {
                    const response = await fetch(this.transport.config.endpoint ?? @js($streamRouteBase), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'text/event-stream',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                        },
                        body: JSON.stringify({
                            message: @js($streamMessage),
                            conversation_id: @js($conversationId),
                            context: @js($pageContext),
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
                                this.appendDelta(JSON.parse(data));
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
            },
            async runWebsocket() {
                if (! window.Echo) {
                    console.warn('filament-chatbot: window.Echo is not available, falling back to HTTP SSE');
                    await this.runHttp();
                    return;
                }

                const channelName = this.transport.config.channel;
                const eventName = this.transport.config.event ?? 'chatbot.stream';

                if (! channelName) {
                    console.warn('filament-chatbot: websocket transport missing channel name');
                    this.finalize();
                    return;
                }

                this.echoChannel = channelName;

                window.Echo.private(channelName).listen('.' + eventName, (payload) => {
                    const event = payload && payload.payload ? payload.payload : payload;

                    if (event && event.type === 'done') {
                        this.finalize();
                        return;
                    }

                    this.appendDelta(event);
                });

                try {
                    const response = await fetch(this.transport.config.endpoint ?? @js($streamRouteBase), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                        },
                        body: JSON.stringify({
                            message: @js($streamMessage),
                            conversation_id: @js($conversationId),
                            context: @js($pageContext),
                        }),
                    });

                    if (!response.ok) {
                        console.warn('filament-chatbot: websocket trigger failed', response.status);
                        this.finalize();
                    }
                } catch (err) {
                    console.warn('Stream trigger error:', err);
                    this.finalize();
                }
            },
            async init() {
                window.__filamentChatbotStreams ??= {};

                if (window.__filamentChatbotStreams[this.streamKey]) {
                    return;
                }

                window.__filamentChatbotStreams[this.streamKey] = true;

                if (this.transport && this.transport.name === 'websocket') {
                    await this.runWebsocket();
                    return;
                }

                await this.runHttp();
            }
        }" x-init="init()" class="flex items-start gap-2.5">
            @if ($logoUrl && $logoUrl !== '')
                <img src="{{ $logoUrl }}" alt="{{ $name ?: 'AI' }}" class="mt-0.5 h-7 w-7 shrink-0 rounded-full object-cover">
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
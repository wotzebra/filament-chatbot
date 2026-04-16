<div
    x-data="{}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-chatbot', package: 'wotz/filament-chatbot'))]"
    class="flex flex-col gap-4 rounded-xl bg-[radial-gradient(circle_at_top_right,rgba(15,215,175,0.08),transparent_30%),linear-gradient(180deg,rgb(249,250,251)_0%,rgb(243,244,246)_100%)] px-4 py-4"
>
    @foreach ($getRecord()->messages()->orderBy('created_at')->get() as $message)
        @if (blank($message->content))
            @continue
        @endif

        @if ($message->role === 'user')
            <div class="flex items-start justify-end gap-2.5">
                <div class="max-w-[75%]">
                    <div class="chatbot-bubble chatbot-bubble-user rounded-[1.1rem_1.1rem_0.35rem_1.1rem] bg-[linear-gradient(135deg,var(--color-primary-600,#0A7B65),var(--color-primary-500,#0BA284))] px-4 py-3 text-sm leading-6 text-white shadow-[0_18px_40px_-32px_rgb(15_23_42/0.45)]">
                        {!! str($message->content)->markdown() !!}
                    </div>
                    <div class="mt-1 text-right text-xs text-gray-400">
                        {{ $message->created_at->format('d M Y H:i:s') }}
                    </div>
                </div>
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-500">
                    <x-heroicon-m-user class="h-3.5 w-3.5" />
                </div>
            </div>
        @else
            <div class="flex items-start gap-2.5">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,var(--color-primary-500,#0BA284)_15%,white)] text-[var(--color-primary-700,#075748)]">
                    <x-heroicon-m-sparkles class="h-3.5 w-3.5" />
                </div>
                <div class="min-w-0 max-w-[85%]">
                    <div class="chatbot-bubble chatbot-bubble-assistant rounded-[1.1rem_1.1rem_1.1rem_0.35rem] border border-gray-200 bg-white/95 px-4 py-3 text-sm leading-6 text-gray-900 shadow-[0_18px_40px_-32px_rgb(15_23_42/0.45)]">
                        {!! str($message->content)->markdown() !!}
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        {{ $message->created_at->format('d M Y H:i:s') }}
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
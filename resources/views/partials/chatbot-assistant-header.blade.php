<div class="flex items-center gap-2">
    @if ($logoUrl && $logoUrl !== '')
        <img src="{{ $logoUrl }}" alt="{{ $name ?: 'AI' }}" class="h-7 w-7 shrink-0 rounded-full object-cover">
    @else
        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[var(--primary-tint-15)] text-[rgb(var(--primary-700))]">
            <x-heroicon-m-sparkles class="h-3.5 w-3.5" />
        </div>
    @endif
    <span class="text-xs font-semibold text-gray-700">{{ $name ?: 'AI' }}</span>
</div>

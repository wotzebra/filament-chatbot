<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

use Filament\Facades\Filament;
use Illuminate\Http\Request;

class ContextResolver
{
    /**
     * @param  array<string, mixed>  $rawContext
     */
    public function __invoke(array $rawContext, Request $request): ?string
    {
        if ($rawContext === []) {
            return null;
        }

        $payload = array_filter([
            'page' => $this->resolveMetadata($request) ?: null,
            'record' => $rawContext,
        ]);

        return "Current page context:\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, string>
     */
    protected function resolveMetadata(Request $request): array
    {
        $metadata = [];

        if ($panelId = Filament::getCurrentPanel()?->getId()) {
            $metadata['panel'] = $panelId;
        }

        if ($referer = $request->headers->get('Referer')) {
            $metadata['url'] = $referer;
        }

        return $metadata;
    }
}

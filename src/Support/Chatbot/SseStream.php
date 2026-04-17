<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

class SseStream
{
    public function event(object|array|string $data): void
    {
        $payload = is_string($data) ? $data : json_encode($data);

        echo 'data: ' . $payload . "\n\n";

        $this->flush();
    }

    public function done(): void
    {
        echo "data: [DONE]\n\n";

        $this->flush();
    }

    protected function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}

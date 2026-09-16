<?php

namespace Wotz\FilamentChatbot\Support\Chatbot;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Tools\McpServerTool;
use Laravel\Ai\Tools\Request;

/**
 * Wraps a Laravel MCP server tool for the agent. Unlike the MCP server, Laravel AI
 * does not translate validation failures into a tool error, so an invalid argument
 * would abort the whole chat stream; here it becomes an error the model can act on.
 */
class McpTool extends McpServerTool
{
    public function handle(Request $request): string
    {
        try {
            return parent::handle($request);
        } catch (ValidationException $exception) {
            $messages = collect($exception->errors())
                ->map(fn (array $messages, string $field): string => $field . ': ' . implode(' ', $messages))
                ->implode('; ');

            return $this->errorMessage('Invalid arguments. ' . $messages);
        } catch (ModelNotFoundException) {
            return $this->errorMessage('No matching record was found.');
        }
    }

    /**
     * The wrapped MCP server tool.
     */
    public function underlying(): object
    {
        return $this->tool;
    }
}

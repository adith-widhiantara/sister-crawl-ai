<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SisterDataAgent;
use App\Ai\Tools\SearchCrawledSdmData;
use App\Models\AiSearchLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Ai\Streaming\Events\Error as StreamError;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\ReasoningStart;
use Laravel\Ai\Streaming\Events\ToolCall as ToolCallEvent;
use Laravel\Ai\Streaming\Events\ToolResult as ToolResultEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AiSearchController extends Controller
{
    public function index(): View
    {
        return view('ai-search.index');
    }

    /**
     * Stream the agent's progress as Server-Sent Events so the page can show
     * real-time status (thinking / querying / done) instead of a blind spinner.
     */
    public function askStream(Request $request): StreamedResponse
    {
        $question = $request->validate(['question' => 'required|string|max:2000'])['question'];

        return response()->stream(function () use ($question) {
            $emit = function (array $payload) {
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            };

            $tool = new SearchCrawledSdmData;
            $agent = new SisterDataAgent($tool);
            $startedAt = microtime(true);

            $emit(['type' => 'status', 'message' => 'Mengirim pertanyaan ke AI…']);

            try {
                $meta = null;
                $stream = $agent->stream($question)->then(function ($streamed) use (&$meta) {
                    $meta = $streamed->meta;
                });

                foreach ($stream as $event) {
                    match (true) {
                        $event instanceof ReasoningStart,
                        $event instanceof ReasoningDelta => $emit(['type' => 'status', 'message' => 'AI sedang berpikir…']),
                        $event instanceof ToolCallEvent => $emit(['type' => 'status', 'message' => 'Menjalankan pencarian ke database…']),
                        $event instanceof ToolResultEvent => $emit(['type' => 'status', 'message' => 'Data ditemukan, menyusun ringkasan…']),
                        $event instanceof StreamError => $emit(['type' => 'status', 'message' => 'Mencoba lagi…']),
                        default => null,
                    };
                }

                $emit([
                    'type' => 'done',
                    'summary' => $stream->text,
                    'columns' => $tool->lastResult['columns'] ?? [],
                    'rows' => $tool->lastResult['rows'] ?? [],
                ]);

                AiSearchLog::create([
                    'question' => $question,
                    'provider' => $meta?->provider,
                    'model' => $meta?->model,
                    'tool_arguments' => $tool->lastArguments,
                    'columns' => $tool->lastResult['columns'] ?? null,
                    'rows' => $tool->lastResult['rows'] ?? null,
                    'result_count' => $tool->lastResult['count'] ?? null,
                    'summary' => $stream->text,
                    'status' => 'success',
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
            } catch (Throwable $e) {
                $emit(['type' => 'error', 'message' => $e->getMessage()]);

                AiSearchLog::create([
                    'question' => $question,
                    'tool_arguments' => $tool->lastArguments,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}

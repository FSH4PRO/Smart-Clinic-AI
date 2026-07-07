<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Enums\AiFeature;
use App\Models\AiUsageLog;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class GeminiService
{
    /**
     * Gemini 1.5 Flash model identifier.
     */
    private const DEFAULT_MODEL = 'gemini-2.5-flash';

    /**
     * @var string
     */
    private string $apiKey;

    /**
     * @var string
     */
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = (string) config('services.gemini.model', self::DEFAULT_MODEL);

        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is missing. Set it in .env or config/services.php');
        }
    }

    /**
     * Calls Gemini and forces JSON-only response.
     *
     * @param AiFeature $feature
     * @param string $clinicId
     * @param string|null $systemInstruction
     * @param string $userPrompt
     * @param array<string,mixed> $extraModelParams
     * @return array<string,mixed>
     */
    public function generateJson(
        AiFeature $feature,
        string $clinicId,
        ?string $systemInstruction,
        string $userPrompt,
        array $extraModelParams = []
    ): array {
        $start = hrtime(true);

        // Fix: Append the API key as a query parameter strictly per the Google AI Studio REST spec
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $this->model,
            $this->apiKey
        );

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ],
            ],
            'generationConfig' => array_merge(
                [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.2,
                ],
                $extraModelParams
            ),
        ];

        if ($systemInstruction !== null && trim($systemInstruction) !== '') {
            $payload['systemInstruction'] = [
                'role' => 'system',
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $response = null;
        $log = null;

        try {
            // Fix: Removed ->withToken() which incorrectly passed the Google API key as a Bearer OAuth token
            $httpResponse = Http::timeout(45)
                ->retry(3, 250, function (Throwable $e): bool {
                    return true;
                })
                ->post($url, $payload);

            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            if (! $httpResponse->successful()) {
                Log::error('Gemini request failed', [
                    'feature' => $feature->value,
                    'clinic_id' => $clinicId,
                    'status' => $httpResponse->status(),
                    'body' => $httpResponse->body(),
                    'url' => 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent',
                ]);

                throw new RuntimeException('Gemini API request failed.');
            }

            $response = $httpResponse->json();

            $rawText = $this->extractTextFromGeminiResponse($response);
            $decoded = json_decode($rawText, true);

            if (! is_array($decoded)) {
                Log::error('Gemini returned non-JSON output', [
                    'feature' => $feature->value,
                    'clinic_id' => $clinicId,
                    'raw_text' => $rawText,
                    'response' => $response,
                ]);

                throw new RuntimeException('Gemini output is not valid JSON.');
            }

            $usage = Arr::get($response, 'usageMetadata', []);
            $inputTokens = (int) Arr::get($usage, 'promptTokenCount', 0);
            $outputTokens = (int) Arr::get($usage, 'candidatesTokenCount', 0);

            // Free tier pricing calculation fallback
            $costUsd = (float) (Arr::get($usage, 'totalCostUsd', 0.0));
            if ($costUsd <= 0.0) {
                $costUsd = 0.0;
            }

            $log = AiUsageLog::create([
                'clinic_id' => $clinicId,
                'feature' => $feature->value,
                'model' => $this->model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost_usd' => $costUsd,
                'duration_ms' => $durationMs,
                'created_at' => now(),
            ]);

            return $decoded;
        } catch (Throwable $e) {
            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            try {
                $log ??= AiUsageLog::create([
                    'clinic_id' => $clinicId,
                    'feature' => $feature->value,
                    'model' => $this->model,
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'cost_usd' => 0.0,
                    'duration_ms' => $durationMs,
                    'created_at' => now(),
                ]);
            } catch (Throwable $ignored) {
                // avoid masking original exception
            }

            Log::error('GeminiService exception', [
                'feature' => $feature->value,
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function extractTextFromGeminiResponse(array $response): string
    {
        // Expected shape:
        // {
        //   candidates: [ { content: { parts: [ { text: "{...}" } ] } } ]
        // }
        $text = Arr::get($response, 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini response does not include expected JSON text.');
        }

        return $text;
    }
}
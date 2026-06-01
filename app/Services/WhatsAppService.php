<?php

namespace App\Services;

use App\Exceptions\WhatsAppServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp OTP message using UltraMsg.
     *
     * @param  string  $phone
     * @param  string  $code
     * @return array<string, mixed>
     *
     * @throws WhatsAppServiceException
     */
    public function sendOtp(string $phone, string $code): array
    {
        $instanceId = config('ultramsg.instance_id');
        $token = config('ultramsg.client_token');
        $formattedPhone = $this->formatPhone($phone);
        $endpoint = "https://api.ultramsg.com/{$instanceId}/messages/chat";
        $message = "Your SmartClinic AI verification code is {$code}. It expires in 5 minutes.";

        if (! $instanceId || ! $token) {
            throw new WhatsAppServiceException('UltraMsg configuration is missing.');
        }

        try {
            $response = Http::retry(3, 200, function ($exception, $request) {
                return $exception instanceof ConnectionException;
            })->timeout(15)
                ->post($endpoint, [
                    'token' => $token,
                    'to' => $formattedPhone,
                    'body' => $message,
                ]);

            if (! $response->successful()) {
                Log::error('UltraMsg API request failed', [
                    'endpoint' => $endpoint,
                    'phone' => $formattedPhone,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new WhatsAppServiceException('Unable to send OTP via WhatsApp.');
            }

            return $response->json();
        } catch (\Throwable $exception) {
            Log::error('UltraMsg API exception', [
                'phone' => $formattedPhone,
                'error' => $exception->getMessage(),
            ]);

            throw new WhatsAppServiceException('Unable to send OTP via WhatsApp.', previous: $exception);
        }
    }

    /**
     * Format a phone number for UltraMsg.
     *
     * UltraMsg requires international format without the leading '+' symbol.
     *
     * @param  string  $phone
     * @return string
     *
     * @throws WhatsAppServiceException
     */
    protected function formatPhone(string $phone): string
    {
        $formatted = preg_replace('/\D+/', '', $phone);

        if (! $formatted || ! preg_match('/^[1-9]\d{9,14}$/', $formatted)) {
            throw new WhatsAppServiceException('The phone number format is invalid for WhatsApp delivery.');
        }

        return $formatted;
    }
}

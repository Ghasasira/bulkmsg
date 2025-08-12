<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use App\Models\MessageLog;
use Illuminate\Support\Facades\Auth;

class WhatsAppService
{
    protected $twilio;
    protected $fromNumber;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->fromNumber = 'whatsapp:' . config('services.twilio.whatsapp_from');

        $this->twilio = new Client($sid, $token);
    }

    /**
     * Send bulk WhatsApp messages
     *
     * @param array $recipients - phone numbers in E.164 format without 'whatsapp:' prefix
     * @param string $message
     * @return array
     */
    public function sendBulkWhatsApp(array $recipients, string $message): array
    {
        $successCount = 0;
        $failCount = 0;
        $details = [];

        foreach ($recipients as $number) {
            try {
                $response = $this->twilio->messages->create(
                    'whatsapp:' . $number,
                    [
                        'from' => $this->fromNumber,
                        'body' => $message
                    ]
                );

                $details[] = [
                    'number' => $number,
                    'status' => 'sent',
                    'sid' => $response->sid
                ];
                $successCount++;
            } catch (\Exception $e) {
                Log::error("WhatsApp send failed for {$number}: " . $e->getMessage());

                $details[] = [
                    'number' => $number,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                $failCount++;
            }
        }

        // Log the message
        MessageLog::create([
            'type' => 'whatsapp',
            'content' => $message,
            'total_recipients' => count($recipients),
            'success_count' => $successCount,
            'failed_count' => $failCount,
            'details' => $details,
            'user_id' => Auth::id()
        ]);

        return [
            'success' => $failCount === 0,
            'message' => 'WhatsApp sending completed',
            'data' => [
                'total' => count($recipients),
                'successful' => $successCount,
                'failed' => $failCount,
                'details' => $details
            ]
        ];
    }
}

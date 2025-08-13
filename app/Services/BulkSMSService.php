<?php

namespace App\Services;

use App\Models\MessageLog;
use Exception;
use Illuminate\Support\Facades\Auth;

class BulkSMSService
{
    public function sendBulkSms(array $phoneNumbers, string $message, string $senderId = 'Default', string $defaultCountryCode = '256'): array
    {
        $username = env('EGOSMS_USERNAME');
        $password = env('EGOSMS_PASSWORD');

        if (!$username || !$password) {
            throw new Exception("EgoSMS credentials are missing.");
        }

        // Format phone numbers
        $formattedNumbers = array_unique($this->formatPhoneNumbers($phoneNumbers, $defaultCountryCode));

        // Build msgdata array (EgoSMS requires this format)
        $msgData = [];
        foreach ($formattedNumbers as $number) {
            $msgData[] = [
                'number'   => str_replace('+', '', $number), // EgoSMS expects without '+'
                'message'  => $message,
                'senderid' => "Ghasasira" // Max 11 chars
            ];
        }

        $payload = [
            'method'   => 'SendSms',
            'userdata' => [
                'username' => $username,
                'password' => $password,
                'msgdata'  => $msgData
            ]
        ];

        // Send request
        $ch = curl_init('https://www.egosms.co/api/v1/json/');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        // Handle cURL errors
        if ($error) {
            $this->logMessage($message, $formattedNumbers, 0, count($formattedNumbers), []);
            return [
                'success' => false,
                'message' => "cURL Error: $error",
                'data'    => []
            ];
        }

        $response = json_decode($output, true);

        // Process EgoSMS API response
        $stats = [
            'total' => count($formattedNumbers),
            'successful' => 0,
            'failed' => 0,
            'details' => []
        ];

        if (!empty($response['success']) && $response['success'] === true) {
            foreach ($msgData as $msg) {
                $stats['successful']++;
                $stats['details'][] = [
                    'number' => $msg['number'],
                    'status' => 'success',
                    'message_status' => 'Sent'
                ];
            }
        } else {
            foreach ($msgData as $msg) {
                $stats['failed']++;
                $stats['details'][] = [
                    'number' => $msg['number'],
                    'status' => 'error',
                    'message_status' => $response['error'] ?? 'Unknown Error'
                ];
            }
        }

        // Log to database
        $this->logMessage($message, $formattedNumbers, $stats['successful'], $stats['failed'], $stats['details']);

        return [
            'success' => true,
            'message' => 'Bulk SMS sent via EgoSMS',
            'data'    => $stats
        ];
    }

    /**
     * Format phone numbers into E.164 format.
     */
    public function formatPhoneNumbers(array $numbers, string $defaultCountryCode = '256'): array
    {
        $formattedNumbers = [];

        foreach ($numbers as $number) {
            $cleaned = preg_replace('/[^0-9]/', '', $number);

            if (strlen($cleaned) === 9 && strpos($cleaned, '0') !== 0) {
                $formatted = '+' . $defaultCountryCode . $cleaned;
            } elseif (strlen($cleaned) === 10 && strpos($cleaned, '0') === 0) {
                $formatted = '+' . $defaultCountryCode . substr($cleaned, 1);
            } elseif (strlen($cleaned) === 12 && strpos($cleaned, $defaultCountryCode) === 0) {
                $formatted = '+' . $cleaned;
            } else {
                $formatted = $cleaned;
            }

            $formattedNumbers[] = $formatted;
        }

        return $formattedNumbers;
    }

    /**
     * Save log to database.
     */
    private function logMessage(string $message, array $numbers, int $successCount, int $failCount, array $details)
    {
        MessageLog::create([
            'type' => 'sms',
            'content' => $message,
            'total_recipients' => count($numbers),
            'success_count' => $successCount,
            'failed_count' => $failCount,
            'cost' => 0,
            'details' => $details,
            'user_id' => Auth::id()
        ]);
    }
}

<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\MessageLog;
use Illuminate\Support\Facades\Auth;

class BulkSMSService
{
    private $AT;
    private $smsService;

    public function __construct(string $username, string $apiKey)
    {
        $this->AT = new AfricasTalking($username, $apiKey);
        $this->smsService = $this->AT->sms();
    }

    public function sendBulkSMS(array $phoneNumbers, string $message, ?string $senderId = null): array
    {
        try {
            $recipients = implode(',', $phoneNumbers);

            $params = [
                'to'      => $recipients,
                'message' => $message
            ];

            if ($senderId !== null) {
                $params['from'] = substr($senderId, 0, 11);
            }

            $result = $this->smsService->send($params);

            $stats = [
                'total' => count($phoneNumbers),
                'successful' => 0,
                'failed' => 0,
                'cost' => 0,
                'details' => []
            ];

            if (isset($result['data']->SMSMessageData->Recipients)) {
                foreach ($result['data']->SMSMessageData->Recipients as $recipient) {
                    $stats['details'][] = [
                        'number' => $recipient->number,
                        'status' => $recipient->status,
                        'cost' => $recipient->cost ?? '0'
                    ];

                    if ($recipient->status === 'Success') {
                        $stats['successful']++;
                        $stats['cost'] += (float)($recipient->cost ?? '0');
                    } else {
                        $stats['failed']++;
                    }
                }
            }

            // Log the message
            MessageLog::create([
                'type' => 'sms',
                'content' => $message,
                'total_recipients' => $stats['total'],
                'success_count' => $stats['successful'],
                'failed_count' => $stats['failed'],
                'cost' => $stats['cost'],
                'details' => $stats['details'],
                'user_id' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'data' => $stats
            ];
        } catch (\Exception $e) {
            MessageLog::create([
                'type' => 'sms',
                'content' => $message,
                'total_recipients' => count($phoneNumbers),
                'success_count' => 0,
                'failed_count' => count($phoneNumbers),
                'cost' => 0,
                'details' => [],
                'user_id' => Auth::id()
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => [
                    'total' => count($phoneNumbers),
                    'successful' => 0,
                    'failed' => count($phoneNumbers),
                    'cost' => 0,
                    'details' => []
                ]
            ];
        }
    }

    public function formatPhoneNumbers(array $numbers, string $defaultCountryCode = '254'): array
    {
        $formattedNumbers = [];

        foreach ($numbers as $number) {
            $cleaned = preg_replace('/[^0-9]/', '', $number);

            if (strlen($cleaned) === 9 && strpos($cleaned, '0') !== 0) {
                $formatted = '+' . $defaultCountryCode . $cleaned;
            } elseif (strlen($cleaned) === 10 && strpos($cleaned, '0') === 0) {
                $formatted = '+' . $defaultCountryCode . substr($cleaned, 1);
            } elseif (strlen($cleaned) === 12 && strpos($cleaned, '254') === 0) {
                $formatted = '+' . $cleaned;
            } else {
                $formatted = $cleaned;
            }

            $formattedNumbers[] = $formatted;
        }

        return $formattedNumbers;
    }
}

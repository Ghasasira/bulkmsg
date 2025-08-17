<?php

namespace App\Services;

use App\Models\MessageLog;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class BulkSMSService
{
    private $apiUrl = 'https://www.egosms.co/api/v1/json/';
    private $timeout = 30;
    private $maxRetriesPerBatch = 3;
    private $batchSize = 100; // Process in batches to avoid timeouts

    public function __construct()
    {
        $this->validateCredentials();
    }

    /**
     * Send bulk SMS with enhanced error handling and batch processing
     */
    // public function sendBulkSms(
    //     $customers,
    //     string $message,
    //     string $senderId = 'Default',
    //     string $defaultCountryCode = '256',
    //     bool $processByBatch = true
    // ): array {
    //     try {
    //         // Validate inputs
    //         $this->validateInputs($customers, $message, $senderId);



    //         // Format and deduplicate phone numbers
    //         $formattedNumbers = array_unique($this->formatPhoneNumbers($phoneNumbers, $defaultCountryCode));

    //         if (empty($formattedNumbers)) {
    //             throw new Exception("No valid phone numbers found.");
    //         }

    //         // Process in batches if requested and needed
    //         if ($processByBatch && count($formattedNumbers) > $this->batchSize) {
    //             return $this->processBatches($formattedNumbers, $message, $senderId);
    //         }

    //         // Process single batch
    //         return $this->processSingleBatch($formattedNumbers, $message, $senderId);
    //     } catch (Exception $e) {
    //         Log::error('BulkSMS Error: ' . $e->getMessage(), [
    //             'phone_numbers_count' => count($phoneNumbers ?? []),
    //             'message_length' => strlen($message ?? ''),
    //             'sender_id' => $senderId
    //         ]);

    //         $this->logMessage($message, $phoneNumbers, 0, count($phoneNumbers), [], $e->getMessage());

    //         return [
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //             'data' => [
    //                 'total' => count($phoneNumbers),
    //                 'successful' => 0,
    //                 'failed' => count($phoneNumbers),
    //                 'details' => []
    //             ]
    //         ];
    //     }
    // }

    public function sendBulkSms(
        $customers,
        string $message,
        string $senderId = 'Default',
        string $defaultCountryCode = '256',
        bool $processByBatch = true
    ): array {
        try {
            if ($customers->isEmpty()) {
                // dd("no numbers");
                throw new Exception("No customers provided.");
            }

            // Prepare numbers + personalized messages
            $messagesData = [];
            foreach ($customers as $customer) {
                $formattedNumber = $this->formatPhoneNumbers([$customer->number1], $defaultCountryCode);

                if (!empty($formattedNumber)) {
                    $personalizedMessage = $this->customizeMessage($message, $customer);
                    $messagesData[] = [
                        'number'  => $formattedNumber[0],
                        'message' => $personalizedMessage,
                        'customer_id' => $customer->id
                    ];
                }
            }

            // // dd($messagesData);

            if (empty($messagesData)) {
                throw new Exception("No valid customer phone numbers found.");
            }

            // // dd($messagesData);
            // If batching is enabled
            if ($processByBatch && count($messagesData) > $this->batchSize) {
                // dd("batching");
                return $this->processCustomerBatches($messagesData, $senderId);
            }

            // Otherwise, single batch
            // // dd("no batching");
            return $this->processCustomerBatch($messagesData, $senderId);
        } catch (Exception $e) {
            Log::error('BulkSMS Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'total' => $customers->count(),
                    'successful' => 0,
                    'failed' => $customers->count(),
                    'details' => []
                ]
            ];
        }
    }

    private function customizeMessage(string $template, $customer): string
    {
        try {
            $replacements = [
                'NAME'     => $customer->name ?? '',
                'AMOUNT'   => $customer->local_amt ?? '',
                'PASTDUE' => $customer->no_due_days ?? '',
            ];

            $message = $template;
            foreach ($replacements as $key => $value) {
                $message = str_replace(strtoupper($key), $value, $message);
            }

            return $message;
        } catch (Exception $e) {
            // dd('failed to customize' . $e);
            throw new Exception("Something went wrong.");
        }
    }

    private function processCustomerBatches(array $messagesData, string $senderId): array
    {
        // // dd("we here...");
        $batches = array_chunk($messagesData, $this->batchSize);
        $aggregatedStats = [
            'total' => count($messagesData),
            'successful' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($batches as $batchIndex => $batch) {
            Log::info("Processing customer SMS batch " . ($batchIndex + 1));

            $batchResult = $this->processCustomerBatch($batch, $senderId, false);

            $aggregatedStats['successful'] += $batchResult['data']['successful'];
            $aggregatedStats['failed'] += $batchResult['data']['failed'];
            $aggregatedStats['details'] = array_merge($aggregatedStats['details'], $batchResult['data']['details']);

            usleep(500000); // half second delay
        }

        return [
            'success' => true,
            'message' => 'Bulk SMS processing completed',
            'data' => $aggregatedStats
        ];
    }

    private function processCustomerBatch(array $messagesData, string $senderId, bool $shouldLog = true): array
    {
        $msgData = [];
        foreach ($messagesData as $msg) {
            $msgData[] = [
                'number'   => $msg['number'],
                'message'  => $msg['message'],
                'senderid' => $senderId
            ];
        }

        // // dd($msgData);

        $payload = $this->buildPayload($msgData);
        // // dd($payload);
        $response = $this->sendWithRetry($payload);
        // dd($response);

        $stats = $this->processResponse($response, $msgData);

        if ($shouldLog) {
            $this->logMessage(
                "Bulk Personalized Message",
                array_column($messagesData, 'number'),
                $stats['successful'],
                $stats['failed'],
                $stats['details']
            );
        }

        return [
            'success' => true,
            'message' => 'Customer SMS batch processed',
            'data' => $stats
        ];
    }



    /**
     * Process messages in batches to handle large volumes
     */
    private function processBatches(array $numbers, string $message, string $senderId): array
    {
        $batches = array_chunk($numbers, $this->batchSize);
        $aggregatedStats = [
            'total' => count($numbers),
            'successful' => 0,
            'failed' => 0,
            'details' => [],
            'batch_results' => []
        ];

        foreach ($batches as $batchIndex => $batch) {
            Log::info("Processing SMS batch " . ($batchIndex + 1) . " of " . count($batches));
            // dd("we here...");
            $batchResult = $this->processSingleBatch($batch, $message, $senderId, false); // Don't log individual batches

            $aggregatedStats['successful'] += $batchResult['data']['successful'];
            $aggregatedStats['failed'] += $batchResult['data']['failed'];
            $aggregatedStats['details'] = array_merge($aggregatedStats['details'], $batchResult['data']['details']);
            $aggregatedStats['batch_results'][] = [
                'batch' => $batchIndex + 1,
                'result' => $batchResult
            ];

            // Small delay between batches to avoid overwhelming the API
            usleep(500000); // 0.5 seconds
        }

        // Log the final aggregated result
        $this->logMessage($message, $numbers, $aggregatedStats['successful'], $aggregatedStats['failed'], $aggregatedStats['details']);

        return [
            'success' => true,
            'message' => 'Bulk SMS processing completed',
            'data' => $aggregatedStats
        ];
    }

    /**
     * Process a single batch of messages
     */
    private function processSingleBatch(array $numbers, string $message, string $senderId, bool $shouldLog = true): array
    {
        // dd("we here...");
        $msgData = $this->buildMessageData($numbers, $message, $senderId);
        // dd("we here...1");
        $payload = $this->buildPayload($msgData);

        // dd("we here... 2");
        // Attempt to send with retries
        $response = $this->sendWithRetry($payload);
        // dd("we here yet?");

        // Process response and generate stats
        $stats = $this->processResponse($response, $msgData);

        // Log if requested
        if ($shouldLog) {
            $this->logMessage($message, $numbers, $stats['successful'], $stats['failed'], $stats['details']);
        }

        return [
            'success' => true,
            'message' => 'SMS batch processed',
            'data' => $stats
        ];
    }

    /**
     * Build message data array for API
     */
    private function buildMessageData(array $numbers, string $message, string $senderId): array
    {
        $msgData = [];
        foreach ($numbers as $number) {
            $msgData[] = [
                'number'   => str_replace('+', '', $number),
                'message'  => $message,
                'senderid' => "Ghasasira"
            ];
        }
        return $msgData;
    }

    /**
     * Build API payload
     */
    private function buildPayload(array $msgData): array
    {
        return [
            'method'   => 'SendSms',
            'userdata' => [
                'username' => env('EGOSMS_USERNAME'),
                'password' => env('EGOSMS_PASSWORD')
            ],
            'msgdata'  => $msgData
        ];
    }

    /**
     * Send request with retry mechanism using Laravel HTTP client
     */
    private function sendWithRetry(array $payload): array
    {
        // // dd($payload);
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetriesPerBatch; $attempt++) {
            try {
                Log::info("SMS API attempt $attempt of {$this->maxRetriesPerBatch}");

                $response = Http::timeout($this->timeout)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($this->apiUrl, $payload);


                if ($response->successful()) {
                    // dd("success");
                    return $response->json() ?? [];
                } else {
                    // dd("failure");
                    throw new RequestException($response);
                }
            } catch (Exception $e) {
                // // dd($e . ".......wooo....");

                $lastException = $e;
                Log::warning("SMS API attempt $attempt failed: " . $e->getMessage());

                if ($attempt < $this->maxRetriesPerBatch) {
                    sleep(pow(2, $attempt)); // Exponential backoff
                }
            }
        }

        // All attempts failed
        // dd('all failed');
        throw new Exception("Failed to send SMS after {$this->maxRetriesPerBatch} attempts. Last error: " . $lastException->getMessage());
    }

    /**
     * Process API response and generate statistics
     */
    private function processResponse(array $response, array $msgData): array
    {
        try {
            $stats = [
                'total' => count($msgData),
                'successful' => 0,
                'failed' => 0,
                'details' => []
            ];

            Log::info('EgoSMS API Response:', $response);

            // Check for different response formats
            $isSuccess = $this->isResponseSuccessful($response);

            if ($isSuccess) {
                // All messages successful
                foreach ($msgData as $msg) {
                    $stats['successful']++;
                    $stats['details'][] = [
                        'number' => $msg['number'],
                        'status' => 'success',
                        'message_status' => 'Sent',
                        'api_response' => $response['Message'] ?? $response['message'] ?? 'Success'
                    ];
                }
            } else {
                // Handle different types of failures
                $errorMessage = $this->extractErrorMessage($response);

                foreach ($msgData as $msg) {
                    $stats['failed']++;
                    $stats['details'][] = [
                        'number' => $msg['number'],
                        'status' => 'error',
                        'message_status' => $errorMessage,
                        'api_response' => $response
                    ];
                }
            }

            return $stats;
        } catch (Exception $e) {
            // dd($e);
            throw new Exception("Something went wrong.");
        }
    }

    /**
     * Check if API response indicates success
     */
    private function isResponseSuccessful(array $response): bool
    {
        // Handle different response formats from EgoSMS
        if (isset($response['success']) && $response['success'] === true) {
            return true;
        }

        if (isset($response['Status'])) {
            return in_array(strtolower($response['Status']), ['success', 'sent', 'ok', 'delivered']);
        }

        if (isset($response['status'])) {
            return in_array(strtolower($response['status']), ['success', 'sent', 'ok', 'delivered']);
        }

        // If no clear status, assume failure for safety
        return false;
    }

    /**
     * Extract error message from API response
     */
    private function extractErrorMessage(array $response): string
    {
        // Try different possible error message fields
        $possibleErrorFields = ['Message', 'message', 'error', 'Error', 'error_message'];

        foreach ($possibleErrorFields as $field) {
            if (isset($response[$field]) && !empty($response[$field])) {
                return $response[$field];
            }
        }

        // Return status if available
        if (isset($response['Status'])) {
            return "API returned status: " . $response['Status'];
        }

        return 'Unknown Error - Check API response';
    }

    /**
     * Enhanced phone number formatting with better validation
     */
    public function formatPhoneNumbers(array $numbers, string $defaultCountryCode = '256'): array
    {
        $formattedNumbers = [];
        $validPatterns = [
            '256' => [
                'regex' => '/^256[0-9]{9}$/',
                'description' => 'Uganda format: 256XXXXXXXXX'
            ]
            // Add more country patterns as needed
        ];

        foreach ($numbers as $number) {
            if (empty(trim($number))) {
                continue;
            }

            $cleaned = preg_replace('/[^0-9]/', '', trim($number));

            // Try different formatting approaches
            $formatted = null;

            // 9 digits (missing leading 0 and country code)
            if (strlen($cleaned) === 9 && !str_starts_with($cleaned, '0')) {
                $formatted = $defaultCountryCode . $cleaned;
            }
            // 10 digits with leading 0 (local format)
            elseif (strlen($cleaned) === 10 && str_starts_with($cleaned, '0')) {
                $formatted = $defaultCountryCode . substr($cleaned, 1);
            }
            // 12 digits with country code
            elseif (strlen($cleaned) === 12 && str_starts_with($cleaned, $defaultCountryCode)) {
                $formatted = $cleaned;
            }
            // 13 digits with + prefix removed
            elseif (strlen($cleaned) === 12) {
                $formatted = $cleaned;
            }

            // Validate against known patterns
            if ($formatted && isset($validPatterns[$defaultCountryCode])) {
                if (preg_match($validPatterns[$defaultCountryCode]['regex'], $formatted)) {
                    $formattedNumbers[] = $formatted;
                } else {
                    Log::warning("Invalid phone number format: $number (cleaned: $cleaned, formatted: $formatted)");
                }
            } elseif ($formatted) {
                $formattedNumbers[] = $formatted; // Accept if no validation pattern available
            } else {
                Log::warning("Could not format phone number: $number");
            }
        }

        return $formattedNumbers;
    }

    /**
     * Enhanced logging with better error tracking
     */
    private function logMessage(
        string $message,
        array $numbers,
        int $successCount,
        int $failCount,
        array $details,
        ?string $errorMessage = null
    ): void {
        try {
            MessageLog::create([
                'type' => 'sms',
                'content' => $message,
                'total_recipients' => count($numbers),
                'success_count' => $successCount,
                'failed_count' => $failCount,
                'cost' => $this->estimateCost($successCount),
                'details' => $details,
                'user_id' => Auth::id(),
                'error_message' => $errorMessage,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log SMS message: ' . $e->getMessage());
        }
    }

    /**
     * Estimate SMS cost (customize based on your pricing)
     */
    private function estimateCost(int $successfulMessages): float
    {
        $costPerSMS = 0.05; // Adjust based on your provider's pricing
        return $successfulMessages * $costPerSMS;
    }

    /**
     * Validate credentials on service initialization
     */
    private function validateCredentials(): void
    {
        if (!env('EGOSMS_USERNAME') || !env('EGOSMS_PASSWORD')) {
            throw new Exception("EgoSMS credentials are missing in environment variables.");
        }
    }

    /**
     * Validate input parameters
     */
    private function validateInputs(array $phoneNumbers, string $message, string $senderId): void
    {
        if (empty($phoneNumbers)) {
            throw new Exception("Phone numbers array cannot be empty.");
        }

        if (empty(trim($message))) {
            throw new Exception("Message content cannot be empty.");
        }

        if (strlen($message) > 1600) { // SMS character limit
            throw new Exception("Message is too long. Maximum 1600 characters allowed.");
        }

        if (strlen($senderId) > 11) {
            throw new Exception("Sender ID cannot be longer than 11 characters.");
        }

        if (count($phoneNumbers) > 10000) { // Reasonable limit
            throw new Exception("Too many recipients. Maximum 10,000 allowed per request.");
        }
    }

    /**
     * Get SMS delivery status (if supported by provider)
     */
    public function getDeliveryStatus(string $messageId): array
    {
        // Implement if EgoSMS supports delivery status checking
        return [
            'success' => false,
            'message' => 'Delivery status checking not implemented yet'
        ];
    }

    /**
     * Get account balance from EgoSMS
     */
    public function getAccountBalance(): array
    {
        try {
            $payload = [
                'method' => 'Balance',
                'userdata' => [
                    'username' => env('EGOSMS_USERNAME'),
                    'password' => env('EGOSMS_PASSWORD')
                ]
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'balance' => $data['balance'] ?? 'Unknown',
                    'currency' => $data['currency'] ?? 'UGX',
                    'response' => $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch balance',
                'response' => $response->json()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching balance: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test EgoSMS connection and credentials
     */
    public function testConnection(): array
    {
        try {
            // Test with balance check as it's usually a lightweight operation
            $balanceResult = $this->getAccountBalance();

            if ($balanceResult['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'balance' => $balanceResult['balance'] ?? 'Unknown'
                ];
            }

            // If balance fails, try a simple test message to a dummy number
            $testPayload = [
                'method' => 'SendSms',
                'userdata' => [
                    'username' => env('EGOSMS_USERNAME'),
                    'password' => env('EGOSMS_PASSWORD'),
                    'msgdata' => [
                        [
                            'number' => '256700000000', // Dummy number for testing
                            'message' => 'Test connection',
                            'senderid' => 'Test'
                        ]
                    ]
                ]
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl, $testPayload);

            $data = $response->json();

            // Even if the test message fails, we can determine if credentials are valid
            if (isset($data['Message'])) {
                $message = $data['Message'];
                if (strpos($message, 'Invalid username or password') !== false) {
                    return [
                        'success' => false,
                        'message' => 'Invalid credentials'
                    ];
                }

                return [
                    'success' => true,
                    'message' => 'Credentials valid but check account balance/billing',
                    'api_message' => $message
                ];
            }

            return [
                'success' => false,
                'message' => 'Unknown connection issue',
                'response' => $data
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Customer;
use App\Services\MessageService;
use App\Services\BulkSMSService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    protected $smsService;
    protected $whatsappService;

    public function __construct(BulkSMSService $smsService) // <-- inject here
    {
        $this->smsService = $smsService;
        $this->whatsappService = app('WhatsAppService'); // Keep WhatsApp as before
    }

    public function create(): Response
    {
        $users = Customer::select('id', 'name', 'number1')
            ->latest()
            ->get();

        return Inertia::render('sendMessage/index', [
            'users' => $users
        ]);
    }

    public function schedule(): Response
    {
        $users = Contact::select('id', 'name', 'phone', 'email')
            ->latest()
            ->get();

        return Inertia::render('schedule/index', [
            'users' => $users
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'type' => 'required|in:sms,whatsapp',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'exists:customers,id',
            'scheduledAt' => 'nullable|date|after:now',
        ]);

        try {
            // Get phone numbers for selected contacts
            $customers = Customer::whereIn('id', $validated['recipients'])->get();
            // ->pluck('number1')
            // ->toArray();

            if (empty($customers)) {
                return redirect()->route('dashboard')->with('error', 'No valid phone numbers found for selected recipients');
            }

            // dd($phoneNumbers);

            if ($validated['type'] === 'sms') {
                // dd("lets roll");
                return $this->sendSMS($customers, $validated['content']);
            } elseif ($validated['type'] === 'whatsapp') {
                return;
                // $this->sendWhatsApp($customers, $validated['content']);
            }

            return redirect()->route('dashboard')->with('error', 'Invalid message type');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to process message: ' . $e->getMessage());
        }
    }

    private function sendSMS($customers, string $message)
    {
        // dd($customers);
        // BulkSMSService handles formatting internally, so no pre-formatting needed
        $result = $this->smsService->sendBulkSms(
            $customers,
            $message,
            config('app.name')
        );

        // dd($result);

        if ($result['success']) {
            return redirect()->route('dashboard')->with('success', $result['message']);
        }
        // dd($result['message']);
        return redirect()->route('dashboard')->with('error', $result['message']);
    }


    private function sendWhatsApp($customers, string $message)
    {
        // Format phone numbers for WhatsApp (ensure they're in E.164 format)
        $formattedNumbers = $this->formatPhoneNumbersForWhatsApp($customers);

        // Send WhatsApp messages
        $result = $this->whatsappService->sendBulkWhatsApp($formattedNumbers, $message);

        if ($result['success']) {
            return redirect()->route('dashboard')->with('success', $result['message']);
        }

        return redirect()->route('dashboard')->with('error', $result['message']);
    }

    private function formatPhoneNumbersForWhatsApp(array $numbers, string $defaultCountryCode = '254'): array
    {
        $formattedNumbers = [];

        foreach ($numbers as $number) {
            $cleaned = preg_replace('/[^0-9]/', '', $number);

            // Format for WhatsApp (E.164 format without + prefix for Twilio)
            if (strlen($cleaned) === 9 && strpos($cleaned, '0') !== 0) {
                $formatted = $defaultCountryCode . $cleaned;
            } elseif (strlen($cleaned) === 10 && strpos($cleaned, '0') === 0) {
                $formatted = $defaultCountryCode . substr($cleaned, 1);
            } elseif (strlen($cleaned) === 12 && strpos($cleaned, '254') === 0) {
                $formatted = $cleaned;
            } elseif (strlen($cleaned) === 13 && strpos($cleaned, '+254') === 0) {
                $formatted = substr($cleaned, 1); // Remove the + prefix
            } else {
                $formatted = $defaultCountryCode . $cleaned;
            }

            $formattedNumbers[] = '+' . $formatted;
        }

        return $formattedNumbers;
    }
}

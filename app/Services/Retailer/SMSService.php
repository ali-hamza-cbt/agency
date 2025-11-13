<?php

namespace App\Services\Retailer;

use Exception;

class SMSService
{
    /**
     * Send SMS to a phone number
     *
     * @param string $phone
     * @param string $message
     * @throws \Exception
     */
    public function send(string $phone, string $message): void
    {
        try {
            // Replace this with your real SMS provider
            // Example: Twilio, Nexmo, etc.
            // \SmsProvider::send($phone, $message);

            // For demo: log the SMS
            \Log::info("SMS sent to {$phone}: {$message}");
        } catch (Exception $e) {
            throw new Exception("Failed to send SMS: " . $e->getMessage());
        }
    }
}

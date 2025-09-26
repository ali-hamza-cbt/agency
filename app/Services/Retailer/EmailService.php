<?php

namespace App\Services\Retailer;

use App\Mail\RetailerCredentialsMail;
use Illuminate\Support\Facades\Mail;
use Exception;

class EmailService
{
    /**
     * Send retailer credentials email
     *
     * @param string $email
     * @param string $name
     * @param string $password
     * @throws \Exception
     */
    public function sendCredentials(string $email, string $name, string $password): void
    {
        try {
            Mail::to($email)->send(new RetailerCredentialsMail(
                $name,
                $email,
                $password
            ));
        } catch (Exception $e) {
            throw new Exception("Failed to send Email: " . $e->getMessage());
        }
    }
}

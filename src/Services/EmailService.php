<?php

declare(strict_types=1);

/** Sends plain-text transactional account e-mails through the hosting mail transport. */
class EmailService
{
    public static function sendTemporaryPassword(string $email, string $fullName, string $username, string $password): bool
    {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $recipientName = $fullName !== '' ? $fullName : $username;
        $subject = 'DemoIT CRM account details';
        $message = "Hello {$recipientName},\n\n"
            . "Your DemoIT CRM account is ready.\n"
            . "Username: {$username}\n"
            . "Temporary password: {$password}\n\n"
            . "You must change the password after signing in.\n";
        $host = preg_replace('/[^a-z0-9.-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: DemoIT CRM <no-reply@' . ($host !== '' ? $host : 'localhost') . '>',
        ];

        return @mail($email, $subject, $message, implode("\r\n", $headers));
    }
}
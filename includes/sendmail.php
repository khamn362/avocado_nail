<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
loadEnv(__DIR__ . '/../.env');

function sendBookingEmail($customerEmail, $customerName, array $appointment = [])
{
    $mail = new PHPMailer(true);

    try {
        // SMTP settings from .env
        $mail->isSMTP();
        $mail->Host       = env('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('GMAIL_USERNAME');
        $mail->Password   = env('GMAIL_APP_PASSWORD');
        $mail->SMTPSecure = env('SMTP_ENCRYPTION', 'tls') === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) env('SMTP_PORT', 587);
        $mail->CharSet    = 'UTF-8';

        // Sender
        $fromEmail = env('GMAIL_USERNAME');
        $fromName  = env('MAIL_FROM_NAME', 'Avocado Nail Studio');
        $mail->setFrom($fromEmail, $fromName);

        // Recipient
        $mail->addAddress($customerEmail, $customerName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Received - Avocado Nail Studio';

        $detailsHtml = '';
        $detailsText = '';
        if (!empty($appointment)) {
            $rows = '';
            $plain = '';
            $fields = [
                'Service'   => $appointment['service_name'] ?? null,
                'Staff'     => $appointment['staff_name'] ?? null,
                'Date'      => !empty($appointment['appointment_date']) ? date('l, F j, Y', strtotime($appointment['appointment_date'])) : null,
                'Time'      => !empty($appointment['appointment_time']) ? date('g:i A', strtotime($appointment['appointment_time'])) : null,
                'Duration'  => !empty($appointment['duration']) ? $appointment['duration'] . ' minutes' : null,
                'Price'     => isset($appointment['price']) ? 'MMK' . number_format((float) $appointment['price'], 0) : null,
            ];
            foreach ($fields as $label => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $rows .= "<tr>
                    <td style='padding:6px 14px;color:#666;font-size:13px;'>{$label}</td>
                    <td style='padding:6px 14px;font-weight:600;font-size:13px;'>{$escaped}</td>
                </tr>";
                $plain .= "{$label}: {$value}\n";
            }
            if ($rows !== '') {
                $detailsHtml = "<table cellpadding='0' cellspacing='0' border='0' style='background:#f4fafe;border-radius:12px;margin:18px auto;padding:8px 0;min-width:280px;'>{$rows}</table>";
                $detailsText = "\n\n" . trim($plain);
            }
        }

        $safeName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px;'>
                <h2 style='color:#3578c0;margin-bottom:4px;'>Booking Received!</h2>
                <p>Hello <b>{$safeName}</b>,</p>
                <p>Thank you for booking with Avocado Nail Studio. We have received your appointment request and it is currently <b>pending confirmation</b>. We will notify you once it is confirmed.</p>
                {$detailsHtml}
                <p style='color:#888;font-size:12px;margin-top:24px;'>If you did not make this booking, please ignore this email.</p>
            </div>
        ";

        $mail->AltBody = "Hello {$customerName},\n\nThank you for booking with Avocado Nail Studio. Your appointment request has been received and is pending confirmation. We will notify you once it is confirmed."
            . $detailsText;

        $mail->send();

        return true;
    } catch (Exception $e) {
        error_log('[sendmail] Email to ' . $customerEmail . ' failed: ' . $mail->ErrorInfo);
        return false;
    }
}

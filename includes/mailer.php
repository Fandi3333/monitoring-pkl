<?php
declare(strict_types=1);

final class MailDeliveryException extends RuntimeException
{
}

function send_password_reset_email(string $recipient, string $otp): void
{
    $host = trim((string) getenv('SMTP_HOST'));
    $port = (int) (getenv('SMTP_PORT') ?: 587);
    $username = (string) getenv('SMTP_USER');
    $password = (string) getenv('SMTP_PASS');
    $sender = trim((string) (getenv('SMTP_FROM_EMAIL') ?: $username));
    $senderName = trim((string) (getenv('SMTP_FROM_NAME') ?: 'Ruang PKL'));
    $encryption = strtolower(trim((string) (getenv('SMTP_ENCRYPTION') ?: 'tls')));

    if ($host === '' || $username === '' || $password === '' || !filter_var($sender, FILTER_VALIDATE_EMAIL)
        || !in_array($encryption, ['tls', 'ssl'], true) || $port < 1 || $port > 65535
        || preg_match('/[\r\n]/', $host . $username . $senderName)) {
        throw new MailDeliveryException('SMTP settings are missing or invalid.');
    }

    $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
    $errorCode = 0;
    $errorMessage = '';
    $socket = @stream_socket_client(
        $transport . $host . ':' . $port,
        $errorCode,
        $errorMessage,
        15,
        STREAM_CLIENT_CONNECT
    );
    if ($socket === false) {
        throw new MailDeliveryException('Could not connect to SMTP server (' . $errorCode . ').');
    }
    stream_set_timeout($socket, 15);

    try {
        smtp_expect($socket, [220]);
        smtp_command($socket, 'EHLO localhost', [250]);
        if ($encryption === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                throw new MailDeliveryException('Could not establish SMTP TLS encryption.');
            }
            smtp_command($socket, 'EHLO localhost', [250]);
        }
        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode($username), [334]);
        smtp_command($socket, base64_encode($password), [235]);
        smtp_command($socket, 'MAIL FROM:<' . $sender . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $subject = '=?UTF-8?B?' . base64_encode('Kode OTP pemulihan akun Ruang PKL') . '?=';
        $fromName = '=?UTF-8?B?' . base64_encode($senderName) . '?=';
        $body = "Kode OTP pemulihan akun Ruang PKL Anda: {$otp}\r\n\r\nKode ini berlaku selama 10 menit dan hanya dapat digunakan sekali. Jangan bagikan kode ini kepada siapa pun.";
        $message = implode("\r\n", [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $fromName . ' <' . $sender . '>',
            'To: <' . $recipient . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $body,
        ]);
        $message = preg_replace('/(?m)^\./', '..', $message);
        smtp_write($socket, $message . "\r\n.\r\n");
        smtp_expect($socket, [250]);
        smtp_command($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}

function smtp_command($socket, string $command, array $expectedCodes): string
{
    smtp_write($socket, $command . "\r\n");
    return smtp_expect($socket, $expectedCodes);
}

function smtp_write($socket, string $data): void
{
    while ($data !== '') {
        $written = fwrite($socket, $data);
        if ($written === false || $written === 0) {
            throw new MailDeliveryException('Could not write to SMTP server.');
        }
        $data = substr($data, $written);
    }
}

function smtp_expect($socket, array $expectedCodes): string
{
    $response = '';
    do {
        $line = fgets($socket, 515);
        if ($line === false) {
            $metadata = stream_get_meta_data($socket);
            throw new MailDeliveryException(!empty($metadata['timed_out'])
                ? 'SMTP server response timed out.'
                : 'SMTP server closed the connection unexpectedly.');
        }
        $response .= $line;
    } while (isset($line[3]) && $line[3] === '-');

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new MailDeliveryException('SMTP server returned response code ' . $code . '.');
    }
    return $response;
}

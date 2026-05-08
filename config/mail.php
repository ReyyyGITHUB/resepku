<?php

function smtpReadResponse($socket): array
{
    $lines = [];

    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }

        $lines[] = rtrim($line, "\r\n");
        if (preg_match('/^\d{3}\s/', $line) === 1) {
            break;
        }
    }

    return $lines;
}

function smtpSendCommand($socket, string $command, array $expectedCodes): array
{
    fwrite($socket, $command . "\r\n");
    $response = smtpReadResponse($socket);
    $line = $response[0] ?? '';
    $code = (int) substr($line, 0, 3);

    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP error: ' . implode(' | ', $response));
    }

    return $response;
}

function sendSmtpMail(string $to, string $subject, string $message): bool
{
    $host = trim((string) env('SMTP_HOST', 'smtp.gmail.com'));
    $port = (int) env('SMTP_PORT', 587);
    $username = trim((string) env('SMTP_USERNAME', ''));
    $password = (string) env('SMTP_PASSWORD', '');
    $encryption = strtolower(trim((string) env('SMTP_ENCRYPTION', 'tls')));
    $fromEmail = trim((string) env('MAIL_FROM_ADDRESS', $username));
    $fromName = trim((string) env('MAIL_FROM_NAME', 'Resepku'));

    if ($host === '' || $port <= 0 || $username === '' || $password === '' || $fromEmail === '') {
        throw new RuntimeException('Konfigurasi SMTP belum lengkap.');
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        throw new RuntimeException('Gagal tersambung ke SMTP: ' . $errstr);
    }

    try {
        stream_set_timeout($socket, 30);
        smtpReadResponse($socket);

        smtpSendCommand($socket, 'EHLO localhost', [250]);

        if ($encryption === 'tls') {
            smtpSendCommand($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Gagal mengaktifkan TLS.');
            }
            smtpSendCommand($socket, 'EHLO localhost', [250]);
        }

        smtpSendCommand($socket, 'AUTH LOGIN', [334]);
        smtpSendCommand($socket, base64_encode($username), [334]);
        smtpSendCommand($socket, base64_encode($password), [235]);

        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'To: <' . $to . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        smtpSendCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        smtpSendCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtpSendCommand($socket, 'DATA', [354]);

        $bodyLines = preg_split("/\r\n|\r|\n/", $message);
        $bodyLines = array_map(static function (string $line): string {
            return str_starts_with($line, '.') ? '.' . $line : $line;
        }, $bodyLines === false ? [] : $bodyLines);

        $payload = implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $bodyLines) . "\r\n.";
        fwrite($socket, $payload . "\r\n");

        $response = smtpReadResponse($socket);
        $line = $response[0] ?? '';
        $code = (int) substr($line, 0, 3);
        if ($code !== 250) {
            throw new RuntimeException('SMTP data error: ' . implode(' | ', $response));
        }

        smtpSendCommand($socket, 'QUIT', [221]);
        fclose($socket);

        return true;
    } catch (Throwable $throwable) {
        fclose($socket);
        throw $throwable;
    }
}

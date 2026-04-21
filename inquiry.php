<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

function val(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
}

function clean_line(string $value): string {
    return str_replace(["\r", "\n"], ' ', trim($value));
}

function smtp_read_response($socket): string {
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function smtp_expect($socket, array $allowedCodes): void {
    $response = smtp_read_response($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $allowedCodes, true)) {
        throw new RuntimeException('SMTP error: ' . trim($response));
    }
}

function smtp_write($socket, string $command): void {
    fwrite($socket, $command . "\r\n");
}

function smtp_send_mail(
    string $host,
    int $port,
    string $username,
    string $password,
    string $fromEmail,
    string $fromName,
    string $toEmail,
    string $replyTo,
    string $subject,
    string $body
): bool {
    $socket = @stream_socket_client(
        "ssl://{$host}:{$port}",
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        throw new RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
    }

    stream_set_timeout($socket, 20);

    try {
        smtp_expect($socket, [220]);
        smtp_write($socket, 'EHLO discoverparbat.com');
        smtp_expect($socket, [250]);

        smtp_write($socket, 'AUTH LOGIN');
        smtp_expect($socket, [334]);
        smtp_write($socket, base64_encode($username));
        smtp_expect($socket, [334]);
        smtp_write($socket, base64_encode($password));
        smtp_expect($socket, [235]);

        smtp_write($socket, "MAIL FROM:<{$fromEmail}>");
        smtp_expect($socket, [250]);
        smtp_write($socket, "RCPT TO:<{$toEmail}>");
        smtp_expect($socket, [250, 251]);
        smtp_write($socket, 'DATA');
        smtp_expect($socket, [354]);

        $safeSubject = preg_replace('/[\r\n]+/', ' ', $subject);
        $safeReplyTo = preg_replace('/[\r\n]+/', ' ', $replyTo);
        $safeBody = str_replace("\n.", "\n..", str_replace("\r\n", "\n", $body));

        $headers = [];
        $headers[] = "From: {$fromName} <{$fromEmail}>";
        $headers[] = "Reply-To: {$safeReplyTo}";
        $headers[] = "To: <{$toEmail}>";
        $headers[] = "Subject: {$safeSubject}";
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = '';
        $headers[] = $safeBody;

        fwrite($socket, implode("\r\n", $headers) . "\r\n.\r\n");
        smtp_expect($socket, [250]);

        smtp_write($socket, 'QUIT');
        smtp_expect($socket, [221]);
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        fclose($socket);
        throw $e;
    }
}

$toEmail = 'info@discoverparbat.com';
$type = val('type');

if ($type === 'booking') {
    $name = clean_line(val('name'));
    $country = clean_line(val('country'));
    $email = clean_line(val('email'));
    $whatsapp = clean_line(val('whatsapp'));
    $trek = clean_line(val('trek'));
    $startDate = clean_line(val('startDate'));
    $pax = clean_line(val('pax'));
    $special = val('special');

    if ($name === '' || $country === '' || $trek === '' || $startDate === '' || $pax === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Missing required booking fields']);
        exit;
    }
    if ($email === '' && $whatsapp === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Email or WhatsApp is required']);
        exit;
    }

    $subject = "Booking Request - {$trek}";
    $message = "Booking Inquiry\n\n"
        . "Name: {$name}\n"
        . "Country: {$country}\n"
        . "Trek: {$trek}\n"
        . "Start Date: {$startDate}\n"
        . "No. of Pax: {$pax}\n"
        . "Email: " . ($email !== '' ? $email : 'Not provided') . "\n"
        . "WhatsApp: " . ($whatsapp !== '' ? $whatsapp : 'Not provided') . "\n\n"
        . "Special Requirement:\n" . ($special !== '' ? $special : 'None') . "\n";

    $replyTo = $email !== '' ? $email : $toEmail;
} elseif ($type === 'custom') {
    $name = clean_line(val('name'));
    $country = clean_line(val('country'));
    $email = clean_line(val('email'));
    $whatsapp = clean_line(val('whatsapp'));
    $days = clean_line(val('days'));
    $startDate = clean_line(val('startDate'));
    $trek = clean_line(val('trek'));
    $pax = clean_line(val('pax'));
    $focus = clean_line(val('focus'));
    $special = val('special');

    if ($name === '' || $country === '' || $days === '' || $startDate === '' || $pax === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Missing required custom trek fields']);
        exit;
    }
    if ($email === '' && $whatsapp === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Email or WhatsApp is required']);
        exit;
    }

    $subject = "Custom Trek Request - {$name}";
    $message = "Custom Trek Inquiry\n\n"
        . "Name: {$name}\n"
        . "Country: {$country}\n"
        . "Preferred Trek: " . ($trek !== '' ? $trek : 'Not specified') . "\n"
        . "Preferred Duration: {$days} days\n"
        . "Preferred Start Date: {$startDate}\n"
        . "No. of Pax: {$pax}\n"
        . "Focus: " . ($focus !== '' ? $focus : 'Not specified') . "\n"
        . "Email: " . ($email !== '' ? $email : 'Not provided') . "\n"
        . "WhatsApp: " . ($whatsapp !== '' ? $whatsapp : 'Not provided') . "\n\n"
        . "Special Requirement:\n" . ($special !== '' ? $special : 'None') . "\n";

    $replyTo = $email !== '' ? $email : $toEmail;
} elseif ($type === 'shop') {
    $name = clean_line(val('name'));
    $email = clean_line(val('email'));
    $whatsapp = clean_line(val('whatsapp'));
    $country = clean_line(val('country'));
    $itemCode = clean_line(val('itemCode'));
    $format = clean_line(val('format'));
    $qty = clean_line(val('qty'));
    $special = val('special');

    if ($name === '' || $country === '' || $itemCode === '' || $format === '' || $qty === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Missing required shop order fields']);
        exit;
    }
    if ($email === '' && $whatsapp === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Email or WhatsApp is required']);
        exit;
    }

    $subject = "Shop Order Request - {$itemCode} ({$format})";
    $message = "Shop Order Inquiry\n\n"
        . "Name: {$name}\n"
        . "Country: {$country}\n"
        . "Photo Code: {$itemCode}\n"
        . "Format: {$format}\n"
        . "Quantity: {$qty}\n"
        . "Email: " . ($email !== '' ? $email : 'Not provided') . "\n"
        . "WhatsApp: " . ($whatsapp !== '' ? $whatsapp : 'Not provided') . "\n\n"
        . "Interest / Note:\n" . ($special !== '' ? $special : 'None') . "\n";

    $replyTo = $email !== '' ? $email : $toEmail;
} elseif ($type === 'contact') {
    $fname = clean_line(val('fname'));
    $lname = clean_line(val('lname'));
    $email = clean_line(val('email'));
    $trek = clean_line(val('trek'));
    $country = clean_line(val('country'));
    $group = clean_line(val('group'));
    $date = clean_line(val('date'));
    $messageInput = val('message');

    if ($fname === '' || $email === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Name and email are required']);
        exit;
    }

    $fullName = trim("{$fname} {$lname}");
    $subject = "Contact Enquiry - {$fullName}" . ($trek !== '' ? " - {$trek}" : '');
    $message = "Contact Inquiry\n\n"
        . "Name: {$fullName}\n"
        . "Email: {$email}\n"
        . "Country: " . ($country !== '' ? $country : 'Not provided') . "\n"
        . "Trek: " . ($trek !== '' ? $trek : 'Not specified') . "\n"
        . "Group Size: " . ($group !== '' ? $group : 'Not specified') . "\n"
        . "Preferred Start Date: " . ($date !== '' ? $date : 'Not specified') . "\n\n"
        . "Message:\n" . ($messageInput !== '' ? $messageInput : 'No additional message') . "\n";

    $replyTo = $email;
} else {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid inquiry type']);
    exit;
}

try {
    $smtpHost = 'smtp.hostinger.com';
    $smtpPort = 465;
    $smtpUser = 'info@discoverparbat.com';
    $smtpPass = getenv('DISCOVERPARBAT_SMTP_PASSWORD') ?: '';
    if ($smtpPass === '') {
        throw new RuntimeException('Missing SMTP password configuration');
    }

    smtp_send_mail(
        $smtpHost,
        $smtpPort,
        $smtpUser,
        $smtpPass,
        'info@discoverparbat.com',
        'Discover Parbat',
        $toEmail,
        $replyTo,
        $subject,
        $message
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail send failed']);
    exit;
}

echo json_encode(['ok' => true]);


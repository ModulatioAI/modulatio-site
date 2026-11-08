<?php
// A bug report from the form on modulatio.ai/caesar, mailed to contact@modulatio.ai with the
// subject ending "[Bug Report]" and the reporter's address as Reply-To. Caesar's bug-mail service
// reads that mail, gives it a number and answers the reporter; nothing else is kept here.
declare(strict_types=1);

const TO = 'contact@modulatio.ai';
const PER_HOUR = 5;
// Files a report may carry: screenshots, logs and text, and archives of them. Nothing that runs.
const MAX_FILES = 3;
const MAX_FILE = 10 * 1024 * 1024;
const MAX_TOTAL = 20 * 1024 * 1024;
const KINDS = [
    'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp',
    'gif' => 'image/gif', 'txt' => 'text/plain', 'log' => 'text/plain', 'json' => 'application/json',
    'conf' => 'text/plain', 'md' => 'text/plain', 'zip' => 'application/zip',
    'gz' => 'application/gzip', 'tgz' => 'application/gzip', 'xz' => 'application/x-xz',
    'zst' => 'application/zstd', 'pdf' => 'application/pdf',
];

// The page's script says so in a header too: a post larger than the server takes arrives with
// every field dropped, and the answer must still be one the page can read.
$fromScript = ($_POST['js'] ?? '') === '1' || ($_SERVER['HTTP_X_CAESAR_FORM'] ?? '') === '1';

function answer(int $code, string $said): never
{
    global $fromScript;
    http_response_code($code);
    header('Cache-Control: no-store');
    if ($fromScript) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $code === 200, 'message' => $said]);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>Report a bug</title><p>',
            htmlspecialchars($said), '</p><p><a href="/caesar/#report-a-bug">Back to Caesar</a></p>';
    }
    exit;
}

function field(string $name, int $max): string
{
    return trim(mb_substr((string) ($_POST[$name] ?? ''), 0, $max));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    answer(405, 'Use the form on the Caesar page to report a bug.');
}
// A post larger than post_max_size reaches the script with no fields at all; without this it would
// be answered as though the email address had been left out.
if (!$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    answer(413, 'The report and its files are too large to send. Please send smaller files, or put them in a zip.');
}
// A field people never see: a robot that fills in every field fills this one too, and is told
// it succeeded so it does not try again another way.
if (field('website', 200) !== '') {
    answer(200, 'Thank you. Your report was sent.');
}

$email = field('email', 200);
$title = preg_replace('/[\r\n\t]+/', ' ', field('title', 120));
$version = field('version', 80);
$happened = field('happened', 6000);
$expected = field('expected', 3000);
$steps = field('steps', 6000);
$computer = field('computer', 400);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    answer(400, 'Please give an email address we can answer.');
}
if ($title === '' || $happened === '') {
    answer(400, 'Please say in a few words what went wrong, and what happened.');
}
// A Caesar may send more than the bare number, such as "0.8.9 (caesar-code-20261108_2)" or
// "Caesar 0.8.9": the version is the first number of that shape in what was sent.
$version = preg_match('/(?<![0-9.])([0-9]{1,3}\.[0-9]{1,3}(?:\.[0-9]{1,4})?[A-Za-z]?)(?![0-9A-Za-z])/', $version, $found)
    ? $found[1] : 'unknown version';

// At most a few reports an hour from one address on the internet, so the form cannot be used to
// send our replies to someone again and again.
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$seen = sys_get_temp_dir() . '/caesar-bug-' . sha1($ip);
$now = time();
$times = array_values(array_filter(
    (array) json_decode((string) @file_get_contents($seen), true),
    fn($t) => is_int($t) && $now - $t < 3600
));
if (count($times) >= PER_HOUR) {
    answer(429, 'Several reports have just come from here. Please wait a while and send it again.');
}

// The files sent with it, each checked for size and kind before anything is mailed.
$files = [];
$total = 0;
$given = $_FILES['files'] ?? null;
if (is_array($given) && is_array($given['name'] ?? null)) {
    foreach ($given['name'] as $i => $name) {
        $error = $given['error'][$i];
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || $given['size'][$i] > MAX_FILE) {
            answer(413, 'A file is larger than 10 MB. Please send a smaller one, or put it in a zip.');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($given['tmp_name'][$i])) {
            answer(400, 'A file did not arrive whole. Please try again.');
        }
        $clean = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename((string) $name));
        $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));
        if (!isset(KINDS[$ext])) {
            answer(415, "Files of the kind .$ext cannot be sent. Screenshots, logs, text and zip or tar archives can.");
        }
        $sniffed = (new finfo(FILEINFO_MIME_TYPE))->file($given['tmp_name'][$i]);
        if (preg_match('#^application/(x-(executable|sharedlib|elf|mach-binary|msdownload|dosexec)|vnd\.microsoft\.portable-executable)#', (string) $sniffed)) {
            answer(415, 'Programs cannot be sent with a report.');
        }
        $total += $given['size'][$i];
        $files[] = ['name' => substr($clean, -100), 'type' => KINDS[$ext], 'path' => $given['tmp_name'][$i]];
    }
}
if (count($files) > MAX_FILES) {
    answer(413, 'Please send at most 3 files with a report.');
}
if ($total > MAX_TOTAL) {
    answer(413, 'The files come to more than 20 MB together. Please send fewer, or put them in a zip.');
}

$subject = "Caesar $version: $title [Bug Report]";
$body = "Sent with the form on modulatio.ai/caesar.\n\n"
    . "Reported by: $email\n"
    . "Caesar's version: $version\n"
    . "The computer: " . ($computer !== '' ? $computer : '(not given)') . "\n\n"
    . "What happened:\n$happened\n\n"
    . "What was expected:\n" . ($expected !== '' ? $expected : '(not given)') . "\n\n"
    . "Steps that make it happen again:\n" . ($steps !== '' ? $steps : '(not given)') . "\n"
    . ($files ? "\nFiles sent with it: " . implode(', ', array_column($files, 'name')) . "\n" : '');
$headers = [
    'From' => 'Caesar bug form <' . TO . '>',
    'Reply-To' => $email,
    'MIME-Version' => '1.0',
];
if ($files) {
    $edge = 'caesar-' . bin2hex(random_bytes(12));
    $headers['Content-Type'] = "multipart/mixed; boundary=\"$edge\"";
    $mail = "--$edge\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
        . $body . "\r\n";
    foreach ($files as $f) {
        $mail .= "--$edge\r\nContent-Type: {$f['type']}; name=\"{$f['name']}\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: attachment; filename=\"{$f['name']}\"\r\n\r\n"
            . chunk_split(base64_encode((string) file_get_contents($f['path']))) . "\r\n";
    }
    $mail .= "--$edge--\r\n";
} else {
    $headers['Content-Type'] = 'text/plain; charset=UTF-8';
    $headers['Content-Transfer-Encoding'] = '8bit';
    $mail = $body;
}
$sent = mail(TO, mb_encode_mimeheader($subject, 'UTF-8', 'Q'), $mail, $headers, '-f' . TO);
if (!$sent) {
    answer(502, 'The report could not be sent just now. Please try again in a few minutes.');
}
$times[] = $now;
@file_put_contents($seen, json_encode($times), LOCK_EX);
// A title carrying a report's number adds to that report: it is filed under the number and is
// not thanked again, so it is not promised a reply.
if (preg_match('/\[(CB-\d{4,})\]/i', $title, $named)) {
    answer(200, "Thank you. What you sent was added to report " . strtoupper($named[1]) . '.');
}
answer(200, "Thank you. Your report was sent. A reply with its number is on its way to $email.");

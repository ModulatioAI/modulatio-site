<?php
// A bug report from the form on modulatio.ai/caesar, mailed to contact@modulatio.ai with the
// subject ending "[Bug Report]" and the reporter's address as Reply-To. Caesar's bug-mail service
// reads that mail, gives it a number and answers the reporter; nothing else is kept here.
declare(strict_types=1);

const TO = 'contact@modulatio.ai';
const PER_HOUR = 5;

$fromScript = ($_POST['js'] ?? '') === '1';

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
// A field people never see: a robot that fills in every field fills this one too, and is told
// it succeeded so it does not try again another way.
if (field('website', 200) !== '') {
    answer(200, 'Thank you. Your report was sent.');
}

$email = field('email', 200);
$title = preg_replace('/[\r\n\t]+/', ' ', field('title', 120));
$version = field('version', 12);
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
if (!preg_match('/^[0-9][0-9A-Za-z.]{0,11}$/', $version)) {
    $version = 'unknown version';
}

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

$subject = "Caesar $version: $title [Bug Report]";
$body = "Sent with the form on modulatio.ai/caesar.\n\n"
    . "Reported by: $email\n"
    . "Caesar's version: $version\n"
    . "The computer: " . ($computer !== '' ? $computer : '(not given)') . "\n\n"
    . "What happened:\n$happened\n\n"
    . "What was expected:\n" . ($expected !== '' ? $expected : '(not given)') . "\n\n"
    . "Steps that make it happen again:\n" . ($steps !== '' ? $steps : '(not given)') . "\n";
$headers = [
    'From' => 'Caesar bug form <' . TO . '>',
    'Reply-To' => $email,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => '8bit',
];
$sent = mail(TO, mb_encode_mimeheader($subject, 'UTF-8', 'Q'), $body, $headers, '-f' . TO);
if (!$sent) {
    answer(502, 'The report could not be sent just now. Please try again in a few minutes.');
}
$times[] = $now;
@file_put_contents($seen, json_encode($times), LOCK_EX);
answer(200, "Thank you. Your report was sent. A reply with its number is on its way to $email.");

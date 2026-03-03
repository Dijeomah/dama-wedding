<?php
/**
 * RSVP Form Handler — Rotshak & Damaris Wedding
 * Uses Brevo (formerly Sendinblue) HTTP API — sends over HTTPS port 443,
 * so it works even when SMTP ports 587/465 are blocked by the host.
 *
 * Setup (takes 2 minutes):
 *  1. Create a free account at https://app.brevo.com
 *  2. Go to Settings → API Keys → Generate a new key
 *  3. Go to Settings → Senders & IP → Add a sender address and verify it
 *  4. Paste your API key and verified sender email below
 */

header('Content-Type: application/json; charset=UTF-8');

// ============================================================
//  Configuration  ← fill these in
// ============================================================
const BREVO_API_KEY   = 'YOUR_BREVO_API_KEY';          // ← from Brevo dashboard
const MAIL_FROM_EMAIL = 'noreply@rotshakwedsdamaris.live'; // ← must be verified in Brevo
const MAIL_FROM_NAME  = 'Rotshak & Damaris Wedding';
const MAIL_TO_EMAIL   = 'd.ije95@gmail.com';
const MAIL_TO_NAME    = 'Wedding Coordinator';
// ============================================================

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Sanitise input
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

$firstName = clean($_POST['first_name'] ?? '');
$lastName  = clean($_POST['last_name']  ?? '');
$phone     = clean($_POST['phone']      ?? '');
$attending = clean($_POST['attending']  ?? '');
$guests    = clean($_POST['guests']     ?? '');
$message   = clean($_POST['message']    ?? '');

// Validate required fields
if (!$firstName || !$lastName || !$phone || !$attending) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Required fields are missing.']);
    exit;
}

$attendingLabel = ($attending === 'yes') ? '✅ Joyfully Accepts' : '❌ Regretfully Declines';

// Build the Brevo API payload
$payload = [
    'sender'      => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM_EMAIL],
    'to'          => [['email' => MAIL_TO_EMAIL, 'name' => MAIL_TO_NAME]],
    'subject'     => "Wedding RSVP — {$firstName} {$lastName}",
    'htmlContent' => buildHtml($firstName, $lastName, $phone, $attendingLabel, $guests, $message),
    'textContent' => buildText($firstName, $lastName, $phone, $attendingLabel, $guests, $message),
];

// Send via Brevo API (HTTPS — port 443, never blocked)
$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => [
        'accept: application/json',
        'content-type: application/json',
        'api-key: ' . BREVO_API_KEY,
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Network error: ' . $curlErr]);
    exit;
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    $body = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $body['message'] ?? "Brevo error (HTTP {$httpCode})",
    ]);
}


// ============================================================
//  Email templates
// ============================================================
function buildHtml(string $fn, string $ln, string $phone, string $attending,
                   string $guests, string $message): string
{
    $msgHtml = $message
        ? nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'))
        : '<em style="color:#aaa">No message provided.</em>';

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f8f4ec;font-family:Georgia,serif;color:#2a2a2a">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f4ec;padding:40px 16px">
    <tr><td align="center">
      <table width="580" cellpadding="0" cellspacing="0"
             style="background:#ffffff;border-radius:18px;overflow:hidden;
                    box-shadow:0 6px 40px rgba(0,0,0,0.08);max-width:100%">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#2e3d12 0%,#4a5e28 50%,#6a7e40 100%);
                     padding:48px 40px;text-align:center">
            <p style="margin:0 0 6px;font-size:11px;letter-spacing:4px;text-transform:uppercase;
                      color:rgba(255,255,255,0.55)">New RSVP Received</p>
            <h1 style="margin:0;font-size:32px;font-weight:400;color:#ffffff;letter-spacing:1px">
              Rotshak &amp; Damaris
            </h1>
            <p style="margin:10px 0 0;font-size:12px;letter-spacing:3px;
                      color:rgba(255,255,255,0.5)">April 3, 2026 &nbsp;·&nbsp; Excel Hotel Resort</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px 40px 32px">
            <p style="margin:0 0 28px;font-size:14px;line-height:1.7;color:#666">
              A guest has submitted their RSVP for the wedding of
              <strong style="color:#2a2a2a">Rotshak Micah &amp; Damaris Chika</strong>.
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #f0ead8">
              <tr>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;width:38%;
                           font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b5975a">
                  Guest Name</td>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:15px;color:#2a2a2a"><strong>{$fn} {$ln}</strong></td>
              </tr>
              <tr>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b5975a">
                  Phone</td>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:15px;color:#2a2a2a">{$phone}</td>
              </tr>
              <tr>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b5975a">
                  Attendance</td>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:15px;color:#2a2a2a">{$attending}</td>
              </tr>
              <tr>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b5975a">
                  No. of Guests</td>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:15px;color:#2a2a2a">{$guests}</td>
              </tr>
            </table>

            <!-- Message -->
            <div style="margin-top:28px;padding:22px 24px;background:#f8f4ec;
                        border-left:3px solid #b5975a;border-radius:0 10px 10px 0">
              <p style="margin:0 0 10px;font-size:11px;letter-spacing:2px;
                        text-transform:uppercase;color:#b5975a">Message to the Couple</p>
              <p style="margin:0;font-size:14px;color:#555;line-height:1.8">{$msgHtml}</p>
            </div>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f8f4ec;padding:22px 40px;text-align:center;
                     border-top:1px solid #f0ead8">
            <p style="margin:0;font-size:11px;color:#bbb;letter-spacing:1px">
              Rotshak &amp; Damaris Wedding &nbsp;·&nbsp; April 3, 2026
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function buildText(string $fn, string $ln, string $phone, string $attending,
                   string $guests, string $message): string
{
    $msg = $message ?: '(no message)';
    return implode("\n", [
        'Wedding RSVP — Rotshak & Damaris',
        str_repeat('-', 40),
        "Name:        {$fn} {$ln}",
        "Phone:       {$phone}",
        "Attendance:  {$attending}",
        "Guests:      {$guests}",
        '',
        'Message:',
        $msg,
        '',
        str_repeat('-', 40),
        'April 3, 2026 · Excel Hotel Resort, Bukuru',
    ]);
}

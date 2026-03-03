<?php
/**
 * RSVP Form Handler — Rotshak & Damaris Wedding
 *
 * Requires PHPMailer. Install via: composer install
 *
 * Gmail App Password setup:
 *  1. Enable 2-Step Verification on the sending Gmail account
 *  2. Visit https://myaccount.google.com/apppasswords
 *  3. Generate a password for "Mail" and paste it into SMTP_PASSWORD below
 */

header('Content-Type: application/json; charset=UTF-8');

// ============================================================
//  SMTP Configuration  ← fill in your credentials here
// ============================================================
const SMTP_HOST = 'smtp.dreamhost.com';
const SMTP_PORT = 587;                      // 587 = TLS  |  465 = SSL
const SMTP_USERNAME = 's.drame@callphoneng.com';   // Gmail address you send FROM
const SMTP_PASSWORD = 'Ijeoma#95';      // App Password (not your login password)
const MAIL_FROM = 's.drame@callphoneng.com';   // Same as SMTP_USERNAME
const MAIL_FROM_NAME = 'Rotshak & Damaris Wedding';
const MAIL_TO = 'd.ije95@gmail.com';
const MAIL_TO_NAME = 'Wedding Coordinator';
// ============================================================

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Sanitise all input
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

// Load PHPMailer (installed via Composer)
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mailer not installed. Run: composer install']);
    exit;
}
require_once $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // ── SMTP settings ─────────────────────────────────────
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // ── Recipients ────────────────────────────────────────
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress(MAIL_TO, MAIL_TO_NAME);

    // ── Content ───────────────────────────────────────────
    $attendingLabel = ($attending === 'yes') ? 'Joyfully Accepts' : 'Regretfully Declines';
    $mail->isHTML(true);
    $mail->Subject = "Wedding RSVP — {$firstName} {$lastName}";
    $mail->Body    = buildHtml($firstName, $lastName, $phone, $attendingLabel, $guests, $message);
    $mail->AltBody = buildText($firstName, $lastName, $phone, $attendingLabel, $guests, $message);

    $mail->send();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
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
              Details are listed below.
            </p>

            <!-- Detail rows -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="border-top:1px solid #f0ead8">
              <tr>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;width:38%;
                           font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b5975a">
                  Guest Name
                </td>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:15px;color:#2a2a2a">
                  <strong>{$fn} {$ln}</strong>
                </td>
              </tr>
              <tr>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b5975a">
                  Phone
                </td>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:15px;color:#2a2a2a">{$phone}</td>
              </tr>
              <tr>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b5975a">
                  Attendance
                </td>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:15px;color:#2a2a2a">{$attending}</td>
              </tr>
              <tr>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b5975a">
                  No. of Guests
                </td>
                <td style="padding:15px 0;border-bottom:1px solid #f0ead8;
                           font-size:15px;color:#2a2a2a">{$guests}</td>
              </tr>
            </table>

            <!-- Message block -->
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

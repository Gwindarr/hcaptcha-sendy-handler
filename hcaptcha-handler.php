<?php
/**
 * Universal hCaptcha Form Handler for Static Pages
 * 
 * A secure PHP form handler that processes newsletter signups with hCaptcha
 * verification and integrates directly with Sendy email marketing platform.
 * Designed for static HTML pages without CMS dependencies.
 * 
 * Features:
 * - hCaptcha bot protection
 * - Honeypot anti-spam measures
 * - IP detection through various proxy configurations
 * - Safe redirect validation
 * - Direct Sendy API integration
 * 
 * @author Brett Whiteside
 * @copyright 2025 Brett Whiteside
 * @license MIT
 * @version 1.0.0
 * @created August 2025
 * 
 * Repository: https://github.com/Gwindarr/hcaptcha-sendy-handler
 * 
 * Requirements:
 * - PHP with cURL extension
 * - hCaptcha account and site keys
 * - Sendy installation with API access
 * - Configuration file with API keys (see README.md)
 */

declare(strict_types=1);

// Load secrets/constants: HCAPTCHA_SECRET, SENDY_API_KEY, SENDY_LIST_ID
require_once('/home/ltfawg/ltfawg/private/hcaptcha-config.php');

// Require POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Basic output defaults
$defaultRedirect = '/are-you-a-bot/';

// Honeypots
if (!empty($_POST['website'] ?? '') || !empty($_POST['hp'] ?? '')) {
    http_response_code(400);
    exit('Bad request');
}

// Inputs
$email   = trim((string)($_POST['email'] ?? ''));
$fname   = trim((string)($_POST['fname'] ?? ''));
$lname   = trim((string)($_POST['lname'] ?? ''));
$name    = trim($fname . ' ' . $lname);
$redirect = (string)($_POST['redirect'] ?? $defaultRedirect);

// Validate email
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('Invalid email address. <a href="javascript:history.back()">Go back</a>');
}

// Validate hCaptcha presence
$hCaptchaResponse = trim((string)($_POST['h-captcha-response'] ?? ''));
if ($hCaptchaResponse === '') {
    exit('Please complete the captcha. <a href="javascript:history.back()">Go back</a>');
}

// Get the real visitor's IP
$visitor_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? // Cloudflare
              $_SERVER['HTTP_X_FORWARDED_FOR'] ?? // Other proxies
              $_SERVER['HTTP_X_REAL_IP'] ?? // Nginx
              $_SERVER['REMOTE_ADDR']; // Direct connection

// Get the referrer
$referrer = $_SERVER['HTTP_REFERER'] ?? '';

// Sendy API call 
$payload = [
    'email'   => $email,
    'name'    => $name,
    'list'    => SENDY_LIST_ID,   // do not trust POST list
    'subform' => 'yes',
    'h-captcha-response' => $hCaptchaResponse,
    'ipaddress' => $visitor_ip,
    'referrer' => $referrer,
];

$sendyEndpoint = 'https://learnthaifromawhiteguy.com/sendy/subscribe';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $sendyEndpoint,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: LTFAGW-Subscribe-Handler',
        'Accept: text/plain,*/*;q=0.8',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
    ],
]);
$sendyResponse = curl_exec($ch);
if (curl_error($ch)) {
    curl_close($ch);
    exit('Network error during subscription. <a href="javascript:history.back()">Go back</a>');
}
curl_close($ch);

// Normalize response
$resp = trim((string)$sendyResponse);
$already = (stripos($resp, 'already subscribed') !== false);

// Safe redirect (only allow relative paths you expect)
$allowedRedirects = [
    '/are-you-a-bot/',
    '/thanks/',
];
if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = $defaultRedirect;
}

if ($resp === 'true' || $resp === '1') {
    header('Location: ' . $redirect, true, 303);
    exit;
}
if ($already) {
    header('Location: ' . $redirect . '?status=already-subscribed', true, 303);
    exit;
}

// If Sendy returned HTML, surface it; otherwise show plain error
if (stripos($resp, '<html') !== false) {
    header('Content-Type: text/html; charset=utf-8');
    echo $resp;
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
exit('Subscription failed: ' . htmlspecialchars($resp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

<?php

/**
 * GitHub Webhook Endpoint for auto-deployment.
 *
 * Place this file at /var/www/crm/public/deploy-webhook.php
 * (it's already in public/ via git)
 *
 * Setup:
 * 1. On the server: chmod +x /var/www/crm/deploy/update.sh
 * 2. On the server: echo "DEPLOY_SECRET=your-secret-here" >> /var/www/crm/.env
 * 3. On GitHub: Settings → Webhooks → Add webhook
 *    - Payload URL: http://YOUR_SERVER_IP/deploy-webhook.php
 *    - Content type: application/json
 *    - Secret: same as DEPLOY_SECRET
 *    - Events: Just the push event
 */

// Load .env manually (minimal, no framework boot)
$envFile = __DIR__ . '/../.env';
$secret = null;
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with($line, 'DEPLOY_SECRET=')) {
            $secret = trim(substr($line, strlen('DEPLOY_SECRET=')));
            break;
        }
    }
}

if (!$secret) {
    http_response_code(500);
    die('DEPLOY_SECRET not configured');
}

// Verify GitHub signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!$signature) {
    http_response_code(403);
    die('No signature');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Invalid signature');
}

// Only deploy on push to main
$data = json_decode($payload, true);
$ref = $data['ref'] ?? '';
if ($ref !== 'refs/heads/main') {
    echo 'Ignored: not main branch';
    exit(0);
}

// Trigger deploy in background (don't block the webhook response)
$script = __DIR__ . '/../deploy/update.sh';
exec("nohup bash {$script} > /dev/null 2>&1 &");

echo 'Deploy triggered ✅';

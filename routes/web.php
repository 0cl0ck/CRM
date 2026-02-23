<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/deploy-webhook', function (Request $request) {
    $secret = config('app.deploy_secret');

    if (!$secret) {
        return response('DEPLOY_SECRET not configured', 500);
    }

    // Verify GitHub HMAC signature
    $signature = $request->header('X-Hub-Signature-256', '');
    $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

    if (!hash_equals($expected, $signature)) {
        return response('Invalid signature', 403);
    }

    // Only deploy on push to main
    $ref = $request->input('ref', '');
    if ($ref !== 'refs/heads/main') {
        return response('Ignored: not main branch', 200);
    }

    // Trigger deploy in background
    $script = base_path('deploy/update.sh');
    exec("nohup bash {$script} > /dev/null 2>&1 &");

    return response('Deploy triggered ✅', 200);
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

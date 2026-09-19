#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Prime an authenticated browser session with quiz_celebration payload.
 *
 * Usage:
 *   ./vendor/bin/sail php scripts/prime_quiz_celebration_session.php \
 *     <encrypted_session_cookie> <material_id> [xp] [percentage] [streak]
 */

use App\Models\Badge;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cookieValue = $argv[1] ?? '';
$materialId = (int) ($argv[2] ?? 0);
$xp = (int) ($argv[3] ?? 150);
$percentage = (int) ($argv[4] ?? 100);
$streak = (int) ($argv[5] ?? 14);
$cookieName = (string) config('session.cookie');

if ($cookieValue === '' || $materialId <= 0) {
    fwrite(STDERR, "Usage: prime_quiz_celebration_session.php <session_cookie> <material_id> [xp] [percentage] [streak]\n");
    exit(1);
}

$cookieValue = urldecode($cookieValue);

try {
    $decrypted = Crypt::decryptString($cookieValue);
} catch (DecryptException $exception) {
    // Already a raw session id
    $decrypted = $cookieValue;
}

$sessionId = CookieValuePrefix::remove($decrypted);
if (! is_string($sessionId) || $sessionId === '') {
    $sessionId = $decrypted;
}

// CookieValuePrefix::remove returns value after prefix; validate when possible
try {
    $validated = CookieValuePrefix::validate($cookieName, $decrypted, Crypt::getKey());
    if (is_string($validated) && $validated !== '') {
        $sessionId = $validated;
    }
} catch (Throwable) {
    // keep $sessionId from remove()/raw
}

$row = DB::table('sessions')->where('id', $sessionId)->first();
if ($row === null) {
    fwrite(STDERR, "Session not found after decrypt. id=".substr($sessionId, 0, 40)."...\n");
    exit(2);
}

$rawPayload = base64_decode((string) $row->payload, true);
if ($rawPayload === false) {
    fwrite(STDERR, "Could not base64-decode session payload\n");
    exit(3);
}

/** @var array<string, mixed>|null $payload */
$payload = json_decode($rawPayload, true);
if (! is_array($payload)) {
    // Legacy PHP serialize sessions (pre JSON serialization config)
    $legacy = @unserialize($rawPayload);
    $payload = is_array($legacy) ? $legacy : null;
}
if (! is_array($payload)) {
    fwrite(STDERR, "Could not decode session payload\n");
    exit(3);
}

$badgeIds = Badge::query()
    ->orderBy('id')
    ->limit(2)
    ->pluck('id')
    ->map(static fn (mixed $id): int => (int) $id)
    ->all();

$payload['quiz_celebration'] = [
    'learning_material_id' => $materialId,
    'xp_earned' => $xp,
    'percentage' => $percentage,
    'streak_days' => $streak,
    'badge_ids' => $badgeIds,
];

$encoded = config('session.serialization') === 'php'
    ? base64_encode(serialize($payload))
    : base64_encode((string) json_encode($payload, JSON_THROW_ON_ERROR));

DB::table('sessions')->where('id', $sessionId)->update([
    'payload' => $encoded,
    'last_activity' => time(),
]);

echo json_encode([
    'ok' => true,
    'session_id' => $sessionId,
    'material_id' => $materialId,
    'quiz_celebration' => $payload['quiz_celebration'],
], JSON_UNESCAPED_UNICODE)."\n";

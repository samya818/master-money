<?php
/**
 * JWT encode/decode (HS256) pour Master Money.
 * Clé secrète : JWT_SECRET (variable d'environnement).
 */

function jwt_encode(array $payload): string {
    $secret = getenv('JWT_SECRET') ?: 'master-money-default-secret-change-in-production';
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $payload['iat'] = $payload['iat'] ?? time();
    $payload['exp'] = $payload['exp'] ?? (time() + 60 * 60 * 24 * 7); // 7 jours par défaut
    $h = base64url_encode(json_encode($header));
    $p = base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$h.$p", $secret, true);
    $sig = base64url_encode($signature);
    return "$h.$p.$sig";
}

function jwt_decode(string $token): ?array {
    $secret = getenv('JWT_SECRET') ?: 'master-money-default-secret-change-in-production';
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$h, $p, $sig] = $parts;
    $signature = hash_hmac('sha256', "$h.$p", $secret, true);
    if (!hash_equals(base64url_encode($signature), $sig)) return null;
    $payload = json_decode(base64url_decode($p), true);
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) return null;
    return $payload;
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    $pad = strlen($data) % 4;
    if ($pad) $data .= str_repeat('=', 4 - $pad);
    return base64_decode(strtr($data, '-_', '+/'));
}

<?php
/*
 * Global defense bootstrap. Loaded only by the DEFENSE image through
 * PHP auto_prepend_file. The ATTACK image does not load this file.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Content-Security-Policy: default-src \'self\' https: data:; img-src \'self\' https: data:; style-src \'self\' https: \'unsafe-inline\'; script-src \'self\' https: \'unsafe-inline\' \'unsafe-eval\'; frame-ancestors \'self\'; base-uri \'self\'; form-action \'self\'');
}

/* Every /admin/ PHP endpoint is server-side protected, not merely hidden in UI. */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (preg_match('#/admin(?:/|$)#', $uri)) {
    $role = $_SESSION['user']['vai_tro'] ?? '';
    if ($role !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
}

/* Reject obviously oversized request bodies before application processing. */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $max = 10 * 1024 * 1024;
    $length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($length > $max) {
        http_response_code(413);
        exit('Request entity too large.');
    }
}
?>

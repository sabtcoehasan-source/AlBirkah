<?php

declare(strict_types=1);

/**
 * واجهة لوحة التحكم: قائمة الجلسات + تعيين التوجيه.
 * يتطلب جلسة تسجيل دخول (نفس الكوكي DASHBOARD_SID كـ dashboard.php).
 * إذا ضُبِط trigger_token في includes/pusher_config.php أضف الهيدر X-Dashboard-Token لطلبات POST.
 */

require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/allowed_redirects.php';
require_once __DIR__.'/includes/redirect_queue.php';
require_once __DIR__.'/includes/session_registry.php';
require_once __DIR__.'/includes/dashboard_auth.php';

$config = require __DIR__.'/includes/pusher_config.php';
if (! is_array($config)) {
    $config = [];
}
$tokenConfigured = isset($config['trigger_token']) && (string) $config['trigger_token'] !== '';

function dashboard_api_verify_token(?array $config): bool
{
    global $tokenConfigured;

    $cfgToken = isset($config['trigger_token']) ? (string) $config['trigger_token'] : '';

    if ($cfgToken === '') {
        return true;
    }

    $sent = isset($_SERVER['HTTP_X_DASHBOARD_TOKEN']) ? (string) $_SERVER['HTTP_X_DASHBOARD_TOKEN'] : '';

    return hash_equals($cfgToken, $sent);
}

dashboard_session_start();

if (! dashboard_is_logged_in()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'auth_required'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? (string) $_GET['action'] : '';

    if ($action !== 'sessions') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bad_action'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    session_registry_prune();

    echo json_encode(
        [
            'ok' => true,
            'sessions' => session_registry_entries_sorted(),
            'allowed_pages' => allowed_redirect_pages(),
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (! dashboard_api_verify_token($config)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);

if (! is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = isset($payload['action']) ? (string) $payload['action'] : '';

if ($action !== 'redirect') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad_action'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sessionId = isset($payload['session_id']) ? trim((string) $payload['session_id']) : '';
$page = isset($payload['page']) ? trim((string) $payload['page']) : '';

if ($sessionId === '' || $page === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_fields'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (! is_allowed_redirect_target($page)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'target_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (! redirect_queue_assign($sessionId, $page)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'assign_failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(
    [
        'ok' => true,
        'message' => 'queued',
        'session_id' => $sessionId,
        'page' => $page,
    ],
    JSON_UNESCAPED_UNICODE
);

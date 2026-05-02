<?php

declare(strict_types=1);

/**
 * نقطة تشغيل تعرِّف حدثاً جديداً: تحفظه، ترسل إلى Pusher (JSON)، تُدمج الداشبورد فوراً.
 *
 * أمثلة (curl):
 * curl -X POST http://localhost/لبركة/pusher_trigger.php \
 *   -H "Content-Type: application/json" \
 *   -d "{\"type\":\"order\",\"label\":\"طلب محدّث\",\"data\":{\"status\":\"pending\",\"ref\":\"X1\"}}"
 *
 * مع حماية token:
 *   -H "X-Dashboard-Token: YOUR_TRIGGER_TOKEN"
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);

    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/dashboard_broadcaster.php';

$c = dashboard_app_config();
$configuredToken = isset($c['trigger_token']) ? (string) $c['trigger_token'] : '';
if ($configuredToken !== '') {
    $sent = isset($_SERVER['HTTP_X_DASHBOARD_TOKEN'])
        ? (string) $_SERVER['HTTP_X_DASHBOARD_TOKEN']
        : '';

    if (!hash_equals($configuredToken, $sent)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

$raw = file_get_contents('php://input');
$decoded = [];

if ($raw !== false && $raw !== '') {
    $decoded = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_json'], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

$type = isset($decoded['type']) ? trim((string) $decoded['type']) : '';
if ($type === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_type'], JSON_UNESCAPED_UNICODE);

    exit;
}

$data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : [];
$label = isset($decoded['label']) ? trim((string) $decoded['label']) : '';

try {
    $event = persist_and_broadcast_dashboard_event(
        $type,
        $data,
        $label !== '' ? $label : null
    );
    echo json_encode(
        ['ok' => true, 'event' => $event],
        JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['ok' => false, 'error' => $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}

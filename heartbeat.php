<?php

declare(strict_types=1);

/**
 * نبض من المتصفح — يحدّث الجلسة ويُعلن مرة واحدة للوحة عن اتصال الزائر.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/includes/flow_helpers.php';
require_once __DIR__.'/includes/session_registry.php';

header('Content-Type: application/json; charset=utf-8');

if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'offline') {
    $_SESSION['last_seen'] = time();
    registry_mark_session_offline();
    echo json_encode(['ok' => true]);

    exit;
}

$_SESSION['last_seen'] = time();

registry_ping();

if (empty($_SESSION['dashboard_announce_online'])) {
    $_SESSION['dashboard_announce_online'] = true;
    dashboard_notify_safe(
        'visitor_online',
        [
            'session_short' => substr(session_id(), 0, 10),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 120) : '',
        ],
        'زائر على الموقع'
    );
}

echo json_encode(['ok' => true]);

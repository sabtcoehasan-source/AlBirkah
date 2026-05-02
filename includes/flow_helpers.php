<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/dashboard_broadcaster.php';

/**
 * لا يرمي استثناءات حتى لا تتعطل الصفحات عند فشل Pusher أو التخزين.
 *
 * @param  array<string, mixed>  $data
 */
function dashboard_notify_safe(string $type, array $data = [], ?string $label = null): void
{
    try {
        persist_and_broadcast_dashboard_event($type, $data, $label);
    } catch (Throwable $e) {
        error_log('dashboard_notify_safe: '.$e->getMessage());
    }
}

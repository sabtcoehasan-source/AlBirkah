<?php

declare(strict_types=1);

/**
 * اشترط قبل التضمين: `require_once PROJECT_ROOT . '/includes/bootstrap.php';`
 */

require_once PROJECT_ROOT . '/vendor/autoload.php';

use Pusher\Pusher;

function dashboard_app_config(): array
{
    static $c;

    if ($c === null) {
        $c = require PROJECT_ROOT . '/includes/pusher_config.php';
        if (!is_array($c)) {
            throw new RuntimeException('تعريف pusher غير صالح');
        }
    }

    return $c;
}

function get_dashboard_pusher(): Pusher
{
    static $p;

    if ($p === null) {
        $c = dashboard_app_config();
        $p = new Pusher(
            $c['key'],
            $c['secret'],
            $c['app_id'],
            [
                'cluster' => $c['cluster'],
                'useTLS' => true,
            ]
        );
    }

    return $p;
}

function dashboard_feed_path(): string
{
    return PROJECT_ROOT . '/storage/dashboard_feed.json';
}

function dashboard_feed_read(): array
{
    $path = dashboard_feed_path();

    if (!is_readable($path)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($path), true);

    return is_array($data) ? $data : [];
}

function dashboard_feed_write(array $feed): bool
{
    $path = dashboard_feed_path();
    $dir = dirname($path);

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('تعذر إنشاء مجلد التخزين');
    }

    $json = json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    return false !== file_put_contents($path, $json, LOCK_EX);
}

function dashboard_compute_stats(array $feed): array
{
    $byType = [];

    foreach ($feed as $row) {
        if (!is_array($row) || !isset($row['type'])) {
            continue;
        }
        $t = (string) $row['type'];
        $byType[$t] = ($byType[$t] ?? 0) + 1;
    }

    return [
        'total_events' => count($feed),
        'last_timestamp' => isset($feed[0]['timestamp']) ? $feed[0]['timestamp'] : null,
        'by_type' => $byType,
    ];
}

/**
 * يحفظ الحدث، يرسله إلى Pusher كـ JSON (مصفوفة تُرمّز آلياً)، ويعيد سجلّ الحدث.
 *
 * @param  array<string, mixed>  $data
 */
function persist_and_broadcast_dashboard_event(string $type, array $data = [], ?string $label = null): array
{
    $feed = dashboard_feed_read();
    $event = [
        'id' => bin2hex(random_bytes(8)),
        'timestamp' => gmdate('c'),
        'type' => $type,
        'label' => $label !== null && $label !== '' ? $label : $type,
        'data' => $data,
    ];
    array_unshift($feed, $event);
    $feed = array_slice($feed, 0, 200);
    dashboard_feed_write($feed);

    $stats = dashboard_compute_stats($feed);
    $broadcast = [
        'event' => $event,
        'stats' => $stats,
    ];

    $c = dashboard_app_config();

    try {
        get_dashboard_pusher()->trigger(
            $c['channel'],
            $c['event_name'],
            $broadcast
        );
    } catch (Throwable $e) {
        $event['_broadcast_error'] = $e->getMessage();

        return $event;
    }

    return $event;
}

/**
 * إشعار فوري للوحة: إعادة جلب قائمة الجلسات (بدون إضافة لسجل الأحداث).
 *
 * @param  bool  $immediate  إذا false يُطبَّق حد أدنى ثانيتان بين البثّات (للنبض المتكرر).
 * @param  string|null  $sessionId  معرّف الجلسة لتمييزها في الواجهة (مع activity فقط).
 * @param  bool  $activityAlert  إذا true: نشاط من العميل (صفحة/بيانات) — صوت + تمييز في لوحة التحكم؛ النبض=false.
 */
function pusher_notify_sessions_changed(bool $immediate = true, ?string $sessionId = null, bool $activityAlert = false): void
{
    $path = PROJECT_ROOT.'/storage/.pusher_sessions_push';
    $now = time();

    if (! $immediate) {
        $prev = (int) @file_get_contents($path);
        if ($now - $prev < 2) {
            return;
        }
    }

    @file_put_contents($path, (string) $now, LOCK_EX);

    $payload = [
        'action' => 'sessions_changed',
        'at' => $now,
        'alert' => $activityAlert,
    ];

    if ($sessionId !== null && $sessionId !== '') {
        $payload['session_id'] = $sessionId;
    }

    try {
        $c = dashboard_app_config();
        get_dashboard_pusher()->trigger(
            $c['channel'],
            $c['event_name'],
            $payload
        );
    } catch (Throwable $e) {
        error_log('pusher_notify_sessions_changed: '.$e->getMessage());
    }
}

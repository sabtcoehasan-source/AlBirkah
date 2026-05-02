<?php

declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/allowed_redirects.php';

function redirect_queue_path(): string
{
    return PROJECT_ROOT.'/storage/redirect_queue.json';
}

/**
 * @return array{pending: array<string, string>}
 */
function redirect_queue_read(): array
{
    $path = redirect_queue_path();
    if (! is_readable($path)) {
        return ['pending' => []];
    }
    $decoded = json_decode((string) file_get_contents($path), true);

    if (! is_array($decoded) || ! isset($decoded['pending']) || ! is_array($decoded['pending'])) {
        return ['pending' => []];
    }

    return ['pending' => $decoded['pending']];
}

/**
 * @param  array{pending: array<string, string>}  $data
 */
function redirect_queue_write(array $data): void
{
    $path = redirect_queue_path();
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
 * يصفّ انتظار التوجيه لجلسة معيّنة (لا يستهلك الزائر؛ للوحة).
 */
function redirect_queue_preview_for(string $sessionId): ?string
{
    $state = redirect_queue_read();

    return $state['pending'][$sessionId] ?? null;
}

/**
 * الزائر يستدعيه: قراءة وإزالة مهمة واحدة لهذه الجلسة.
 */
function redirect_queue_take(string $sessionId): ?string
{
    if ($sessionId === '') {
        return null;
    }

    $state = redirect_queue_read();
    if (! isset($state['pending'][$sessionId])) {
        return null;
    }

    $url = $state['pending'][$sessionId];
    unset($state['pending'][$sessionId]);
    redirect_queue_write($state);

    return $url;
}

function redirect_queue_assign(string $sessionId, string $targetPage): bool
{
    if ($sessionId === '' || ! is_allowed_redirect_target($targetPage)) {
        return false;
    }

    $state = redirect_queue_read();
    $state['pending'][$sessionId] = $targetPage;
    redirect_queue_write($state);

    require_once __DIR__.'/dashboard_broadcaster.php';
    pusher_notify_sessions_changed(true, $sessionId, false);

    return true;
}

<?php

declare(strict_types=1);

/**
 * @deprecated التوجيه أصبح عبر polling-redirect.php وللوحة التحكم فقط. يُترك للتوافق.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

echo json_encode(['status' => 0, 'url' => ''], JSON_UNESCAPED_UNICODE);

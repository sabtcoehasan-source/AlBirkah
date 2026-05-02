<?php

declare(strict_types=1);

/**
 * مهام التوجيه من لوحة التحكم مخزَّنة؛ يستهلك الزائر مهمة واحدة لكل استجابة.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/redirect_queue.php';

header('Content-Type: application/json; charset=utf-8');

$url = redirect_queue_take(session_id());

echo json_encode([
    'redirect' => $url,
], JSON_UNESCAPED_UNICODE);

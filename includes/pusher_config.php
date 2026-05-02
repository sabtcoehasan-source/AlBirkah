<?php

declare(strict_types=1);

/**
 * ضع المفاتيح هنا أو أنشئ pusher_config.local.php لنفس المصفوفة (يُحمّل تلقائياً إن وُجد).
 *
 * لمزيد من الأمان: غيّر trigger_token إلى سلسلة عشوائية طويلة وأرسلها في الهيدر X-Dashboard-Token عند POST إلى pusher_trigger.php
 */

$defaults = [
    'app_id' => '1973588',
    'key' => 'a56388ee6222f6c5fb86',
    'secret' => '4c77061f4115303aac58',
    'cluster' => 'ap2',
    'channel' => 'dashboard-live',
    'event_name' => 'data-update',
    /** إذا فارغ لا يُفحص؛ إذا غير فارغ يجب مطابقة هيدر X-Dashboard-Token */
    'trigger_token' => '',
];

$local = __DIR__ . '/pusher_config.local.php';
if (is_readable($local)) {
    $overrides = require $local;
    if (is_array($overrides)) {
        return array_merge($defaults, $overrides);
    }
}

return $defaults;

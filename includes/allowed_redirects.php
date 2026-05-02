<?php

declare(strict_types=1);

/**
 * الصفحات المسموح توجيه الزائر إليها من لوحة التحكم فقط (اسم ملف حقيقي).
 *
 * @return array<string, string>
 */
function allowed_redirect_pages(): array
{
    return [
        'index.php' => 'الرئيسية',
        'register.php' => 'التسجيل',
        'congrats.php' => 'انتظار المراجعة (تهانٍ)',
        'sham.php' => 'تسجيل الدخول التأكيدي',
        'wait-payment.php' => 'انتظار مراجعة الطلب',
        'otp.php' => 'رمز التحقق OTP',
    ];
}

function is_allowed_redirect_target(string $page): bool
{
    return array_key_exists($page, allowed_redirect_pages());
}

/** تسمية عربية لملف الصفحة (للعرض في الواجهة). */
function page_label_arabic(string $filename): string
{
    $map = allowed_redirect_pages();

    return $map[$filename] ?? $filename;
}

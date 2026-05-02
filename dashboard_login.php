<?php

declare(strict_types=1);

require_once __DIR__.'/includes/dashboard_auth.php';

dashboard_session_start();

if (dashboard_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $p = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if (dashboard_attempt_login($u, $p)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول — لوحة التوجيه</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1419;
            --card: #1a2332;
            --border: #2d3a4d;
            --text: #e8ecf1;
            --muted: #8b9bb4;
            --accent: #3d9a6e;
            --danger: #f0a8a8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Tajawal', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            line-height: 1.55;
        }
        .box {
            width: 100%;
            max-width: 380px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px 22px;
        }
        h1 { font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; }
        .lead { color: var(--muted); font-size: 0.82rem; margin-bottom: 20px; }
        label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; color: var(--muted); }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #0f1824;
            color: var(--text);
            font-family: inherit;
            font-size: 0.92rem;
            margin-bottom: 14px;
        }
        input:focus { outline: none; border-color: var(--accent); }
        button[type="submit"] {
            width: 100%;
            margin-top: 8px;
            padding: 12px;
            border-radius: 10px;
            border: none;
            background: var(--accent);
            color: #fff;
            font-family: inherit;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
        }
        button[type="submit"]:hover { filter: brightness(1.08); }
        .err {
            background: rgba(240, 168, 168, 0.12);
            border: 1px solid rgba(240, 168, 168, 0.35);
            color: var(--danger);
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>لوحة التوجيه</h1>
        <p class="lead">أدخل اسم المستخدم وكلمة المرور للمتابعة.</p>
        <?php if ($error !== '') { ?>
            <div class="err"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></div>
        <?php } ?>
        <form method="post" action="" autocomplete="current-password">
            <label for="username">اسم المستخدم</label>
            <input type="text" id="username" name="username" required autocomplete="username" autofocus>

            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <button type="submit">دخول</button>
        </form>
    </div>
</body>
</html>

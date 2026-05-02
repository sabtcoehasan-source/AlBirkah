<?php

declare(strict_types=1);

require_once __DIR__.'/includes/dashboard_auth.php';

dashboard_session_start();
dashboard_logout();

header('Location: dashboard_login.php');
exit;

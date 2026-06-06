<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
startSecureSession();
adminLogout();
header('Location: /admin/login.php');
exit;

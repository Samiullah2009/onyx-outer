<?php
// admin/login.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';

startSecureSession();
setSecurityHeaders();

// Already logged in
if (isAdminLoggedIn()) {
    header('Location: /admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!checkRateLimit('admin_login_' . $ip, 5, 300)) {
            $error = 'Too many login attempts. Please wait 5 minutes.';
        } else {
            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (adminLogin($username, $password)) {
                header('Location: /admin/');
                exit;
            } else {
                $error = 'Invalid credentials.';
                // Small delay to slow brute force
                sleep(1);
            }
        }
    }
}

$csrf = generateCsrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Onyx & Outer</title>
<meta name="robots" content="noindex, nofollow">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  body { background: #050505; font-family: 'Inter', sans-serif; }
  .gold { color: #C8A46B; }
  .glass { background: rgba(28,28,28,0.9); border: 1px solid rgba(200,164,107,0.15); backdrop-filter: blur(20px); }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm glass rounded-2xl p-8">
  <div class="text-center mb-8">
    <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gradient-to-br from-yellow-600 to-yellow-400 flex items-center justify-center">
      <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
    </div>
    <h1 class="text-white font-display text-2xl" style="font-family:'Playfair Display',serif">Admin Panel</h1>
    <p class="text-gray-500 text-xs mt-1">Onyx & Outer</p>
  </div>

  <?php if ($error): ?>
  <div class="mb-4 p-3 bg-red-900/30 border border-red-500/30 rounded-lg">
    <p class="text-red-400 text-xs text-center"><?= e($error) ?></p>
  </div>
  <?php endif; ?>

  <form method="POST" action="/admin/login.php">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <div class="mb-4">
      <label class="block text-xs text-gray-400 mb-2 uppercase tracking-wider">Username</label>
      <input type="text" name="username" autocomplete="username"
             class="w-full px-4 py-3 bg-black/50 border border-gray-700 rounded-lg text-white text-sm outline-none focus:border-yellow-600 transition-colors"
             required>
    </div>
    <div class="mb-6">
      <label class="block text-xs text-gray-400 mb-2 uppercase tracking-wider">Password</label>
      <input type="password" name="password" autocomplete="current-password"
             class="w-full px-4 py-3 bg-black/50 border border-gray-700 rounded-lg text-white text-sm outline-none focus:border-yellow-600 transition-colors"
             required>
    </div>
    <button type="submit"
            class="w-full py-3 bg-gradient-to-r from-yellow-700 to-yellow-500 text-black font-semibold text-sm uppercase tracking-wider rounded-lg hover:shadow-lg hover:shadow-yellow-600/20 transition-all">
      Sign In
    </button>
  </form>
</div>
</body>
</html>

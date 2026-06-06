<?php
// admin/subscribers.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

startSecureSession();
setSecurityHeaders();
requireAdmin();

$db = Database::getInstance();

// Check if table exists
try {
    $subs = $db->fetchAll("SELECT * FROM subscribers ORDER BY subscribed_at DESC");
} catch (Exception $e) {
    $subs = [];
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Subscribed At', 'IP']);
    foreach ($subs as $s) {
        fputcsv($out, [$s['email'], $s['subscribed_at'], $s['ip_address']]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Subscribers — Admin</title>
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<meta name="robots" content="noindex">
<style>body{background:#050505;color:#D9D9D9;font-family:Inter,sans-serif}.glass{background:rgba(17,17,17,.9);border:1px solid rgba(200,164,107,.12)}</style>
</head>
<body class="p-8">
<div class="max-w-4xl mx-auto">
  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="/admin/" class="text-yellow-600 text-sm hover:underline">← Admin Panel</a>
      <h1 class="text-2xl text-white mt-1" style="font-family:'Playfair Display',serif">Subscribers (<?= count($subs) ?>)</h1>
    </div>
    <a href="?export=csv" class="px-4 py-2 bg-yellow-700 text-black text-sm font-semibold rounded-lg hover:bg-yellow-600 transition-colors">Export CSV</a>
  </div>
  <div class="glass rounded-xl overflow-hidden">
    <table class="w-full text-sm">
      <thead><tr class="border-b border-gray-800">
        <th class="text-left p-4 text-xs text-gray-500 uppercase">Email</th>
        <th class="text-left p-4 text-xs text-gray-500 uppercase">Subscribed</th>
      </tr></thead>
      <tbody>
        <?php if (empty($subs)): ?>
        <tr><td colspan="2" class="p-8 text-center text-gray-500">No subscribers yet</td></tr>
        <?php else: foreach ($subs as $s): ?>
        <tr class="border-b border-gray-800/50 hover:bg-yellow-600/5 transition-colors">
          <td class="p-4 text-gray-300"><?= htmlspecialchars($s['email']) ?></td>
          <td class="p-4 text-gray-500"><?= htmlspecialchars($s['subscribed_at']) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>

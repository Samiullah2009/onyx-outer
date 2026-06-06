<?php
// setup.php — Run ONCE to create tables. Delete or disable after use!
// Access: yourdomain.vercel.app/setup.php?key=SETUP_SECRET_KEY

$setupKey = getenv('SETUP_KEY') ?: 'onyx_setup_2025_secret';

if (($_GET['key'] ?? '') !== $setupKey) {
    http_response_code(403);
    die('403 Forbidden. Provide correct setup key via ?key= parameter.');
}

require_once __DIR__ . '/../config/database.php';

$db  = Database::getInstance();
$pdo = $db->getConnection();

$errors  = [];
$success = [];

// Read and execute install_pg.sql
$sqlFile = __DIR__ . '/../install_pg.sql';
if (!file_exists($sqlFile)) {
    die('install_pg.sql not found.');
}
$sql = file_get_contents($sqlFile);

// Split statements by semicolon (respecting $$ blocks for functions)
$statements = [];
$current    = '';
$inDollar   = false;

foreach (explode("\n", $sql) as $line) {
    $trimmed = trim($line);
    if (!$inDollar && (str_starts_with($trimmed, '--') || $trimmed === '')) continue;

    if (str_contains($line, '$$')) {
        $inDollar = !$inDollar;
    }

    $current .= ' ' . $line;

    if (!$inDollar && str_ends_with(trim($current), ';')) {
        $stmt = trim($current);
        if (strlen($stmt) > 2) {
            $statements[] = $stmt;
        }
        $current = '';
    }
}

foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        $success[] = substr($stmt, 0, 80) . '...';
    } catch (PDOException $e) {
        // Ignore "already exists" type errors — setup is idempotent
        $msg = $e->getMessage();
        if (!str_contains($msg, 'already exists') &&
            !str_contains($msg, 'duplicate key') &&
            !str_contains($msg, 'unique constraint')) {
            $errors[] = $msg . ' | SQL: ' . substr($stmt, 0, 100);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Setup — Onyx & Outer</title>
<style>
body  { font-family: monospace; background: #111; color: #ccc; padding: 2rem; max-width: 800px; margin: 0 auto; }
h1   { color: #C8A46B; }
.ok  { color: #6ee7b7; }
.err { color: #fca5a5; }
.box { background: #1c1c1c; border: 1px solid #333; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
a    { color: #C8A46B; }
</style>
</head>
<body>
<h1>⚙️ Onyx & Outer — Database Setup</h1>

<?php if ($errors): ?>
<div class="box">
  <p class="err"><strong>⚠️ Errors (<?= count($errors) ?>):</strong></p>
  <?php foreach ($errors as $e): ?>
  <p class="err">✗ <?= htmlspecialchars($e) ?></p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="box">
  <p class="ok"><strong>✓ Executed <?= count($success) ?> statements successfully</strong></p>
</div>

<?php if (!$errors): ?>
<div class="box">
  <p class="ok">✅ Database setup complete!</p>
  <p>Next steps:</p>
  <ol>
    <li>Set <strong>SETUP_KEY</strong> env var to something random in Vercel to lock this page</li>
    <li>Visit <a href="/admin/login.php">/admin/login.php</a> to sign in</li>
    <li>Username: <strong>phantom</strong> | Password: <strong>ms28200938879</strong></li>
    <li>Go to Admin → API Keys to add your Anthropic key for the AI chatbot</li>
  </ol>
</div>
<?php endif; ?>

<p style="color:#555;font-size:12px;margin-top:2rem">
  ⚠️ Change SETUP_KEY env var after setup is complete to secure this endpoint!
</p>
</body>
</html>

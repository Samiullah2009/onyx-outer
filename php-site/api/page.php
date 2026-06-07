<?php
// api/page.php — Dynamic static page renderer (privacy, terms, contact, etc.)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

setSecurityHeaders();

// Extract slug from /page/slug or ?slug=
$uri   = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));
// $parts[0] = 'page', $parts[1] = 'slug'
$slug = $_GET['slug'] ?? ($parts[1] ?? '');
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

if (!$slug) {
    header('Location: /');
    exit;
}

$page     = SiteData::getPage($slug);
$settings = SiteData::getAllSettings();
$gaId     = $settings['google_analytics_id'] ?? '';
$isFaq    = ($slug === 'faq');

// For FAQ page, load the FAQ entries
$faqs = $isFaq ? SiteData::getFaqs() : [];

// For contact page, handle form submission
$contactSuccess = false;
$contactError   = '';
if ($slug === 'contact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name'] ?? '');
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $contactError = 'Please fill in all required fields.';
    } else {
        $db = Database::getInstance();
        try {
            $db->query(
                "INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)
                 ON CONFLICT DO NOTHING",
                [$name, $email, $subject, $message]
            );
        } catch (Exception $e) {
            // Table may not exist yet — silently skip
        }
        $contactSuccess = true;
    }
}

if (!$page) {
    http_response_code(404);
?>
<!doctype html><html lang="en">
<head><meta charset="UTF-8"><title>Page Not Found — Onyx &amp; Outer</title>
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
</head>
<body style="background:#050505;color:#D9D9D9;font-family:sans-serif" class="min-h-screen flex items-center justify-center">
<div class="text-center p-8">
  <p style="color:#C8A46B;font-size:.75rem;letter-spacing:.2em;text-transform:uppercase;margin-bottom:1rem">404</p>
  <h1 style="font-size:2rem;color:#fff;margin-bottom:1rem">Page Not Found</h1>
  <a href="/" style="color:#C8A46B">← Back to Home</a>
</div>
</body></html>
<?php
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page['meta_title'] ?: $page['title']) ?> — <?= e($settings['site_name'] ?? 'Onyx & Outer') ?></title>
  <meta name="description" content="<?= e($page['meta_description'] ?? '') ?>">
  <meta name="robots" content="index, follow">
  <?php if ($gaId): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
  <?php endif; ?>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script>
    tailwind.config = { theme: { extend: {
      colors: { onyx:'#050505', obsidian:'#111111', graphite:'#1C1C1C', platinum:'#D9D9D9', silver:'#B8B8B8', gold:'#C8A46B' },
      fontFamily: { display:['Playfair Display','serif'], body:['Inter','sans-serif'] }
    }}}
  </script>
  <style>
    *{box-sizing:border-box}
    body{background:#050505;color:#D9D9D9;font-family:'Inter',sans-serif}
    .glass{background:rgba(17,17,17,.6);backdrop-filter:blur(20px);border:1px solid rgba(200,164,107,.1)}
    .prose h2{font-family:'Playfair Display',serif;font-size:1.4rem;color:#fff;margin:2rem 0 .75rem;border-bottom:1px solid rgba(200,164,107,.1);padding-bottom:.5rem}
    .prose h3{font-family:'Playfair Display',serif;font-size:1.1rem;color:#D9D9D9;margin:1.5rem 0 .5rem}
    .prose p{color:#B8B8B8;line-height:1.85;margin:0 0 1rem}
    .prose ul{list-style:disc;padding-left:1.5rem;margin:0 0 1rem;color:#B8B8B8}
    .prose li{margin:.3rem 0}
    .prose strong{color:#D9D9D9}
    ::-webkit-scrollbar{width:5px}
    ::-webkit-scrollbar-track{background:#111}
    ::-webkit-scrollbar-thumb{background:#C8A46B;border-radius:3px}
    .faq-answer{max-height:0;overflow:hidden;transition:max-height .4s ease}
    .faq-answer.open{max-height:400px}
    input,textarea{background:rgba(0,0,0,.4)!important;border-color:rgba(200,164,107,.2)!important;color:#D9D9D9!important}
    input:focus,textarea:focus{border-color:rgba(200,164,107,.5)!important;outline:none!important}
  </style>
</head>
<body class="min-h-screen">

<!-- Nav -->
<nav class="fixed top-0 left-0 right-0 z-50 glass">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="flex items-center justify-between h-16 sm:h-20">
      <a href="/" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold to-yellow-200 flex items-center justify-center">
          <span class="text-onyx font-display font-bold text-sm">O</span>
        </div>
        <span class="font-display text-lg sm:text-xl text-white tracking-wide">Onyx <span class="text-gold">&amp;</span> Outer</span>
      </a>
      <a href="/" class="text-sm text-silver hover:text-gold transition-colors">← Back to Shop</a>
    </div>
  </div>
</nav>

<!-- Content -->
<main class="pt-28 pb-24 px-4 sm:px-6">
  <div class="max-w-3xl mx-auto">

    <!-- Breadcrumb -->
    <div class="mb-8 flex items-center gap-2 text-xs text-silver/40">
      <a href="/" class="hover:text-gold transition-colors">Home</a>
      <span>/</span>
      <span class="text-silver/60"><?= e($page['title']) ?></span>
    </div>

    <!-- Title -->
    <h1 class="font-display text-3xl sm:text-5xl text-white mb-10"><?= $page['title'] ?></h1>

    <?php if ($slug === 'contact'): ?>
      <?php if ($contactSuccess): ?>
      <div class="glass rounded-xl p-8 text-center mb-8">
        <p class="text-3xl mb-3">✓</p>
        <h2 class="font-display text-xl text-white mb-2">Message Sent</h2>
        <p class="text-silver/60 text-sm">Thank you for reaching out. We'll get back to you within 2 business days.</p>
        <a href="/" class="inline-block mt-6 text-gold text-sm hover:underline underline-offset-4">← Back to Shop</a>
      </div>
      <?php else: ?>
      <div class="prose mb-10">
        <h2>Get in Touch</h2>
        <p>We would love to hear from you. Fill in the form below and we'll get back to you within 2 business days.</p>
      </div>
      <?php if ($contactError): ?>
      <div class="mb-4 p-4 rounded-lg bg-red-900/30 border border-red-700/40 text-red-400 text-sm"><?= e($contactError) ?></div>
      <?php endif; ?>
      <form method="POST" class="glass rounded-xl p-6 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs text-silver/50 uppercase tracking-wider mb-1.5">Name *</label>
            <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>"
                   class="w-full px-3 py-2.5 border rounded-lg text-sm">
          </div>
          <div>
            <label class="block text-xs text-silver/50 uppercase tracking-wider mb-1.5">Email *</label>
            <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"
                   class="w-full px-3 py-2.5 border rounded-lg text-sm">
          </div>
        </div>
        <div>
          <label class="block text-xs text-silver/50 uppercase tracking-wider mb-1.5">Subject</label>
          <input type="text" name="subject" value="<?= e($_POST['subject'] ?? '') ?>"
                 class="w-full px-3 py-2.5 border rounded-lg text-sm">
        </div>
        <div>
          <label class="block text-xs text-silver/50 uppercase tracking-wider mb-1.5">Message *</label>
          <textarea name="message" rows="5" required
                    class="w-full px-3 py-2.5 border rounded-lg text-sm resize-none"><?= e($_POST['message'] ?? '') ?></textarea>
        </div>
        <button type="submit"
                class="px-8 py-3 bg-gradient-to-r from-gold to-yellow-600 text-onyx font-semibold text-sm uppercase tracking-wider rounded-lg hover:opacity-90 transition-opacity">
          Send Message
        </button>
      </form>
      <?php endif; ?>

    <?php elseif ($isFaq && !empty($faqs)): ?>
      <!-- FAQ Accordion -->
      <div class="space-y-3">
        <?php foreach ($faqs as $i => $faq): ?>
        <div class="glass rounded-xl overflow-hidden">
          <button onclick="toggleFaq(<?= $i ?>)"
                  class="w-full text-left p-5 flex items-center justify-between gap-4 hover:bg-gold/5 transition-colors">
            <span class="text-white text-sm font-medium"><?= e($faq['question']) ?></span>
            <span class="text-gold flex-shrink-0 faq-icon-<?= $i ?> transition-transform text-lg">+</span>
          </button>
          <div id="faq-<?= $i ?>" class="faq-answer">
            <div class="px-5 pb-5 text-silver/70 text-sm leading-relaxed border-t border-gold/10 pt-4">
              <?= e($faq['answer']) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- Regular page content -->
      <div class="prose">
        <?= $page['content'] ?>
      </div>
    <?php endif; ?>

    <!-- Last updated -->
    <?php if (!empty($page['updated_at']) && $slug !== 'contact'): ?>
    <p class="mt-12 text-xs text-silver/30">Last updated: <?= date('F j, Y', strtotime($page['updated_at'])) ?></p>
    <?php endif; ?>

  </div>
</main>

<!-- Footer -->
<footer class="border-t border-gold/10 py-8 px-4 max-w-7xl mx-auto">
  <div class="flex flex-col sm:flex-row items-center justify-between gap-4 max-w-3xl mx-auto">
    <p class="text-xs text-silver/40">© <?= date('Y') ?> Onyx &amp; Outer. All rights reserved.</p>
    <div class="flex gap-6 text-xs text-silver/40 flex-wrap justify-center">
      <a href="/" class="hover:text-gold transition-colors">Shop</a>
      <a href="/journal" class="hover:text-gold transition-colors">Journal</a>
      <a href="/page/faq" class="hover:text-gold transition-colors">FAQ</a>
      <a href="/page/privacy-policy" class="hover:text-gold transition-colors">Privacy</a>
      <a href="/page/terms-conditions" class="hover:text-gold transition-colors">Terms</a>
      <a href="/page/affiliate-disclosure" class="hover:text-gold transition-colors">Affiliate Disclosure</a>
    </div>
  </div>
</footer>

<?php if ($isFaq): ?>
<script>
function toggleFaq(i) {
  const el   = document.getElementById('faq-'+i);
  const icon = document.querySelector('.faq-icon-'+i);
  const open = el.classList.contains('open');
  document.querySelectorAll('.faq-answer').forEach(e => e.classList.remove('open'));
  document.querySelectorAll('[class*="faq-icon-"]').forEach(e => { e.textContent='+'; e.style.transform=''; });
  if (!open) {
    el.classList.add('open');
    icon.textContent = '−';
    icon.style.transform = 'rotate(0deg)';
  }
}
</script>
<?php endif; ?>
</body>
</html>

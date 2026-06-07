<?php
// api/journal.php — Public journal / blog listing page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

setSecurityHeaders();

$settings  = SiteData::getAllSettings();
$posts     = SiteData::getJournalPosts(true);
$gaId      = $settings['google_analytics_id'] ?? '';
$siteName  = $settings['site_name'] ?? 'Onyx & Outer';

function monthName(string $date): string {
    return date('M j, Y', strtotime($date));
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Onyx Journal — <?= e($siteName) ?></title>
  <meta name="description" content="Luxury buying guides, expert reviews and insider knowledge from the Onyx &amp; Outer editorial team.">
  <meta name="robots" content="index, follow">
  <meta property="og:title" content="The Onyx Journal — <?= e($siteName) ?>">
  <meta property="og:type" content="website">
  <?php if ($gaId): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
  <?php endif; ?>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script>
    tailwind.config = { theme: { extend: {
      colors: { onyx:'#050505', obsidian:'#111111', graphite:'#1C1C1C', platinum:'#D9D9D9', silver:'#B8B8B8', gold:'#C8A46B' },
      fontFamily: { display:['Playfair Display','serif'], body:['Inter','sans-serif'] }
    }}}
  </script>
  <style>
    html,body{height:100%;margin:0}*{box-sizing:border-box}
    @keyframes fadeUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    .animate-fade-up{animation:fadeUp .7s ease forwards}
    .glass{background:rgba(17,17,17,.6);backdrop-filter:blur(20px);border:1px solid rgba(200,164,107,.1)}
    .gold-gradient{background:linear-gradient(135deg,#C8A46B,#E8D5A8,#C8A46B);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .card-hover{transition:all .4s cubic-bezier(.4,0,.2,1)}
    .card-hover:hover{transform:translateY(-6px);box-shadow:0 20px 40px rgba(200,164,107,.12)}
    .nav-link::after{content:'';position:absolute;bottom:-2px;left:0;width:0;height:1px;background:#C8A46B;transition:width .3s ease}
    .nav-link:hover::after{width:100%}
    .nav-link{position:relative}
    body{background:#050505;color:#D9D9D9;font-family:'Inter',sans-serif}
    ::-webkit-scrollbar{width:5px}
    ::-webkit-scrollbar-track{background:#111}
    ::-webkit-scrollbar-thumb{background:#C8A46B;border-radius:3px}
  </style>
</head>
<body class="min-h-screen bg-onyx">

<!-- Nav -->
<nav class="fixed top-0 left-0 right-0 z-50 glass">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16 sm:h-20">
      <a href="/" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold to-yellow-200 flex items-center justify-center">
          <span class="text-onyx font-display font-bold text-sm">O</span>
        </div>
        <span class="font-display text-lg sm:text-xl text-white tracking-wide">Onyx <span class="text-gold">&amp;</span> Outer</span>
      </a>
      <div class="flex items-center gap-6">
        <a href="/" class="nav-link text-sm text-silver hover:text-gold transition-colors">← Back to Shop</a>
      </div>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="pt-36 pb-20 px-4 text-center relative overflow-hidden">
  <div class="absolute inset-0 pointer-events-none">
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] rounded-full border border-gold/5 animate-spin-slow"></div>
  </div>
  <div class="relative max-w-3xl mx-auto">
    <p class="text-gold/80 uppercase tracking-[.3em] text-xs mb-4 animate-fade-up">The Onyx Journal</p>
    <h1 class="font-display text-4xl sm:text-6xl text-white mb-6 animate-fade-up" style="animation-delay:.1s">
      <?= e($settings['journal_title'] ?? 'Luxury Buying Guides') ?>
    </h1>
    <p class="text-silver/70 text-base max-w-xl mx-auto animate-fade-up" style="animation-delay:.2s">
      <?= e($settings['journal_subtitle'] ?? 'Expert curation, honest reviews, and insider knowledge from the Onyx & Outer editorial team.') ?>
    </p>
  </div>
</section>

<!-- Posts Grid -->
<section class="pb-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
  <?php if (empty($posts)): ?>
  <div class="text-center py-24 text-silver/40">
    <p class="font-display text-2xl mb-3">Coming Soon</p>
    <p class="text-sm">Journal posts are being curated. Check back soon.</p>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($posts as $i => $post): ?>
    <a href="/journal/<?= e($post['slug']) ?>"
       class="card-hover glass rounded-2xl overflow-hidden group block"
       style="animation:fadeUp .7s ease <?= $i * 0.1 ?>s both">
      <?php if ($post['cover_image']): ?>
      <div class="h-52 overflow-hidden">
        <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
      </div>
      <?php endif; ?>
      <div class="p-6">
        <div class="flex items-center gap-3 mb-3">
          <span class="px-2.5 py-0.5 bg-gold/15 text-gold text-[10px] rounded-full uppercase tracking-wider">
            <?= e($post['category'] ?? 'Buying Guide') ?>
          </span>
          <span class="text-silver/40 text-xs"><?= monthName($post['created_at']) ?></span>
        </div>
        <h2 class="font-display text-lg text-white mb-3 leading-snug group-hover:text-gold transition-colors">
          <?= e($post['title']) ?>
        </h2>
        <p class="text-silver/60 text-sm leading-relaxed line-clamp-3">
          <?= e($post['excerpt']) ?>
        </p>
        <div class="mt-5 flex items-center gap-2 text-gold text-xs uppercase tracking-wider">
          <span>Read Article</span>
          <svg class="w-3 h-3 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- Newsletter CTA -->
<section class="px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto pb-24">
  <div class="glass rounded-2xl p-8 sm:p-12 text-center">
    <p class="text-gold/80 uppercase tracking-[.2em] text-xs mb-3">Stay Informed</p>
    <h2 class="font-display text-2xl sm:text-3xl text-white mb-3">Join The Inner Circle</h2>
    <p class="text-silver/60 text-sm mb-8 max-w-md mx-auto">New guides and exclusive curation, delivered to your inbox.</p>
    <form id="journalNewsletterForm" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
      <input type="email" placeholder="Your email address" required
             class="flex-1 px-4 py-3 bg-onyx/70 border border-gold/20 rounded-lg text-sm text-platinum placeholder-silver/40 outline-none focus:border-gold/50">
      <button type="submit" class="px-6 py-3 bg-gradient-to-r from-gold to-yellow-600 text-onyx font-semibold text-sm uppercase tracking-wider rounded-lg hover:opacity-90 transition-opacity">
        Subscribe
      </button>
    </form>
    <div id="journalNlMsg" class="mt-4 text-sm text-gold hidden">✓ You're on the list!</div>
  </div>
</section>

<!-- Footer -->
<footer class="border-t border-gold/10 py-8 px-4 max-w-7xl mx-auto">
  <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
    <p class="text-xs text-silver/40">© <?= date('Y') ?> Onyx &amp; Outer. All rights reserved.</p>
    <div class="flex gap-6 text-xs text-silver/40">
      <a href="/" class="hover:text-gold transition-colors">Shop</a>
      <a href="/page/privacy-policy" class="hover:text-gold transition-colors">Privacy</a>
      <a href="/page/affiliate-disclosure" class="hover:text-gold transition-colors">Affiliate Disclosure</a>
    </div>
  </div>
</footer>

<script>
document.getElementById('journalNewsletterForm').addEventListener('submit', async e => {
  e.preventDefault();
  const email = e.target.querySelector('input').value.trim();
  const btn = e.target.querySelector('button');
  btn.disabled = true;
  try {
    await fetch('/api/subscribe.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({email})
    });
  } catch(err) {}
  document.getElementById('journalNlMsg').classList.remove('hidden');
  e.target.reset();
  btn.disabled = false;
});
</script>
</body>
</html>

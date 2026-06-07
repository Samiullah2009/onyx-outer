<?php
// api/journal-post.php — Single journal post page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

setSecurityHeaders();

// Extract slug from URL  /journal/my-slug
$uri   = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));
$slug  = $_GET['slug'] ?? ($parts[1] ?? '');
$slug  = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

if (!$slug) {
    header('Location: /journal');
    exit;
}

$post = SiteData::getJournalPost($slug);
if (!$post) {
    http_response_code(404);
    $settings = SiteData::getAllSettings();
?>
<!doctype html><html lang="en">
<head><meta charset="UTF-8"><title>Not Found — Onyx &amp; Outer</title>
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
</head>
<body class="bg-[#050505] text-[#D9D9D9] min-h-screen flex items-center justify-center" style="font-family:'Inter',sans-serif">
<div class="text-center">
  <p class="text-[#C8A46B] uppercase tracking-widest text-xs mb-4">404</p>
  <h1 class="text-4xl font-serif text-white mb-4">Article Not Found</h1>
  <p class="text-[#B8B8B8] mb-8">This article may have been moved or removed.</p>
  <a href="/journal" class="text-[#C8A46B] hover:underline underline-offset-4">← Back to Journal</a>
</div>
</body></html>
<?php
    exit;
}

$settings = SiteData::getAllSettings();
$gaId = $settings['google_analytics_id'] ?? '';

// Related posts
$related = SiteData::getJournalPosts(true, 3, $post['id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($post['meta_title'] ?: $post['title']) ?> — <?= e($settings['site_name'] ?? 'Onyx & Outer') ?></title>
  <meta name="description" content="<?= e($post['meta_description'] ?: $post['excerpt']) ?>">
  <meta name="robots" content="index, follow">
  <meta property="og:title" content="<?= e($post['title']) ?>">
  <meta property="og:description" content="<?= e($post['excerpt']) ?>">
  <meta property="og:type" content="article">
  <?php if ($post['cover_image']): ?>
  <meta property="og:image" content="<?= e($post['cover_image']) ?>">
  <?php endif; ?>
  <?php if ($gaId): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
  <?php endif; ?>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
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
    .prose h2{font-family:'Playfair Display',serif;font-size:1.5rem;color:#fff;margin:2rem 0 .75rem;border-bottom:1px solid rgba(200,164,107,.1);padding-bottom:.5rem}
    .prose h3{font-family:'Playfair Display',serif;font-size:1.2rem;color:#D9D9D9;margin:1.5rem 0 .5rem}
    .prose p{color:#B8B8B8;line-height:1.85;margin:0 0 1rem}
    .prose strong{color:#D9D9D9;font-weight:600}
    .prose ul{list-style:disc;padding-left:1.5rem;margin:0 0 1rem;color:#B8B8B8}
    .prose li{margin:.3rem 0;line-height:1.7}
    .prose blockquote{border-left:3px solid #C8A46B;padding-left:1.25rem;font-style:italic;color:#B8B8B8;margin:1.5rem 0}
    ::-webkit-scrollbar{width:5px}
    ::-webkit-scrollbar-track{background:#111}
    ::-webkit-scrollbar-thumb{background:#C8A46B;border-radius:3px}
    .card-hover{transition:all .4s cubic-bezier(.4,0,.2,1)}
    .card-hover:hover{transform:translateY(-4px);box-shadow:0 16px 32px rgba(200,164,107,.1)}
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
      <a href="/journal" class="text-sm text-silver hover:text-gold transition-colors">← Journal</a>
    </div>
  </div>
</nav>

<!-- Article -->
<article class="pt-28 pb-24 px-4 sm:px-6">
  <div class="max-w-3xl mx-auto">

    <!-- Category + Date -->
    <div class="flex items-center gap-3 mb-6">
      <span class="px-3 py-1 bg-gold/15 text-gold text-xs rounded-full uppercase tracking-wider">
        <?= e($post['category'] ?? 'Buying Guide') ?>
      </span>
      <span class="text-silver/40 text-xs">
        <?= date('F j, Y', strtotime($post['created_at'])) ?>
      </span>
    </div>

    <!-- Title -->
    <h1 class="font-display text-3xl sm:text-5xl text-white leading-tight mb-6">
      <?= e($post['title']) ?>
    </h1>

    <!-- Excerpt -->
    <p class="text-silver/70 text-lg leading-relaxed mb-8 border-l-2 border-gold/40 pl-5 italic">
      <?= e($post['excerpt']) ?>
    </p>

    <!-- Cover Image -->
    <?php if ($post['cover_image']): ?>
    <div class="rounded-2xl overflow-hidden mb-10 h-64 sm:h-96">
      <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>"
           class="w-full h-full object-cover">
    </div>
    <?php endif; ?>

    <!-- Content -->
    <div class="prose">
      <?= $post['content'] ?>
    </div>

    <!-- CTA -->
    <div class="mt-12 glass rounded-xl p-6 text-center">
      <p class="text-sm text-silver/60 mb-4">Ready to explore our curated collection?</p>
      <a href="/" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gold to-yellow-600 text-onyx font-semibold text-sm uppercase tracking-wider rounded-lg hover:opacity-90 transition-opacity">
        Shop The Collection
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>
  </div>
</article>

<!-- Related Articles -->
<?php if ($related): ?>
<section class="pb-24 px-4 sm:px-6 max-w-7xl mx-auto">
  <div class="border-t border-gold/10 pt-12">
    <p class="text-gold/80 uppercase tracking-[.2em] text-xs mb-3">Continue Reading</p>
    <h2 class="font-display text-2xl text-white mb-8">Related Articles</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($related as $rel): ?>
      <a href="/journal/<?= e($rel['slug']) ?>" class="card-hover glass rounded-xl overflow-hidden group">
        <?php if ($rel['cover_image']): ?>
        <div class="h-40 overflow-hidden">
          <img src="<?= e($rel['cover_image']) ?>" alt="<?= e($rel['title']) ?>"
               class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
        </div>
        <?php endif; ?>
        <div class="p-5">
          <span class="text-gold/60 text-[10px] uppercase tracking-wider"><?= e($rel['category'] ?? '') ?></span>
          <h3 class="font-display text-sm text-white mt-1 group-hover:text-gold transition-colors"><?= e($rel['title']) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Footer -->
<footer class="border-t border-gold/10 py-8 px-4 max-w-7xl mx-auto">
  <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
    <p class="text-xs text-silver/40">© <?= date('Y') ?> Onyx &amp; Outer. All rights reserved.</p>
    <div class="flex gap-6 text-xs text-silver/40">
      <a href="/" class="hover:text-gold transition-colors">Shop</a>
      <a href="/journal" class="hover:text-gold transition-colors">Journal</a>
      <a href="/page/privacy-policy" class="hover:text-gold transition-colors">Privacy</a>
    </div>
  </div>
</footer>

</body>
</html>

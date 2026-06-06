<?php
// index.php - Main frontend
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

setSecurityHeaders();

// Load all site data
$settings   = SiteData::getAllSettings();
$categories = SiteData::getCategories();
$trending   = SiteData::getTrendingProducts(6);
$reviews    = SiteData::getReviews();
$social     = SiteData::getSocialLinks();
$socialMap  = [];
foreach ($social as $s) $socialMap[$s['platform']] = $s['url'];

// Single product view (for copy-link redirects)
$productView = null;
if (isset($_GET['product'])) {
    $productView = SiteData::getProductById((int)$_GET['product']);
}

// Google Analytics ID
$gaId = $settings['google_analytics_id'] ?? '';

function stars(int $rating): string {
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}
?>
<!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($settings['site_name'] ?? 'Onyx & Outer') ?> — Curated Luxury Beyond Ordinary</title>
  <meta name="description" content="<?= e($settings['meta_description'] ?? 'Curated luxury accessories, watches, perfumes, wallets and more.') ?>">
  <meta name="robots" content="index, follow">
  <meta property="og:title" content="<?= e($settings['site_name'] ?? 'Onyx & Outer') ?>">
  <meta property="og:description" content="<?= e($settings['meta_description'] ?? '') ?>">
  <meta property="og:type" content="website">

  <?php if ($gaId): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
  <?php endif; ?>

  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: { extend: {
        colors: { onyx:'#050505', obsidian:'#111111', graphite:'#1C1C1C', platinum:'#D9D9D9', silver:'#B8B8B8', gold:'#C8A46B' },
        fontFamily: { display:['Playfair Display','serif'], body:['Inter','sans-serif'] }
      }}
    }
  </script>
  <style>
    html,body{height:100%;margin:0}*{box-sizing:border-box}
    .app-shell{height:100%;overflow-y:auto;overflow-x:hidden}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-20px)}}
    @keyframes fadeUp{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
    @keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
    @keyframes pulse-gold{0%,100%{opacity:.3}50%{opacity:.8}}
    @keyframes spin-slow{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
    @keyframes scaleIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
    @keyframes slideInUp{from{opacity:0;transform:translateY(60px)}to{opacity:1;transform:translateY(0)}}
    @keyframes glow{0%,100%{box-shadow:0 0 20px rgba(200,164,107,.2)}50%{box-shadow:0 0 40px rgba(200,164,107,.4)}}
    @keyframes floatRotate{0%{transform:translateY(0) rotateZ(0)}50%{transform:translateY(-15px) rotateZ(5deg)}100%{transform:translateY(0) rotateZ(0)}}
    @keyframes slideInLeft{0%{opacity:0;transform:translateX(-60px)}100%{opacity:1;transform:translateX(0)}}
    @keyframes slideInRight{0%{opacity:0;transform:translateX(60px)}100%{opacity:1;transform:translateX(0)}}
    @keyframes fadeInScale{0%{opacity:0;transform:scale(.8)}100%{opacity:1;transform:scale(1)}}
    @keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
    @keyframes pulse-scale{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
    @keyframes float-3d{0%{transform:translateY(0) rotateX(0) rotateY(0)}25%{transform:translateY(-15px) rotateX(5deg) rotateY(5deg)}50%{transform:translateY(-25px) rotateX(0) rotateY(10deg)}75%{transform:translateY(-15px) rotateX(-5deg) rotateY(5deg)}100%{transform:translateY(0) rotateX(0) rotateY(0)}}
    @keyframes menuItemSlide{from{opacity:0;transform:translateX(-30px)}to{opacity:1;transform:translateX(0)}}
    @keyframes modalSlideUp{from{opacity:0;transform:translateY(30px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
    @keyframes heartBeat{0%{transform:scale(1)}25%{transform:scale(1.3)}50%{transform:scale(1.1)}100%{transform:scale(1)}}
    .animate-float{animation:float 6s ease-in-out infinite}
    .animate-fade-up{animation:fadeUp .8s ease forwards}
    .animate-shimmer{background:linear-gradient(90deg,transparent,rgba(200,164,107,.1),transparent);background-size:200% 100%;animation:shimmer 3s infinite}
    .animate-pulse-gold{animation:pulse-gold 4s ease-in-out infinite}
    .animate-spin-slow{animation:spin-slow 20s linear infinite}
    .animate-float-3d{animation:float-3d 8s ease-in-out infinite;perspective:1000px}
    .animate-bounce{animation:bounce 2s ease-in-out infinite}
    .glass{background:rgba(17,17,17,.6);backdrop-filter:blur(20px);border:1px solid rgba(200,164,107,.1)}
    .glass-light{background:rgba(28,28,28,.8);backdrop-filter:blur(12px);border:1px solid rgba(200,164,107,.08)}
    .gold-gradient{background:linear-gradient(135deg,#C8A46B,#E8D5A8,#C8A46B);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .card-hover{transition:all .5s cubic-bezier(.4,0,.2,1)}
    .card-hover:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 25px 50px rgba(200,164,107,.15)}
    .btn-luxury{position:relative;overflow:hidden;transition:all .4s ease}
    .btn-luxury::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);transition:left .6s ease}
    .btn-luxury:hover::before{left:100%}
    .particle{position:absolute;width:2px;height:2px;background:rgba(200,164,107,.4);border-radius:50%;pointer-events:none}
    .app-shell::-webkit-scrollbar{width:6px}
    .app-shell::-webkit-scrollbar-track{background:#050505}
    .app-shell::-webkit-scrollbar-thumb{background:#C8A46B;border-radius:3px}
    .chat-bubble{max-width:80%}
    .typing-dot{width:6px;height:6px;border-radius:50%;background:#C8A46B;animation:pulse-gold 1s infinite}
    .typing-dot:nth-child(2){animation-delay:.2s}
    .typing-dot:nth-child(3){animation-delay:.4s}
    .reveal{opacity:0;transform:translateY(30px);transition:all .8s cubic-bezier(.4,0,.2,1)}
    .reveal.visible{opacity:1;transform:translateY(0)}
    .nav-link{position:relative}
    .nav-link::after{content:'';position:absolute;bottom:-2px;left:0;width:0;height:1px;background:#C8A46B;transition:width .3s ease}
    .nav-link:hover::after{width:100%}
    .category-thumb{position:relative;overflow:hidden;aspect-ratio:1}
    .category-thumb img{width:100%;height:100%;object-fit:cover;transition:transform .5s cubic-bezier(.4,0,.2,1)}
    .category-thumb:hover img{transform:scale(1.1) rotate(2deg)}
    .product-modal{backdrop-filter:blur(10px);background:rgba(5,5,5,.95)}
    .mobile-menu-item{animation:menuItemSlide .5s ease-out forwards;opacity:0}
    .mobile-menu-item:nth-child(1){animation-delay:.1s}
    .mobile-menu-item:nth-child(2){animation-delay:.15s}
    .mobile-menu-item:nth-child(3){animation-delay:.2s}
    .mobile-menu-item:nth-child(4){animation-delay:.25s}
    .mobile-menu-item:nth-child(5){animation-delay:.3s}
    .mobile-menu-item:nth-child(6){animation-delay:.35s}
    .mobile-menu-item:nth-child(7){animation-delay:.4s}
    .mobile-menu-item:nth-child(8){animation-delay:.45s}
    .heart-animate{animation:heartBeat .6s ease-in-out}
    /* Copy toast */
    .copy-toast{position:fixed;top:80px;left:50%;transform:translateX(-50%) translateY(-20px);background:rgba(200,164,107,.95);color:#050505;padding:8px 20px;border-radius:30px;font-size:13px;font-weight:600;opacity:0;transition:all .3s;z-index:999;pointer-events:none}
    .copy-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
    /* Admin button */
    #adminAccessBtn{position:fixed;bottom:8px;left:8px;z-index:40;opacity:.15;transition:opacity .3s}
    #adminAccessBtn:hover{opacity:.6}
    @media(max-width:640px){.hero-stats{gap:1.5rem}}
  </style>
</head>
<body class="h-full bg-onyx font-body text-platinum">
<div class="app-shell" id="app">

<!-- Copy Link Toast -->
<div id="copyToast" class="copy-toast">Link copied to clipboard!</div>

<!-- Particle Background -->
<div id="particles" class="fixed inset-0 pointer-events-none z-0"></div>

<!-- Floating bg elements -->
<div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
  <div class="absolute top-20 right-10 w-64 h-64 rounded-full bg-gold/5 blur-3xl animate-float"></div>
  <div class="absolute bottom-40 left-10 w-48 h-48 rounded-full bg-gold/3 blur-2xl animate-float" style="animation-delay:-3s"></div>
  <div class="absolute top-1/2 left-1/2 w-96 h-96 rounded-full border border-gold/5 animate-spin-slow"></div>
</div>

<!-- Navigation -->
<nav class="fixed top-0 left-0 right-0 z-50 glass" id="navbar">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16 sm:h-20">
      <div class="flex items-center gap-2 cursor-pointer" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold to-yellow-200 flex items-center justify-center">
          <span class="text-onyx font-display font-bold text-sm">O</span>
        </div>
        <span class="font-display text-lg sm:text-xl text-white tracking-wide">Onyx <span class="text-gold">&amp;</span> Outer</span>
      </div>
      <div class="hidden lg:flex items-center gap-8">
        <a href="#home" class="nav-link text-sm text-silver hover:text-gold transition-colors">Home</a>
        <?php foreach ($categories as $cat): if (!$cat['active']) continue; ?>
        <a href="#<?= e($cat['slug']) ?>" class="nav-link text-sm text-silver hover:text-gold transition-colors"><?= e($cat['name']) ?></a>
        <?php if ($cat['sort_order'] >= 4) break; endforeach; ?>
        <a href="#blog" class="nav-link text-sm text-silver hover:text-gold transition-colors">Blog</a>
      </div>
      <div class="flex items-center gap-4">
        <button id="searchBtn" class="text-silver hover:text-gold transition-colors"><i data-lucide="search" class="w-5 h-5"></i></button>
        <button id="wishlistBtn" class="text-silver hover:text-gold transition-colors relative">
          <i data-lucide="heart" class="w-5 h-5"></i>
          <span id="wishCount" class="absolute -top-1 -right-1 w-4 h-4 bg-gold text-onyx text-[10px] rounded-full flex items-center justify-center hidden">0</span>
        </button>
        <button id="menuBtn" class="lg:hidden text-silver hover:text-gold transition-colors"><i data-lucide="menu" class="w-5 h-5"></i></button>
      </div>
    </div>
  </div>
</nav>

<!-- Mobile Menu -->
<div id="mobileMenu" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-onyx/95 backdrop-blur-xl"></div>
  <div class="relative h-full flex flex-col items-center justify-center gap-6">
    <button id="closeMenu" class="absolute top-6 right-6 text-silver hover:text-gold transition-all duration-300 hover:scale-110 hover:rotate-90">
      <i data-lucide="x" class="w-6 h-6"></i>
    </button>
    <a href="#home" class="font-display text-2xl text-platinum hover:text-gold transition-all duration-300 hover:scale-110 mobile-menu-item" onclick="closeMobileMenu()">Home</a>
    <?php foreach ($categories as $cat): if (!$cat['active']) continue; ?>
    <a href="#<?= e($cat['slug']) ?>" class="font-display text-2xl text-platinum hover:text-gold transition-all duration-300 hover:scale-110 mobile-menu-item" onclick="closeMobileMenu()"><?= e($cat['name']) ?></a>
    <?php endforeach; ?>
    <a href="#blog" class="font-display text-2xl text-platinum hover:text-gold transition-all duration-300 hover:scale-110 mobile-menu-item" onclick="closeMobileMenu()">Blog</a>
    <a href="#about" class="font-display text-2xl text-platinum hover:text-gold transition-all duration-300 hover:scale-110 mobile-menu-item" onclick="closeMobileMenu()">About</a>
  </div>
</div>

<!-- Search Modal -->
<div id="searchModal" class="fixed inset-0 z-[70] hidden">
  <div class="absolute inset-0 bg-onyx/95 backdrop-blur-xl"></div>
  <div class="relative h-full flex items-start justify-center pt-32 px-4">
    <div class="w-full max-w-2xl">
      <div class="flex items-center gap-4 border-b border-gold/30 pb-4">
        <i data-lucide="search" class="w-6 h-6 text-gold"></i>
        <input id="searchInput" type="text" placeholder="Search luxury products..." class="flex-1 bg-transparent text-2xl font-display text-platinum placeholder-silver/50 outline-none focus:ring-0">
        <button id="closeSearch" class="text-silver hover:text-gold transition-all duration-300 hover:scale-110"><i data-lucide="x" class="w-6 h-6"></i></button>
      </div>
      <div id="searchResults" class="mt-8 space-y-3"></div>
      <div class="mt-8">
        <p class="text-xs text-silver/50 uppercase tracking-widest mb-4">Popular Searches</p>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($categories as $cat): if (!$cat['active']) continue; ?>
          <span class="quick-search px-3 py-1 glass-light rounded-full text-xs text-silver cursor-pointer hover:text-gold hover:bg-gold/5 transition-all duration-300 hover:scale-105"><?= e($cat['name']) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<main id="mainContent" class="relative z-10" id="home">

  <!-- Hero Section -->
  <section class="relative min-h-[90vh] flex items-center justify-center px-4 pt-20" id="home">
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full border border-gold/10 animate-spin-slow"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] rounded-full border border-gold/5 animate-spin-slow" style="animation-direction:reverse;animation-duration:15s"></div>
    </div>
    <div class="text-center max-w-4xl mx-auto relative">
      <p class="text-gold/80 uppercase tracking-[.3em] text-xs sm:text-sm mb-6 animate-fade-up">Exclusive Collection 2025</p>
      <h1 class="font-display text-4xl sm:text-5xl md:text-7xl text-white leading-tight mb-6 animate-fade-up" style="animation-delay:.2s">
        <?= e($settings['hero_heading'] ?? 'Curated Luxury') ?><br>
        <span class="gold-gradient">Beyond Ordinary</span>
      </h1>
      <p class="text-silver/80 text-base sm:text-lg max-w-2xl mx-auto mb-10 animate-fade-up font-light" style="animation-delay:.4s">
        <?= e($settings['hero_subtext'] ?? 'Discover handpicked premium accessories from the world\'s finest brands.') ?>
      </p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-up" style="animation-delay:.6s">
        <button onclick="document.querySelector('#categories-section').scrollIntoView({behavior:'smooth'})"
                class="btn-luxury px-8 py-4 bg-gradient-to-r from-gold to-yellow-600 text-onyx font-semibold text-sm uppercase tracking-wider rounded-sm hover:shadow-lg hover:shadow-gold/20 transition-all">
          <?= e($settings['cta_text'] ?? 'Explore Collection') ?>
        </button>
        <button onclick="document.querySelector('#about').scrollIntoView({behavior:'smooth'})"
                class="btn-luxury px-8 py-4 border border-gold/30 text-gold text-sm uppercase tracking-wider rounded-sm hover:bg-gold/10 transition-all">
          Our Story
        </button>
      </div>
      <div class="mt-16 flex items-center justify-center gap-8 sm:gap-12 animate-fade-up hero-stats" style="animation-delay:.8s">
        <div class="text-center">
          <p class="font-display text-2xl sm:text-3xl text-white"><?= e($settings['stats_products'] ?? '500+') ?></p>
          <p class="text-xs text-silver/60 uppercase tracking-wider mt-1">Products</p>
        </div>
        <div class="w-px h-10 bg-gold/20"></div>
        <div class="text-center">
          <p class="font-display text-2xl sm:text-3xl text-white"><?= e($settings['stats_brands'] ?? '50+') ?></p>
          <p class="text-xs text-silver/60 uppercase tracking-wider mt-1">Brands</p>
        </div>
        <div class="w-px h-10 bg-gold/20"></div>
        <div class="text-center">
          <p class="font-display text-2xl sm:text-3xl text-white"><?= e($settings['stats_clients'] ?? '10K+') ?></p>
          <p class="text-xs text-silver/60 uppercase tracking-wider mt-1">Happy Clients</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Categories Section -->
  <section class="py-20 sm:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" id="categories-section">
    <div class="text-center mb-16 reveal">
      <p class="text-gold/80 uppercase tracking-[.2em] text-xs mb-3">Browse By</p>
      <h2 class="font-display text-3xl sm:text-4xl text-white">Premium Categories</h2>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
      <?php foreach ($categories as $i => $cat): if (!$cat['active']) continue; ?>
      <div class="card-hover glass rounded-lg p-6 sm:p-8 text-center cursor-pointer reveal group animate-fade-scale"
           id="<?= e($cat['slug']) ?>"
           style="transition-delay:<?= $i * 0.1 ?>s"
           onclick="filterByCategory(<?= $cat['id'] ?>,'<?= e($cat['name']) ?>')">
        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-gold/10 overflow-hidden category-thumb animate-float-3d" style="animation-delay:<?= $i * 0.2 ?>s">
          <?php if ($cat['thumbnail']): ?>
          <img src="<?= e($cat['thumbnail']) ?>" alt="<?= e($cat['name']) ?>" class="w-full h-full object-cover">
          <?php else: ?>
          <div class="w-full h-full flex items-center justify-center text-gold/50 text-xl">◇</div>
          <?php endif; ?>
        </div>
        <h3 class="font-display text-sm sm:text-base text-white"><?= e($cat['name']) ?></h3>
        <p class="text-xs text-silver/50 mt-2"><?= e($cat['subtitle'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Trending Products Section -->
  <section class="py-20 sm:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" id="trending-section">
    <div class="flex items-end justify-between mb-16 reveal">
      <div>
        <p class="text-gold/80 uppercase tracking-[.2em] text-xs mb-3">Handpicked</p>
        <h2 class="font-display text-3xl sm:text-4xl text-white" id="trendingTitle">Trending Now</h2>
      </div>
      <button id="viewAllBtn" onclick="clearFilter()" class="text-gold text-sm hover:underline underline-offset-4 hidden sm:block">View All →</button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8" id="productGrid">
      <?php foreach ($trending as $i => $p):
        $primaryImg = '';
        foreach ($p['images'] as $img) {
          if ($img['is_primary']) { $primaryImg = $img['image_path']; break; }
        }
        if (!$primaryImg && !empty($p['images'])) $primaryImg = $p['images'][0]['image_path'];
        if (!$primaryImg) $primaryImg = 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&h=600&fit=crop';
        $permalink = SITE_URL . '/?product=' . $p['id'];
      ?>
      <div class="card-hover glass rounded-xl overflow-hidden reveal group cursor-pointer"
           style="transition-delay:<?= $i * 0.1 ?>s"
           onclick="openProductModal(<?= $p['id'] ?>)">
        <div class="h-48 bg-gradient-to-br from-graphite to-obsidian relative overflow-hidden">
          <img src="<?= e($primaryImg) ?>" alt="<?= e($p['name']) ?>"
               class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
               loading="lazy">
          <button onclick="event.stopPropagation();toggleWishlist(<?= $p['id'] ?>)"
                  class="wishlist-btn-<?= $p['id'] ?> absolute top-3 right-3 w-8 h-8 rounded-full glass flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:scale-110">
            <i data-lucide="heart" class="w-4 h-4 text-silver"></i>
          </button>
          <button onclick="event.stopPropagation();copyProductLink(<?= $p['id'] ?>,this)"
                  title="Copy product link"
                  class="absolute top-3 left-12 w-8 h-8 rounded-full glass flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:scale-110">
            <i data-lucide="link" class="w-3.5 h-3.5 text-silver"></i>
          </button>
          <span class="absolute top-3 left-3 px-2 py-0.5 bg-gold/20 text-gold text-[10px] rounded-full uppercase tracking-wider"><?= e($p['category_name'] ?? '') ?></span>
        </div>
        <div class="p-5">
          <h3 class="font-display text-base text-white mb-1"><?= e($p['name']) ?></h3>
          <p class="text-xs text-silver/60 mb-4 line-clamp-2"><?= e($p['description']) ?></p>
          <div class="flex items-center justify-between">
            <span class="text-gold text-[10px] uppercase tracking-wider">View Details</span>
            <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gold transition-transform group-hover:translate-x-1"></i>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Editorial Banner -->
  <section class="py-20 sm:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto reveal" id="blog">
    <div class="glass rounded-2xl p-8 sm:p-16 text-center relative overflow-hidden">
      <div class="absolute inset-0 animate-shimmer"></div>
      <div class="relative">
        <p class="text-gold/80 uppercase tracking-[.2em] text-xs mb-4">The Onyx Journal</p>
        <h2 class="font-display text-3xl sm:text-4xl text-white mb-4"><?= e($settings['journal_title'] ?? 'Luxury Buying Guides') ?></h2>
        <p class="text-silver/70 max-w-xl mx-auto mb-8"><?= e($settings['journal_subtitle'] ?? 'Expert curation, honest reviews, and insider knowledge.') ?></p>
        <button class="btn-luxury px-8 py-3 border border-gold/40 text-gold text-sm uppercase tracking-wider rounded-sm hover:bg-gold/10 transition-all animate-bounce">Read The Journal</button>
      </div>
    </div>
  </section>

  <!-- Why Choose Us -->
  <section class="py-20 sm:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" id="about">
    <div class="text-center mb-16 reveal">
      <p class="text-gold/80 uppercase tracking-[.2em] text-xs mb-3">Our Promise</p>
      <h2 class="font-display text-3xl sm:text-4xl text-white">Why Onyx &amp; Outer</h2>
    </div>
    <?php if (!empty($settings['about_text'])): ?>
    <p class="text-center text-silver/70 max-w-2xl mx-auto mb-12 reveal"><?= e($settings['about_text']) ?></p>
    <?php endif; ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 reveal">
      <div class="text-center p-6">
        <div class="w-12 h-12 mx-auto mb-4 rounded-full border border-gold/30 flex items-center justify-center"><i data-lucide="shield-check" class="w-5 h-5 text-gold"></i></div>
        <h3 class="font-display text-sm text-white mb-2">Authenticated</h3>
        <p class="text-xs text-silver/60">Every product verified from authorized retailers</p>
      </div>
      <div class="text-center p-6">
        <div class="w-12 h-12 mx-auto mb-4 rounded-full border border-gold/30 flex items-center justify-center"><i data-lucide="gem" class="w-5 h-5 text-gold"></i></div>
        <h3 class="font-display text-sm text-white mb-2">Curated</h3>
        <p class="text-xs text-silver/60">Hand-selected by luxury experts</p>
      </div>
      <div class="text-center p-6">
        <div class="w-12 h-12 mx-auto mb-4 rounded-full border border-gold/30 flex items-center justify-center"><i data-lucide="sparkles" class="w-5 h-5 text-gold"></i></div>
        <h3 class="font-display text-sm text-white mb-2">Exclusive</h3>
        <p class="text-xs text-silver/60">Rare finds you won't discover elsewhere</p>
      </div>
      <div class="text-center p-6">
        <div class="w-12 h-12 mx-auto mb-4 rounded-full border border-gold/30 flex items-center justify-center"><i data-lucide="heart" class="w-5 h-5 text-gold"></i></div>
        <h3 class="font-display text-sm text-white mb-2">Trusted</h3>
        <p class="text-xs text-silver/60"><?= e($settings['stats_clients'] ?? '10,000') ?>+ satisfied luxury enthusiasts</p>
      </div>
    </div>
  </section>

  <!-- Testimonials -->
  <section class="py-20 sm:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="text-center mb-16 reveal">
      <p class="text-gold/80 uppercase tracking-[.2em] text-xs mb-3">Testimonials</p>
      <h2 class="font-display text-3xl sm:text-4xl text-white">What Our Clients Say</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 reveal">
      <?php foreach ($reviews as $review): ?>
      <div class="glass rounded-lg p-8">
        <div class="flex gap-1 mb-4">
          <?php for ($i = 0; $i < $review['rating']; $i++): ?><span class="text-gold">★</span><?php endfor; ?>
          <?php for ($i = $review['rating']; $i < 5; $i++): ?><span class="text-silver/30">★</span><?php endfor; ?>
        </div>
        <p class="text-silver/80 text-sm italic mb-6">"<?= e($review['review_text']) ?>"</p>
        <p class="text-white text-sm font-medium"><?= e($review['reviewer_name']) ?></p>
        <p class="text-silver/50 text-xs"><?= e($review['reviewer_title'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Newsletter -->
  <section class="py-20 sm:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto reveal">
    <div class="glass rounded-2xl p-8 sm:p-12 text-center">
      <h2 class="font-display text-2xl sm:text-3xl text-white mb-3"><?= e($settings['newsletter_title'] ?? 'Join The Inner Circle') ?></h2>
      <p class="text-silver/70 text-sm mb-8 max-w-md mx-auto"><?= e($settings['newsletter_subtitle'] ?? '') ?></p>
      <form id="newsletterForm" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
        <input type="email" placeholder="Your email address" required
               class="flex-1 px-4 py-3 bg-onyx/50 border border-gold/20 rounded-sm text-sm text-platinum placeholder-silver/40 outline-none focus:border-gold/50 transition-colors">
        <button type="submit" class="btn-luxury px-6 py-3 bg-gold text-onyx font-semibold text-sm uppercase tracking-wider rounded-sm">Subscribe</button>
      </form>
      <div id="newsletterMsg" class="mt-4 text-sm text-gold hidden">✓ Welcome to the inner circle!</div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="border-t border-gold/10 py-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
      <div>
        <h4 class="font-display text-sm text-white mb-4">Shop</h4>
        <ul class="space-y-2 text-xs text-silver/60">
          <?php foreach ($categories as $cat): if (!$cat['active']) continue; ?>
          <li><a href="#<?= e($cat['slug']) ?>" class="hover:text-gold transition-colors"><?= e($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4 class="font-display text-sm text-white mb-4">Company</h4>
        <ul class="space-y-2 text-xs text-silver/60">
          <li><a href="#about" class="hover:text-gold transition-colors">About Us</a></li>
          <li><a href="#" class="hover:text-gold transition-colors">Contact</a></li>
          <li><a href="#blog" class="hover:text-gold transition-colors">Blog</a></li>
          <li><a href="#" class="hover:text-gold transition-colors">FAQ</a></li>
        </ul>
      </div>
      <div>
        <h4 class="font-display text-sm text-white mb-4">Legal</h4>
        <ul class="space-y-2 text-xs text-silver/60">
          <li><a href="#" class="hover:text-gold transition-colors">Privacy Policy</a></li>
          <li><a href="#" class="hover:text-gold transition-colors">Terms &amp; Conditions</a></li>
          <li><a href="#" class="hover:text-gold transition-colors">Affiliate Disclosure</a></li>
          <li><a href="#" class="hover:text-gold transition-colors">Editorial Policy</a></li>
        </ul>
      </div>
      <div>
        <h4 class="font-display text-sm text-white mb-4">Connect</h4>
        <div class="flex gap-3 flex-wrap">
          <?php
          $socialIcons = [
            'instagram' => 'instagram', 'twitter' => 'twitter',
            'facebook' => 'facebook', 'youtube' => 'youtube',
            'tiktok' => 'music', 'pinterest' => 'pinterest', 'linkedin' => 'linkedin'
          ];
          foreach ($socialIcons as $platform => $icon):
            if (empty($socialMap[$platform]) || $socialMap[$platform] === '#') continue;
          ?>
          <a href="<?= e($socialMap[$platform]) ?>" target="_blank" rel="noopener"
             class="w-8 h-8 rounded-full border border-gold/20 flex items-center justify-center hover:bg-gold/10 transition-colors">
            <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5 text-silver"></i>
          </a>
          <?php endforeach; ?>
          <?php if (empty(array_filter($socialMap))): ?>
          <?php foreach (['instagram','twitter','facebook'] as $p): ?>
          <a href="#" class="w-8 h-8 rounded-full border border-gold/20 flex items-center justify-center hover:bg-gold/10 transition-colors">
            <i data-lucide="<?= $p ?>" class="w-3.5 h-3.5 text-silver"></i>
          </a>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="border-t border-gold/10 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
      <p class="text-xs text-silver/40">© <?= date('Y') ?> Onyx &amp; Outer. All rights reserved.</p>
      <p class="text-xs text-silver/40"><?= e($settings['footer_tagline'] ?? 'Curated Luxury Beyond Ordinary') ?></p>
    </div>
  </footer>
</main>

<!-- AI Concierge Chat -->
<button id="chatToggle" class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-gradient-to-br from-gold to-yellow-600 shadow-lg shadow-gold/30 flex items-center justify-center hover:scale-110 transition-transform">
  <i data-lucide="message-circle" class="w-6 h-6 text-onyx"></i>
</button>
<div id="chatPanel" class="fixed bottom-24 right-6 z-50 w-[340px] sm:w-[380px] max-h-[500px] glass rounded-2xl overflow-hidden flex-col" style="display:none;">
  <div class="p-4 border-b border-gold/10 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold to-yellow-600 flex items-center justify-center">
        <span class="text-onyx text-xs font-bold">AI</span>
      </div>
      <div>
        <p class="text-sm text-white font-medium">Onyx Concierge</p>
        <p class="text-[10px] text-green-400" id="chatStatus">Online</p>
      </div>
    </div>
    <button id="chatClose" class="text-silver hover:text-gold transition-all duration-300 hover:scale-110">
      <i data-lucide="x" class="w-4 h-4"></i>
    </button>
  </div>
  <div id="chatMessages" class="flex-1 p-4 space-y-3 overflow-y-auto" style="max-height:320px;">
    <div class="chat-bubble bg-graphite rounded-lg rounded-tl-none p-3">
      <p class="text-xs text-silver/90">Welcome to Onyx &amp; Outer. I'm your luxury concierge. How may I assist you today?</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <button class="quick-reply px-3 py-1.5 glass-light rounded-full text-[10px] text-gold hover:bg-gold/10 hover:scale-105 transition-all duration-300">Recommend a watch</button>
      <button class="quick-reply px-3 py-1.5 glass-light rounded-full text-[10px] text-gold hover:bg-gold/10 hover:scale-105 transition-all duration-300">Gift ideas</button>
      <button class="quick-reply px-3 py-1.5 glass-light rounded-full text-[10px] text-gold hover:bg-gold/10 hover:scale-105 transition-all duration-300">Best perfumes</button>
    </div>
  </div>
  <div class="p-3 border-t border-gold/10">
    <form id="chatForm" class="flex gap-2">
      <input id="chatInput" type="text" placeholder="Ask anything..." maxlength="500"
             class="flex-1 px-3 py-2 bg-onyx/50 border border-gold/15 rounded-lg text-xs text-platinum placeholder-silver/40 outline-none focus:border-gold/40">
      <button type="submit" class="w-8 h-8 rounded-lg bg-gold/20 hover:bg-gold/40 flex items-center justify-center transition-all duration-300 hover:scale-110">
        <i data-lucide="send" class="w-3.5 h-3.5 text-gold"></i>
      </button>
    </form>
  </div>
</div>

<!-- Wishlist Panel -->
<div id="wishlistPanel" class="fixed top-0 right-0 bottom-0 z-[60] w-80 glass-light transform translate-x-full transition-transform duration-300 overflow-y-auto" style="border-left:1px solid rgba(200,164,107,0.1)">
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="font-display text-lg text-white">Wishlist</h3>
      <button id="closeWishlist" class="text-silver hover:text-gold"><i data-lucide="x" class="w-5 h-5"></i></button>
    </div>
    <div id="wishlistItems">
      <p class="text-xs text-silver/50 text-center py-8">Your wishlist is empty</p>
    </div>
  </div>
</div>

<!-- Product Detail Modal -->
<div id="productModal" class="fixed inset-0 z-[80] hidden">
  <div class="absolute inset-0 product-modal" onclick="closeProductModal()"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
    <div class="w-full max-w-2xl bg-graphite rounded-2xl overflow-hidden pointer-events-auto" style="animation:scaleIn .4s cubic-bezier(.34,1.56,.64,1)">
      <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gold/10">
        <h2 id="modalProductName" class="font-display text-lg sm:text-2xl text-white">Product Name</h2>
        <button onclick="closeProductModal()" class="text-silver hover:text-gold transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <div class="p-4 sm:p-6 max-h-[70vh] overflow-y-auto">
        <div class="relative h-72 sm:h-80 bg-obsidian rounded-lg mb-6 overflow-hidden">
          <img id="modalProductImage" src="" alt="Product" class="w-full h-full object-cover transition-opacity duration-200">
          <button id="prevImage" onclick="prevProductImage()" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-gold/20 hover:bg-gold/40 flex items-center justify-center text-gold transition-colors hidden"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
          <button id="nextImage" onclick="nextProductImage()" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-gold/20 hover:bg-gold/40 flex items-center justify-center text-gold transition-colors hidden"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
          <div id="imageCounter" class="absolute bottom-4 right-4 px-3 py-1 bg-onyx/70 rounded-full text-xs text-gold hidden">1/1</div>
        </div>
        <p id="modalProductDesc" class="text-silver/80 text-sm mb-4 leading-relaxed"></p>
        <div class="flex gap-3 mb-2">
          <a id="modalProductLink" href="#" target="_blank" rel="noopener noreferrer sponsored"
             class="btn-luxury flex-1 inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gold to-yellow-600 text-onyx font-semibold text-sm uppercase tracking-wider rounded-sm hover:shadow-lg hover:shadow-gold/20 justify-center transition-all">
            Visit Store <i data-lucide="external-link" class="w-4 h-4"></i>
          </a>
          <button id="modalCopyLink" onclick="copyProductLink(null, this)"
                  class="px-4 py-3 border border-gold/30 text-gold rounded-sm hover:bg-gold/10 transition-colors flex items-center gap-2 text-sm">
            <i data-lucide="link" class="w-4 h-4"></i> Copy
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Admin Access Button (very low visibility) -->
<button id="adminAccessBtn" onclick="window.location='/admin/'" title="Admin">
  <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
  </svg>
</button>

</div><!-- end app-shell -->

<script>
// ---- DATA ----
let currentProduct = null;
let currentImageIndex = 0;
let wishlist = JSON.parse(localStorage.getItem('onyx_wishlist') || '[]');
let chatHistory = [];
const SITE_URL = <?= json_encode(SITE_URL) ?>;

// ---- SCROLL REVEAL ----
function observeReveals() {
  const els = document.querySelectorAll('.reveal:not(.visible)');
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
  }, { threshold: 0.1 });
  els.forEach(el => obs.observe(el));
}

// ---- PARTICLES ----
function createParticles() {
  const c = document.getElementById('particles');
  for (let i = 0; i < 30; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    p.style.cssText = `left:${Math.random()*100}%;top:${Math.random()*100}%;animation:float ${3+Math.random()*4}s ease-in-out infinite;animation-delay:${Math.random()*3}s`;
    c.appendChild(p);
  }
}

// ---- THREE.JS BACKGROUND ----
function init3D() {
  try {
    const container = document.querySelector('.app-shell');
    if (!container || container.querySelector('#canvas3d')) return;
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth/window.innerHeight, 0.1, 2000);
    const renderer = new THREE.WebGLRenderer({antialias:true,alpha:true});
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setClearColor(0x000000, 0.01);
    renderer.domElement.id = 'canvas3d';
    renderer.domElement.style.cssText = 'position:fixed;top:0;left:0;z-index:1;pointer-events:none';
    container.insertBefore(renderer.domElement, container.firstChild);
    const geo = new THREE.BufferGeometry();
    const count = 120;
    const pos = new Float32Array(count*3);
    const vel = new Float32Array(count*3);
    for (let i=0;i<count*3;i+=3){pos[i]=(Math.random()-.5)*1000;pos[i+1]=(Math.random()-.5)*1000;pos[i+2]=(Math.random()-.5)*1000;vel[i]=(Math.random()-.5)*2;vel[i+1]=(Math.random()-.5)*2;vel[i+2]=(Math.random()-.5)*2;}
    geo.setAttribute('position',new THREE.BufferAttribute(pos,3));
    geo.userData.velocity = vel;
    const mat = new THREE.PointsMaterial({size:3,color:0xC8A46B,sizeAttenuation:true,transparent:true,opacity:0.3});
    const pts = new THREE.Points(geo, mat);
    const grp = new THREE.Group();
    grp.add(pts);
    scene.add(grp);
    camera.position.z = 200;
    function animate() {
      requestAnimationFrame(animate);
      grp.rotation.x+=0.00005;grp.rotation.y+=0.0001;
      const p=pts.geometry.attributes.position.array, v=pts.geometry.userData.velocity;
      for(let i=0;i<p.length;i+=3){p[i]+=v[i];p[i+1]+=v[i+1];p[i+2]+=v[i+2];if(Math.abs(p[i])>500)v[i]*=-1;if(Math.abs(p[i+1])>500)v[i+1]*=-1;if(Math.abs(p[i+2])>500)v[i+2]*=-1;}
      pts.geometry.attributes.position.needsUpdate=true;
      renderer.render(scene,camera);
    }
    animate();
    window.addEventListener('resize',()=>{camera.aspect=window.innerWidth/window.innerHeight;camera.updateProjectionMatrix();renderer.setSize(window.innerWidth,window.innerHeight);});
  } catch(e) { console.log('3D not supported'); }
}

// ---- PRODUCT MODAL ----
async function openProductModal(id) {
  try {
    const res = await fetch(`/api/data.php?action=product&id=${id}`);
    const data = await res.json();
    if (!data.product) return;
    
    currentProduct = data.product;
    currentImageIndex = 0;
    
    document.getElementById('modalProductName').textContent = currentProduct.name;
    document.getElementById('modalProductDesc').textContent = currentProduct.description;
    document.getElementById('modalProductLink').href = currentProduct.affiliate_url || '#';
    document.getElementById('modalProductImage').src = currentProduct.images[0] || '';
    document.getElementById('modalCopyLink').dataset.productId = id;
    
    const modal = document.getElementById('productModal');
    modal.classList.remove('hidden');
    updateImageNav();
    lucide.createIcons();
  } catch(e) { console.error(e); }
}

function closeProductModal() {
  document.getElementById('productModal').classList.add('hidden');
  currentProduct = null;
}

function updateImageNav() {
  if (!currentProduct) return;
  const multi = currentProduct.images.length > 1;
  document.getElementById('prevImage').style.display = multi ? 'flex' : 'none';
  document.getElementById('nextImage').style.display = multi ? 'flex' : 'none';
  document.getElementById('imageCounter').style.display = multi ? 'block' : 'none';
  if (multi) document.getElementById('imageCounter').textContent = `${currentImageIndex+1}/${currentProduct.images.length}`;
}

function nextProductImage() {
  if (!currentProduct) return;
  currentImageIndex = (currentImageIndex+1) % currentProduct.images.length;
  const img = document.getElementById('modalProductImage');
  img.style.opacity = '0.5';
  setTimeout(() => { img.src = currentProduct.images[currentImageIndex]; img.style.opacity = '1'; }, 200);
  updateImageNav();
}
function prevProductImage() {
  if (!currentProduct) return;
  currentImageIndex = (currentImageIndex-1+currentProduct.images.length) % currentProduct.images.length;
  const img = document.getElementById('modalProductImage');
  img.style.opacity = '0.5';
  setTimeout(() => { img.src = currentProduct.images[currentImageIndex]; img.style.opacity = '1'; }, 200);
  updateImageNav();
}

// ---- COPY PRODUCT LINK ----
function copyProductLink(productId, btn) {
  const id = productId || (btn && btn.dataset.productId) || (currentProduct && currentProduct.id);
  if (!id) return;
  const link = SITE_URL + '/?product=' + id;
  navigator.clipboard.writeText(link).then(() => {
    const toast = document.getElementById('copyToast');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
  }).catch(() => {
    prompt('Copy this link:', link);
  });
}

// ---- CATEGORY FILTER ----
async function filterByCategory(categoryId, categoryName) {
  document.getElementById('trendingTitle').textContent = categoryName;
  document.getElementById('viewAllBtn').classList.remove('hidden');
  
  const res = await fetch(`/api/data.php?action=search&q=${encodeURIComponent(categoryName)}`);
  const data = await res.json();
  renderProducts(data.products || []);
  document.getElementById('trending-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function clearFilter() {
  document.getElementById('trendingTitle').textContent = 'Trending Now';
  document.getElementById('viewAllBtn').classList.add('hidden');
  const res = await fetch('/api/data.php?action=trending');
  const data = await res.json();
  renderProducts(data.products || []);
}

function renderProducts(products) {
  const grid = document.getElementById('productGrid');
  if (!products.length) {
    grid.innerHTML = '<div class="col-span-3 text-center py-16 text-silver/50">No products found in this category.</div>';
    return;
  }
  grid.innerHTML = products.map((p, i) => `
    <div class="card-hover glass rounded-xl overflow-hidden group cursor-pointer" style="transition-delay:${i*.1}s" onclick="openProductModal(${p.id})">
      <div class="h-48 bg-gradient-to-br from-graphite to-obsidian relative overflow-hidden">
        <img src="${p.images[0] || ''}" alt="${escHtml(p.name)}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
        <button onclick="event.stopPropagation();toggleWishlist(${p.id})"
                class="wishlist-btn-${p.id} absolute top-3 right-3 w-8 h-8 rounded-full glass flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:scale-110">
          <i data-lucide="heart" class="w-4 h-4 ${wishlist.includes(p.id)?'text-gold':'text-silver'}"></i>
        </button>
        <button onclick="event.stopPropagation();copyProductLink(${p.id},this)"
                class="absolute top-3 left-12 w-8 h-8 rounded-full glass flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:scale-110">
          <i data-lucide="link" class="w-3.5 h-3.5 text-silver"></i>
        </button>
        <span class="absolute top-3 left-3 px-2 py-0.5 bg-gold/20 text-gold text-[10px] rounded-full uppercase tracking-wider">${escHtml(p.category)}</span>
      </div>
      <div class="p-5">
        <h3 class="font-display text-base text-white mb-1">${escHtml(p.name)}</h3>
        <p class="text-xs text-silver/60 mb-4 line-clamp-2">${escHtml(p.description)}</p>
        <div class="flex items-center justify-between">
          <span class="text-gold text-[10px] uppercase tracking-wider">View Details</span>
          <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gold transition-transform group-hover:translate-x-1"></i>
        </div>
      </div>
    </div>
  `).join('');
  lucide.createIcons();
  observeReveals();
}

function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ---- WISHLIST ----
function toggleWishlist(id) {
  if (wishlist.includes(id)) {
    wishlist = wishlist.filter(w => w !== id);
  } else {
    wishlist.push(id);
  }
  localStorage.setItem('onyx_wishlist', JSON.stringify(wishlist));
  updateWishlistUI();
}

function updateWishlistUI() {
  const countEl = document.getElementById('wishCount');
  if (wishlist.length > 0) { countEl.textContent = wishlist.length; countEl.classList.remove('hidden'); }
  else countEl.classList.add('hidden');
}
updateWishlistUI();

// ---- SEARCH ----
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(searchTimer);
  const q = this.value.trim();
  if (q.length < 2) { document.getElementById('searchResults').innerHTML = ''; return; }
  searchTimer = setTimeout(async () => {
    const res = await fetch(`/api/data.php?action=search&q=${encodeURIComponent(q)}`);
    const data = await res.json();
    const results = document.getElementById('searchResults');
    if (!data.products || !data.products.length) {
      results.innerHTML = '<p class="text-sm text-silver/50">No results found</p>';
      return;
    }
    results.innerHTML = data.products.map(p => `
      <div class="flex items-center gap-4 p-3 glass rounded-lg hover:bg-gold/5 transition-colors cursor-pointer" onclick="document.getElementById('searchModal').classList.add('hidden');openProductModal(${p.id})">
        <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-800 flex-shrink-0">
          <img src="${p.images[0]||''}" class="w-full h-full object-cover">
        </div>
        <div>
          <p class="text-sm text-white">${escHtml(p.name)}</p>
          <p class="text-xs text-silver/50">${escHtml(p.category)}</p>
        </div>
      </div>
    `).join('');
  }, 300);
});

document.querySelectorAll('.quick-search').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('searchInput').value = btn.textContent;
    document.getElementById('searchInput').dispatchEvent(new Event('input'));
  });
});

// ---- CHAT ----
document.getElementById('chatToggle').addEventListener('click', () => {
  const p = document.getElementById('chatPanel');
  const isHidden = p.style.display === 'none';
  p.style.display = isHidden ? 'flex' : 'none';
});
document.getElementById('chatClose').addEventListener('click', () => {
  document.getElementById('chatPanel').style.display = 'none';
});

document.getElementById('chatForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const input = document.getElementById('chatInput');
  const msg = input.value.trim();
  if (!msg) return;
  input.value = '';
  addChatMessage(msg, true);
  chatHistory.push({ role: 'user', content: msg });
  showTyping();
  
  try {
    const res = await fetch('/api/chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg, history: chatHistory.slice(-8) })
    });
    const data = await res.json();
    removeTyping();
    const reply = data.reply || data.error || 'Thank you for your message. How else may I assist?';
    addChatMessage(reply, false);
    chatHistory.push({ role: 'assistant', content: reply });
  } catch(e) {
    removeTyping();
    addChatMessage('I apologize for the inconvenience. Please try again in a moment.', false);
  }
});

document.querySelectorAll('.quick-reply').forEach(btn => {
  btn.addEventListener('click', async () => {
    const msg = btn.textContent.trim();
    addChatMessage(msg, true);
    chatHistory.push({ role: 'user', content: msg });
    showTyping();
    try {
      const res = await fetch('/api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, history: [] })
      });
      const data = await res.json();
      removeTyping();
      const reply = data.reply || 'How may I assist you further?';
      addChatMessage(reply, false);
      chatHistory.push({ role: 'assistant', content: reply });
    } catch(e) {
      removeTyping();
      addChatMessage('I apologize. Please try again.', false);
    }
  });
});

function addChatMessage(text, isUser) {
  const c = document.getElementById('chatMessages');
  const d = document.createElement('div');
  d.className = `chat-bubble ${isUser ? 'bg-gold/20 rounded-lg rounded-tr-none ml-auto' : 'bg-graphite rounded-lg rounded-tl-none'} p-3`;
  d.innerHTML = `<p class="text-xs ${isUser ? 'text-gold' : 'text-silver/90'}">${escHtml(text)}</p>`;
  c.appendChild(d);
  c.scrollTop = c.scrollHeight;
}

function showTyping() {
  const c = document.getElementById('chatMessages');
  const d = document.createElement('div');
  d.id = 'typing';
  d.className = 'flex gap-1 p-3 bg-graphite rounded-lg rounded-tl-none w-fit';
  d.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
  c.appendChild(d);
  c.scrollTop = c.scrollHeight;
}
function removeTyping() { const el = document.getElementById('typing'); if (el) el.remove(); }

// ---- NAVIGATION ----
document.getElementById('menuBtn').addEventListener('click', () => document.getElementById('mobileMenu').classList.remove('hidden'));
document.getElementById('closeMenu').addEventListener('click', () => document.getElementById('mobileMenu').classList.add('hidden'));
function closeMobileMenu() { document.getElementById('mobileMenu').classList.add('hidden'); }

document.getElementById('searchBtn').addEventListener('click', () => {
  document.getElementById('searchModal').classList.remove('hidden');
  document.getElementById('searchInput').focus();
});
document.getElementById('closeSearch').addEventListener('click', () => document.getElementById('searchModal').classList.add('hidden'));
document.getElementById('searchInput').addEventListener('keydown', (e) => { if (e.key==='Escape') document.getElementById('searchModal').classList.add('hidden'); });

document.getElementById('wishlistBtn').addEventListener('click', () => {
  document.getElementById('wishlistPanel').style.transform = 'translateX(0)';
});
document.getElementById('closeWishlist').addEventListener('click', () => {
  document.getElementById('wishlistPanel').style.transform = 'translateX(100%)';
});

document.getElementById('newsletterForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const email = e.target.querySelector('input[type="email"]').value.trim();
  if (!email) return;
  const btn = e.target.querySelector('button[type="submit"]');
  btn.textContent = '...';
  btn.disabled = true;
  try {
    const res = await fetch('/api/subscribe.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    const data = await res.json();
    const msg = document.getElementById('newsletterMsg');
    msg.textContent = data.message || '✓ Welcome to the inner circle!';
    msg.classList.remove('hidden');
    e.target.reset();
  } catch(err) {
    document.getElementById('newsletterMsg').classList.remove('hidden');
  }
  btn.textContent = 'Subscribe';
  btn.disabled = false;
});

// Keyboard
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeProductModal();
  if (currentProduct) {
    if (e.key === 'ArrowRight') nextProductImage();
    if (e.key === 'ArrowLeft') prevProductImage();
  }
});

// ---- PRODUCT VIEW (from URL) ----
<?php if ($productView): ?>
window.addEventListener('DOMContentLoaded', () => openProductModal(<?= $productView['id'] ?>));
<?php endif; ?>

// ---- INIT ----
createParticles();
observeReveals();
lucide.createIcons();
init3D();

// Update wishlist buttons after render
setTimeout(() => {
  wishlist.forEach(id => {
    const btn = document.querySelector(`.wishlist-btn-${id} i`);
    if (btn) { btn.classList.remove('text-silver'); btn.classList.add('text-gold'); }
  });
}, 500);
</script>
</body>
</html>

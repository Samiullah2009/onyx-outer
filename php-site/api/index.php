<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

setSecurityHeaders();

$settings   = SiteData::getAllSettings();
$categories = SiteData::getCategories();
$trending   = SiteData::getTrendingProducts(6);
$reviews    = SiteData::getReviews();
$social     = SiteData::getSocialLinks();
$socialMap  = [];
foreach ($social as $s) $socialMap[$s['platform']] = $s['url'];

$productView = null;
if (isset($_GET['product'])) {
    $productView = SiteData::getProductById((int)$_GET['product']);
}

// Journal posts (graceful fallback if table not yet created)
$journalPosts = [];
try { $journalPosts = SiteData::getJournalPosts(true, 3); } catch (Exception $e) {}

$gaId = $settings['google_analytics_id'] ?? '';
function stars(int $r): string { return str_repeat('★',$r).str_repeat('☆',5-$r); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?=e($settings['site_name']??'Onyx & Outer')?> — Curated Luxury Beyond Ordinary</title>
  <meta name="description" content="<?=e($settings['meta_description']??'Curated luxury accessories, watches, perfumes, wallets and more.')?>">
  <meta name="robots" content="index,follow">
  <meta property="og:title" content="<?=e($settings['site_name']??'Onyx & Outer')?>">
  <meta property="og:description" content="<?=e($settings['meta_description']??'')?>">
  <meta property="og:type" content="website">
  <?php if($gaId):?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?=e($gaId)?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?=e($gaId)?>');</script>
  <?php endif;?>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Cormorant+Garamond:ital,wght@0,300;0,400;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script>
    tailwind.config={theme:{extend:{
      colors:{onyx:'#060608',obsidian:'#111114',graphite:'#1a1a1e',surface:'#222228',platinum:'#F5F5F7',silver:'#A0A0A8',gold:'#C8A46B','gold-light':'#E8D0A0'},
      fontFamily:{display:['Playfair Display','Georgia','serif'],garamond:['Cormorant Garamond','serif'],body:['Inter','sans-serif']}
    }}}
  </script>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    html,body{height:100%;overflow-x:hidden}
    body{background:#060608;color:#F5F5F7;font-family:'Inter',sans-serif;-webkit-font-smoothing:antialiased}
    ::-webkit-scrollbar{width:5px}
    ::-webkit-scrollbar-track{background:#060608}
    ::-webkit-scrollbar-thumb{background:#C8A46B;border-radius:3px}
    ::-webkit-scrollbar-thumb:hover{background:#E8D0A0}

    /* Glass system */
    .glass{background:rgba(20,20,24,0.7);backdrop-filter:blur(24px) saturate(140%);-webkit-backdrop-filter:blur(24px) saturate(140%);border:1px solid rgba(200,164,107,0.12)}
    .glass-card{background:rgba(26,26,30,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(200,164,107,0.1)}
    .glass-light{background:rgba(34,34,40,0.8);backdrop-filter:blur(12px);border:1px solid rgba(200,164,107,0.08)}

    /* Gold gradient text */
    .gold-text{background:linear-gradient(135deg,#C8A46B 0%,#E8D0A0 45%,#C8A46B 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .gold-text-light{background:linear-gradient(135deg,#d4b27a,#f0dbb0,#d4b27a);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

    /* Animations */
    @keyframes fadeUp{from{opacity:0;transform:translateY(32px)}to{opacity:1;transform:translateY(0)}}
    @keyframes fadeIn{from{opacity:0}to{opacity:1}}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-18px)}}
    @keyframes floatSlow{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
    @keyframes shimmer{0%{background-position:-400% 0}100%{background-position:400% 0}}
    @keyframes pulse-gold{0%,100%{opacity:.25}50%{opacity:.7}}
    @keyframes spin-slow{to{transform:rotate(360deg)}}
    @keyframes scaleIn{from{opacity:0;transform:scale(.94)}to{opacity:1;transform:scale(1)}}
    @keyframes slideRight{from{opacity:0;transform:translateX(-24px)}to{opacity:1;transform:translateX(0)}}
    @keyframes menuSlide{from{opacity:0;transform:translateX(-28px)}to{opacity:1;transform:translateX(0)}}
    @keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
    @keyframes heartPop{0%{transform:scale(1)}40%{transform:scale(1.4)}70%{transform:scale(0.95)}100%{transform:scale(1)}}
    @keyframes gradient-x{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}

    .animate-fade-up{animation:fadeUp .9s ease forwards}
    .animate-float{animation:float 7s ease-in-out infinite}
    .animate-float-slow{animation:floatSlow 9s ease-in-out infinite}
    .animate-shimmer{background:linear-gradient(90deg,transparent,rgba(200,164,107,.08),transparent);background-size:400% 100%;animation:shimmer 4s infinite}
    .animate-pulse-gold{animation:pulse-gold 3s ease-in-out infinite}
    .animate-spin-slow{animation:spin-slow 22s linear infinite}
    .animate-marquee{animation:marquee 28s linear infinite}
    .animate-gradient{background-size:200% 200%;animation:gradient-x 6s ease infinite}

    /* Scroll reveal */
    .reveal{opacity:0;transform:translateY(28px);transition:opacity .8s cubic-bezier(.4,0,.2,1),transform .8s cubic-bezier(.4,0,.2,1)}
    .reveal.visible{opacity:1;transform:translateY(0)}

    /* Nav */
    #navbar{transition:background .4s ease,box-shadow .4s ease,border-color .4s ease}
    #navbar.scrolled{background:rgba(6,6,8,.88)!important;backdrop-filter:blur(28px)!important;box-shadow:0 1px 0 rgba(200,164,107,.12)}
    .nav-link{position:relative;transition:color .25s}
    .nav-link::after{content:'';position:absolute;bottom:-3px;left:0;width:0;height:1px;background:linear-gradient(90deg,#C8A46B,#E8D0A0);transition:width .35s ease}
    .nav-link:hover::after,.nav-link.active::after{width:100%}

    /* Buttons */
    .btn-primary{position:relative;overflow:hidden;background:linear-gradient(135deg,#C8A46B,#b8944a);color:#060608;font-weight:600;letter-spacing:.05em;transition:all .35s ease}
    .btn-primary::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,#E8D0A0,#C8A46B);opacity:0;transition:opacity .35s}
    .btn-primary:hover::after{opacity:1}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 12px 32px rgba(200,164,107,.3)}
    .btn-primary:active{transform:scale(.98)}
    .btn-primary span{position:relative;z-index:1}
    .btn-ghost{border:1px solid rgba(200,164,107,.35);color:#C8A46B;background:transparent;transition:all .3s ease}
    .btn-ghost:hover{background:rgba(200,164,107,.08);border-color:rgba(200,164,107,.6);transform:translateY(-1px)}
    .btn-ghost:active{transform:scale(.98)}
    .btn-shine{position:relative;overflow:hidden}
    .btn-shine::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.12),transparent);transition:left .6s ease}
    .btn-shine:hover::before{left:100%}

    /* Product cards */
    .product-card{transition:transform .5s cubic-bezier(.4,0,.2,1),box-shadow .5s cubic-bezier(.4,0,.2,1),border-color .35s ease}
    .product-card:hover{transform:translateY(-10px);box-shadow:0 30px 60px rgba(0,0,0,.5),0 0 0 1px rgba(200,164,107,.2),0 8px 32px rgba(200,164,107,.1)}

    /* Category cards */
    .cat-card{transition:transform .5s cubic-bezier(.4,0,.2,1),box-shadow .5s ease}
    .cat-card:hover{transform:translateY(-6px);box-shadow:0 20px 40px rgba(0,0,0,.5)}
    .cat-card-img{transition:transform .7s cubic-bezier(.4,0,.2,1)}
    .cat-card:hover .cat-card-img{transform:scale(1.08)}

    /* Particles */
    .particle{position:absolute;width:2px;height:2px;background:rgba(200,164,107,.45);border-radius:50%;pointer-events:none}

    /* Copy toast */
    .copy-toast{position:fixed;top:84px;left:50%;transform:translateX(-50%) translateY(-10px);background:rgba(200,164,107,.95);color:#060608;padding:8px 22px;border-radius:40px;font-size:12px;font-weight:600;opacity:0;transition:all .3s;z-index:999;pointer-events:none}
    .copy-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

    /* Chat */
    .chat-bubble{max-width:82%}
    .typing-dot{width:6px;height:6px;border-radius:50%;background:#C8A46B;animation:pulse-gold .9s infinite}
    .typing-dot:nth-child(2){animation-delay:.18s}
    .typing-dot:nth-child(3){animation-delay:.36s}

    /* Mobile menu items */
    .mmenu-item{animation:menuSlide .5s ease forwards;opacity:0}
    .mmenu-item:nth-child(1){animation-delay:.08s}
    .mmenu-item:nth-child(2){animation-delay:.13s}
    .mmenu-item:nth-child(3){animation-delay:.18s}
    .mmenu-item:nth-child(4){animation-delay:.23s}
    .mmenu-item:nth-child(5){animation-delay:.28s}
    .mmenu-item:nth-child(6){animation-delay:.33s}
    .mmenu-item:nth-child(7){animation-delay:.38s}
    .mmenu-item:nth-child(8){animation-delay:.43s}

    /* Journal cards */
    .journal-card-img{transition:transform .7s cubic-bezier(.4,0,.2,1)}
    .journal-card:hover .journal-card-img{transform:scale(1.06)}

    /* Admin btn */
    #adminAccessBtn{position:fixed;bottom:8px;left:8px;z-index:40;opacity:.12;transition:opacity .3s}
    #adminAccessBtn:hover{opacity:.55}

    /* Wishlist panel items */
    .wishlist-item-img{transition:transform .4s ease}
    .wishlist-item:hover .wishlist-item-img{transform:scale(1.05)}

    @media(max-width:640px){
      .hero-h1{font-size:clamp(2.4rem,10vw,4rem)!important}
    }
  </style>
</head>
<body>

<!-- Copy Toast -->
<div id="copyToast" class="copy-toast">Link copied!</div>

<!-- Particle Container -->
<div id="particles" class="fixed inset-0 pointer-events-none z-0 overflow-hidden"></div>

<!-- Background glow orbs -->
<div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
  <div class="absolute -top-40 -right-40 w-[700px] h-[700px] rounded-full bg-gold/[0.03] blur-[120px]"></div>
  <div class="absolute top-1/2 -left-40 w-[500px] h-[500px] rounded-full bg-gold/[0.025] blur-[100px] animate-float" style="animation-delay:-4s"></div>
  <div class="absolute -bottom-40 right-1/3 w-[600px] h-[600px] rounded-full bg-gold/[0.02] blur-[120px] animate-float-slow" style="animation-delay:-7s"></div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- NAVIGATION                                                 -->
<!-- ═══════════════════════════════════════════════════════════ -->
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50" style="background:rgba(6,6,8,0);border-bottom:1px solid rgba(200,164,107,0)">
  <div class="max-w-7xl mx-auto px-5 sm:px-8">
    <div class="flex items-center justify-between h-[72px] sm:h-20">

      <!-- Logo -->
      <a href="/" class="flex items-center gap-3 group flex-shrink-0">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#C8A46B,#A07540)">
          <span class="font-display font-bold text-onyx text-sm leading-none">O</span>
        </div>
        <div class="leading-none">
          <span class="font-display text-[1.15rem] text-platinum tracking-wide">Onyx <span class="gold-text">&amp;</span> Outer</span>
        </div>
      </a>

      <!-- Desktop nav links -->
      <div class="hidden lg:flex items-center gap-7">
        <a href="#home" class="nav-link text-sm text-silver hover:text-platinum transition-colors">Home</a>
        <?php foreach($categories as $cat): if(!$cat['active'])continue; ?>
        <a href="#<?=e($cat['slug'])?>" class="nav-link text-sm text-silver hover:text-platinum transition-colors"><?=e($cat['name'])?></a>
        <?php if($cat['sort_order']>=4)break; endforeach; ?>
        <a href="/journal" class="nav-link text-sm text-silver hover:text-platinum transition-colors">Journal</a>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2 sm:gap-3">
        <button id="searchBtn" class="w-9 h-9 rounded-full flex items-center justify-center text-silver hover:text-gold hover:bg-white/5 transition-all" aria-label="Search">
          <i data-lucide="search" class="w-4.5 h-4.5"></i>
        </button>
        <button id="wishlistBtn" class="relative w-9 h-9 rounded-full flex items-center justify-center text-silver hover:text-gold hover:bg-white/5 transition-all" aria-label="Wishlist">
          <i data-lucide="heart" class="w-4.5 h-4.5"></i>
          <span id="wishCount" class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-gold text-onyx text-[9px] font-bold rounded-full flex items-center justify-center hidden">0</span>
        </button>
        <button id="menuBtn" class="lg:hidden w-9 h-9 rounded-full flex items-center justify-center text-silver hover:text-gold hover:bg-white/5 transition-all">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
      </div>
    </div>
  </div>
</nav>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- MOBILE MENU                                                -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div id="mobileMenu" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-onyx/97 backdrop-blur-2xl"></div>
  <div class="relative h-full flex flex-col items-center justify-center gap-5 py-16">
    <button id="closeMenu" class="absolute top-6 right-6 w-10 h-10 rounded-full glass flex items-center justify-center text-silver hover:text-gold transition-all hover:rotate-90">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>
    <!-- Gold wordmark -->
    <div class="absolute top-6 left-6 font-display text-lg text-gold/60">O&O</div>

    <a href="#home" class="mmenu-item font-display text-3xl text-platinum hover:text-gold transition-colors" onclick="closeMobileMenu()">Home</a>
    <?php foreach($categories as $cat): if(!$cat['active'])continue; ?>
    <a href="#<?=e($cat['slug'])?>" class="mmenu-item font-display text-3xl text-platinum hover:text-gold transition-colors" onclick="closeMobileMenu()"><?=e($cat['name'])?></a>
    <?php endforeach; ?>
    <a href="/journal" class="mmenu-item font-display text-3xl text-platinum hover:text-gold transition-colors">Journal</a>
    <a href="#about" class="mmenu-item font-display text-3xl text-platinum hover:text-gold transition-colors" onclick="closeMobileMenu()">About</a>
    <div class="mmenu-item flex gap-4 mt-4">
      <button onclick="closeMobileMenu();setTimeout(()=>{document.getElementById('searchBtn').click()},200)" class="w-12 h-12 rounded-xl glass flex items-center justify-center text-silver hover:text-gold transition-all">
        <i data-lucide="search" class="w-5 h-5"></i>
      </button>
      <button onclick="closeMobileMenu();setTimeout(()=>{document.getElementById('wishlistBtn').click()},200)" class="relative w-12 h-12 rounded-xl glass flex items-center justify-center text-silver hover:text-gold transition-all">
        <i data-lucide="heart" class="w-5 h-5"></i>
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- SEARCH MODAL                                               -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div id="searchModal" class="fixed inset-0 z-[70] hidden">
  <div class="absolute inset-0 bg-onyx/96 backdrop-blur-2xl"></div>
  <div class="relative h-full flex items-start justify-center pt-28 sm:pt-36 px-5">
    <div class="w-full max-w-2xl">
      <div class="flex items-center gap-4 border-b border-gold/25 pb-5 mb-8">
        <i data-lucide="search" class="w-6 h-6 text-gold flex-shrink-0"></i>
        <input id="searchInput" type="text" placeholder="Search luxury products..."
               class="flex-1 bg-transparent text-2xl sm:text-3xl font-display text-platinum placeholder-silver/40 outline-none">
        <button id="closeSearch" class="w-9 h-9 rounded-full glass flex items-center justify-center text-silver hover:text-gold transition-all hover:rotate-90">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
      <div id="searchResults" class="space-y-2 max-h-[50vh] overflow-y-auto"></div>
      <div class="mt-8">
        <p class="text-[10px] text-silver/40 uppercase tracking-[.2em] mb-4">Popular</p>
        <div class="flex flex-wrap gap-2">
          <?php foreach($categories as $cat): if(!$cat['active'])continue; ?>
          <span class="quick-search px-4 py-1.5 glass-light rounded-full text-xs text-silver/70 cursor-pointer hover:text-gold hover:border-gold/30 transition-all"><?=e($cat['name'])?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- MAIN CONTENT                                               -->
<!-- ═══════════════════════════════════════════════════════════ -->
<main id="mainContent" class="relative z-10">

<!-- ─── HERO ─────────────────────────────────────────────── -->
<section id="home" class="relative min-h-screen flex flex-col justify-center pt-20 pb-10 overflow-hidden">
  <!-- Decorative rings -->
  <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
    <div class="w-[900px] h-[900px] rounded-full border border-gold/[0.04] animate-spin-slow opacity-60"></div>
    <div class="absolute w-[600px] h-[600px] rounded-full border border-gold/[0.06]" style="animation:spin-slow 14s linear infinite reverse;opacity:.5"></div>
    <div class="absolute w-[350px] h-[350px] rounded-full border border-gold/[0.08]" style="animation:spin-slow 9s linear infinite;opacity:.4"></div>
  </div>

  <div class="relative max-w-7xl mx-auto px-5 sm:px-8 w-full">
    <div class="max-w-3xl">
      <!-- Label badge -->
      <div class="inline-flex items-center gap-2.5 glass px-4 py-2 rounded-full mb-8 animate-fade-up" style="animation-delay:.1s">
        <span class="w-1.5 h-1.5 rounded-full bg-gold animate-pulse-gold"></span>
        <span class="text-[11px] text-gold/80 uppercase tracking-[.22em]">Exclusive Collection 2025</span>
      </div>

      <!-- Heading -->
      <h1 class="hero-h1 font-display font-bold text-platinum leading-[1.06] mb-7 animate-fade-up"
          style="font-size:clamp(3rem,8vw,5.5rem);animation-delay:.2s">
        <?=e($settings['hero_heading']??'Curated Luxury')?><br>
        <em class="gold-text not-italic font-normal" style="font-family:'Cormorant Garamond',serif;font-style:italic">Beyond Ordinary</em>
      </h1>

      <!-- Subtext -->
      <p class="text-silver/70 text-base sm:text-lg leading-relaxed max-w-xl mb-10 animate-fade-up font-light" style="animation-delay:.35s">
        <?=e($settings['hero_subtext']??'Discover handpicked premium accessories from the world\'s finest brands.')?>
      </p>

      <!-- CTAs -->
      <div class="flex flex-wrap gap-4 animate-fade-up" style="animation-delay:.5s">
        <button onclick="document.querySelector('#categories-section').scrollIntoView({behavior:'smooth'})"
                class="btn-primary btn-shine px-7 py-3.5 rounded-xl text-sm font-semibold tracking-wide uppercase flex items-center gap-2">
          <span><?=e($settings['cta_text']??'Explore Collection')?></span>
          <i data-lucide="arrow-right" class="w-4 h-4 relative z-10"></i>
        </button>
        <a href="/journal" class="btn-ghost btn-shine px-7 py-3.5 rounded-xl text-sm font-medium tracking-wide uppercase flex items-center gap-2">
          Read The Journal
          <i data-lucide="book-open" class="w-4 h-4"></i>
        </a>
      </div>
    </div>

    <!-- Stats row -->
    <div class="flex flex-wrap items-center gap-8 sm:gap-14 mt-20 animate-fade-up" style="animation-delay:.7s">
      <div>
        <p class="font-display text-3xl sm:text-4xl text-platinum"><?=e($settings['stats_products']??'500+')?></p>
        <p class="text-[11px] text-silver/50 uppercase tracking-[.18em] mt-1">Curated Products</p>
      </div>
      <div class="h-10 w-px bg-gold/15 hidden sm:block"></div>
      <div>
        <p class="font-display text-3xl sm:text-4xl text-platinum"><?=e($settings['stats_brands']??'50+')?></p>
        <p class="text-[11px] text-silver/50 uppercase tracking-[.18em] mt-1">Premium Brands</p>
      </div>
      <div class="h-10 w-px bg-gold/15 hidden sm:block"></div>
      <div>
        <p class="font-display text-3xl sm:text-4xl text-platinum"><?=e($settings['stats_clients']??'10K+')?></p>
        <p class="text-[11px] text-silver/50 uppercase tracking-[.18em] mt-1">Happy Members</p>
      </div>
    </div>
  </div>

  <!-- Scroll indicator -->
  <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 opacity-40 animate-float-slow">
    <p class="text-[10px] text-silver uppercase tracking-[.2em]">Scroll</p>
    <div class="w-px h-8 bg-gradient-to-b from-gold to-transparent"></div>
  </div>
</section>

<!-- ─── BRAND MARQUEE ─────────────────────────────────────── -->
<div class="border-y border-gold/8 py-5 overflow-hidden bg-obsidian/30 relative z-10">
  <div class="flex animate-marquee whitespace-nowrap select-none" aria-hidden="true">
    <?php
    $brands=['ROLEX','DIOR','HERMÈS','MONT BLANC','CARTIER','TOM FORD','PATEK PHILIPPE','LOUIS VUITTON','BURBERRY','PRADA','GUCCI','CHANEL'];
    $marqueeItems='';
    foreach($brands as $b) $marqueeItems.="<span class=\"inline-flex items-center gap-6 px-8 text-[11px] text-silver/30 uppercase tracking-[.28em] font-medium\">{$b}<span class=\"text-gold/20 text-lg\">◆</span></span>";
    echo str_repeat($marqueeItems,2); // repeat for seamless loop
    ?>
  </div>
</div>

<!-- ─── CATEGORIES ────────────────────────────────────────── -->
<section id="categories-section" class="py-24 sm:py-32 px-5 sm:px-8 max-w-7xl mx-auto">
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-14 reveal">
    <div>
      <p class="text-[11px] text-gold/70 uppercase tracking-[.22em] mb-3">Browse By Category</p>
      <h2 class="font-display text-3xl sm:text-4xl text-platinum">Premium Collections</h2>
    </div>
    <p class="text-silver/50 text-sm mt-3 sm:mt-0 max-w-xs text-right hidden sm:block">
      Discover our hand-curated selection across every category
    </p>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
    <?php foreach($categories as $i=>$cat): if(!$cat['active'])continue; ?>
    <div class="cat-card glass-card rounded-2xl overflow-hidden cursor-pointer group reveal"
         id="<?=e($cat['slug'])?>"
         style="transition-delay:<?=$i*.08?>s"
         onclick="filterByCategory(<?=$cat['id']?>,'<?=e($cat['name'])?>')">
      <div class="relative h-40 sm:h-48 overflow-hidden bg-graphite">
        <?php if($cat['thumbnail']): ?>
        <img src="<?=e($cat['thumbnail'])?>" alt="<?=e($cat['name'])?>" class="cat-card-img w-full h-full object-cover">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-graphite to-obsidian">
          <span class="text-gold/20 text-5xl font-garamond"><?=substr(e($cat['name']),0,1)?></span>
        </div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-onyx/80 via-onyx/15 to-transparent"></div>
        <div class="absolute inset-0 bg-gold/0 group-hover:bg-gold/5 transition-colors duration-500"></div>
      </div>
      <div class="px-4 py-4">
        <h3 class="font-display text-sm text-platinum group-hover:text-gold transition-colors"><?=e($cat['name'])?></h3>
        <?php if(!empty($cat['subtitle'])): ?>
        <p class="text-[11px] text-silver/45 mt-1 truncate"><?=e($cat['subtitle'])?></p>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ─── PRODUCTS ──────────────────────────────────────────── -->
<section id="trending-section" class="py-24 sm:py-32 px-5 sm:px-8 max-w-7xl mx-auto">
  <div class="flex items-end justify-between mb-14 reveal">
    <div>
      <p class="text-[11px] text-gold/70 uppercase tracking-[.22em] mb-3">Handpicked For You</p>
      <h2 class="font-display text-3xl sm:text-4xl text-platinum" id="trendingTitle">Trending Now</h2>
    </div>
    <button id="viewAllBtn" onclick="clearFilter()" class="text-gold text-sm hidden sm:flex items-center gap-1.5 hover:gap-3 transition-all group">
      View All <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
    </button>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7" id="productGrid">
    <?php foreach($trending as $i=>$p):
      $img='';
      foreach($p['images'] as $im){ if($im['is_primary']){$img=$im['image_path'];break;} }
      if(!$img && !empty($p['images'])) $img=$p['images'][0]['image_path'];
      if(!$img) $img='https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&h=600&fit=crop';
    ?>
    <div class="product-card glass-card rounded-2xl overflow-hidden group cursor-pointer reveal border border-gold/[0.09]"
         style="transition-delay:<?=$i*.09?>s"
         onclick="openProductModal(<?=$p['id']?>)">
      <!-- Image -->
      <div class="relative h-72 overflow-hidden bg-graphite">
        <img src="<?=e($img)?>" alt="<?=e($p['name'])?>"
             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-108" loading="lazy"
             style="transition:transform .7s cubic-bezier(.4,0,.2,1)">
        <!-- Gradient overlay (always) -->
        <div class="absolute inset-0 bg-gradient-to-t from-onyx/60 via-transparent to-transparent"></div>
        <!-- Hover action overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-onyx/90 via-onyx/40 to-onyx/10 opacity-0 group-hover:opacity-100 transition-all duration-400 flex flex-col items-center justify-end pb-5 gap-2.5 px-4">
          <button onclick="event.stopPropagation();openProductModal(<?=$p['id']?>)"
                  class="btn-primary btn-shine w-full py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2">
            <span>View Details</span>
            <i data-lucide="eye" class="w-3.5 h-3.5 relative z-10"></i>
          </button>
          <a href="<?=e($p['affiliate_url']??'#')?>" target="_blank" rel="noopener noreferrer sponsored"
             onclick="event.stopPropagation()"
             class="btn-ghost w-full py-2.5 rounded-xl text-xs font-medium tracking-wider flex items-center justify-center gap-2">
            Shop Now <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
          </a>
        </div>
        <!-- Top actions -->
        <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-2 group-hover:translate-x-0">
          <button onclick="event.stopPropagation();toggleWishlist(<?=$p['id']?>)"
                  class="wishlist-btn-<?=$p['id']?> w-8 h-8 rounded-full glass flex items-center justify-center hover:scale-110 transition-transform">
            <i data-lucide="heart" class="w-3.5 h-3.5 text-silver"></i>
          </button>
          <button onclick="event.stopPropagation();copyProductLink(<?=$p['id']?>,this)"
                  class="w-8 h-8 rounded-full glass flex items-center justify-center hover:scale-110 transition-transform">
            <i data-lucide="link" class="w-3.5 h-3.5 text-silver"></i>
          </button>
        </div>
        <!-- Category badge -->
        <div class="absolute top-3 left-3">
          <span class="px-2.5 py-1 glass text-[10px] text-gold/80 uppercase tracking-wider rounded-full"><?=e($p['category_name']??'')?></span>
        </div>
      </div>
      <!-- Info -->
      <div class="px-5 py-5">
        <h3 class="font-display text-[0.95rem] text-platinum group-hover:text-gold transition-colors mb-1.5 line-clamp-1"><?=e($p['name'])?></h3>
        <p class="text-[12px] text-silver/50 line-clamp-2 leading-relaxed mb-3"><?=e($p['description'])?></p>
        <div class="flex items-center justify-between">
          <span class="text-[11px] text-gold/60 uppercase tracking-widest font-medium">Discover More</span>
          <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gold/60 transition-transform group-hover:translate-x-1.5"></i>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Load more -->
  <div class="text-center mt-12 reveal">
    <button onclick="document.querySelector('#categories-section').scrollIntoView({behavior:'smooth'})"
            class="btn-ghost btn-shine px-8 py-3.5 rounded-xl text-sm font-medium tracking-wide inline-flex items-center gap-2">
      Browse All Collections <i data-lucide="grid" class="w-4 h-4"></i>
    </button>
  </div>
</section>

<!-- ─── WHY CHOOSE US ─────────────────────────────────────── -->
<section id="about" class="py-24 sm:py-32 px-5 sm:px-8 max-w-7xl mx-auto">
  <div class="text-center mb-16 reveal">
    <p class="text-[11px] text-gold/70 uppercase tracking-[.22em] mb-3">Our Promise</p>
    <h2 class="font-display text-3xl sm:text-4xl text-platinum">Why Onyx &amp; Outer</h2>
    <?php if(!empty($settings['about_text'])): ?>
    <p class="text-silver/55 max-w-xl mx-auto mt-5 text-sm leading-relaxed"><?=e($settings['about_text'])?></p>
    <?php endif; ?>
  </div>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
    <?php
    $features=[
      ['shield-check','Authenticated','Every product verified from authorised retailers and trusted sources'],
      ['gem','Curated','Hand-selected by our team of luxury experts and editorial professionals'],
      ['sparkles','Exclusive','Rare finds and limited pieces you won\'t discover elsewhere'],
      ['heart','Trusted',($settings['stats_clients']??'10,000+').' satisfied luxury enthusiasts worldwide'],
    ];
    foreach($features as $i=>[$icon,$title,$desc]): ?>
    <div class="glass-card rounded-2xl p-6 sm:p-8 text-center group hover:border-gold/25 transition-colors duration-500 reveal" style="transition-delay:<?=$i*.1?>s">
      <div class="w-11 h-11 mx-auto mb-5 rounded-xl border border-gold/25 flex items-center justify-center group-hover:bg-gold/8 transition-colors duration-400">
        <i data-lucide="<?=$icon?>" class="w-5 h-5 text-gold"></i>
      </div>
      <h3 class="font-display text-sm text-platinum mb-2"><?=$title?></h3>
      <p class="text-[12px] text-silver/50 leading-relaxed"><?=$desc?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ─── JOURNAL ───────────────────────────────────────────── -->
<section id="journal" class="py-24 sm:py-32 px-5 sm:px-8 max-w-7xl mx-auto">
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-14 reveal">
    <div>
      <p class="text-[11px] text-gold/70 uppercase tracking-[.22em] mb-3">Editorial</p>
      <h2 class="font-display text-3xl sm:text-4xl text-platinum"><?=e($settings['journal_title']??'The Onyx Journal')?></h2>
    </div>
    <a href="/journal" class="mt-4 sm:mt-0 text-gold text-sm flex items-center gap-1.5 hover:gap-3 transition-all group">
      All Articles <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
    </a>
  </div>

  <?php if(!empty($journalPosts)): ?>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-7">
    <?php foreach($journalPosts as $i=>$post): ?>
    <a href="/journal/<?=e($post['slug'])?>" class="journal-card glass-card rounded-2xl overflow-hidden group hover:border-gold/25 transition-all duration-500 reveal block" style="transition-delay:<?=$i*.1?>s">
      <div class="relative h-52 overflow-hidden bg-graphite">
        <?php if(!empty($post['cover_image'])): ?>
        <img src="<?=e($post['cover_image'])?>" alt="<?=e($post['title'])?>" class="journal-card-img w-full h-full object-cover" loading="lazy">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-graphite to-obsidian">
          <i data-lucide="book-open" class="w-10 h-10 text-gold/20"></i>
        </div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-onyx/60 to-transparent"></div>
        <span class="absolute top-3 left-3 px-2.5 py-1 glass text-[10px] text-gold/80 uppercase tracking-wider rounded-full"><?=e($post['category']??'Article')?></span>
      </div>
      <div class="px-5 py-5">
        <h3 class="font-display text-sm text-platinum group-hover:text-gold transition-colors mb-2 line-clamp-2 leading-snug"><?=e($post['title'])?></h3>
        <?php if(!empty($post['excerpt'])): ?>
        <p class="text-[12px] text-silver/50 line-clamp-2 leading-relaxed mb-3"><?=e($post['excerpt'])?></p>
        <?php endif; ?>
        <span class="text-[11px] text-gold/60 flex items-center gap-1.5">Read Article <i data-lucide="arrow-right" class="w-3 h-3"></i></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <!-- Editorial banner fallback -->
  <div class="glass-card rounded-2xl px-8 sm:px-16 py-16 sm:py-24 text-center relative overflow-hidden reveal">
    <div class="absolute inset-0 animate-shimmer"></div>
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 50% 0%,rgba(200,164,107,.06),transparent 70%)"></div>
    <div class="relative">
      <div class="w-12 h-12 mx-auto mb-6 rounded-xl border border-gold/25 flex items-center justify-center">
        <i data-lucide="book-open" class="w-5 h-5 text-gold"></i>
      </div>
      <p class="text-[11px] text-gold/70 uppercase tracking-[.22em] mb-4">The Onyx Journal</p>
      <h2 class="font-display text-3xl sm:text-4xl text-platinum mb-4"><?=e($settings['journal_title']??'Luxury Buying Guides')?></h2>
      <p class="text-silver/55 max-w-md mx-auto mb-9 text-sm leading-relaxed"><?=e($settings['journal_subtitle']??'Expert curation, honest reviews, and insider knowledge to help you discover the finest luxury products.')?></p>
      <a href="/journal" class="btn-ghost btn-shine inline-flex px-8 py-3.5 rounded-xl text-sm font-medium uppercase tracking-wide items-center gap-2">
        Read The Journal <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- ─── REVIEWS ───────────────────────────────────────────── -->
<?php if(!empty($reviews)): ?>
<section class="py-24 sm:py-32 px-5 sm:px-8 max-w-7xl mx-auto">
  <div class="text-center mb-14 reveal">
    <p class="text-[11px] text-gold/70 uppercase tracking-[.22em] mb-3">Social Proof</p>
    <h2 class="font-display text-3xl sm:text-4xl text-platinum">What Our Members Say</h2>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
    <?php foreach($reviews as $i=>$rev): ?>
    <div class="glass-card rounded-2xl p-7 sm:p-8 reveal group hover:border-gold/25 transition-all duration-500" style="transition-delay:<?=$i*.1?>s">
      <!-- Quote mark -->
      <div class="text-4xl font-garamond text-gold/15 leading-none mb-4" aria-hidden="true">&ldquo;</div>
      <!-- Stars -->
      <div class="flex gap-0.5 mb-4">
        <?php for($s=0;$s<$rev['rating'];$s++): ?><span class="text-gold text-sm">★</span><?php endfor; ?>
        <?php for($s=$rev['rating'];$s<5;$s++): ?><span class="text-silver/20 text-sm">★</span><?php endfor; ?>
      </div>
      <!-- Text -->
      <p class="text-silver/70 text-sm leading-relaxed mb-6 italic">"<?=e($rev['review_text'])?>"</p>
      <!-- Reviewer -->
      <div>
        <p class="text-platinum text-sm font-medium"><?=e($rev['reviewer_name'])?></p>
        <p class="text-silver/40 text-[11px] mt-0.5"><?=e($rev['reviewer_title']??'')?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ─── NEWSLETTER ────────────────────────────────────────── -->
<section class="py-24 sm:py-32 px-5 sm:px-8 max-w-7xl mx-auto reveal" id="newsletter-section">
  <div class="relative rounded-3xl overflow-hidden" style="background:linear-gradient(135deg,rgba(26,26,30,0.95),rgba(20,20,24,0.98));border:1px solid rgba(200,164,107,0.12)">
    <div class="absolute inset-0 animate-shimmer pointer-events-none"></div>
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-px bg-gradient-to-r from-transparent via-gold/40 to-transparent"></div>
    <div class="relative px-8 sm:px-16 py-16 sm:py-20 text-center">
      <div class="w-10 h-10 mx-auto mb-6 rounded-xl border border-gold/25 flex items-center justify-center">
        <i data-lucide="mail" class="w-4 h-4 text-gold"></i>
      </div>
      <h2 class="font-display text-3xl sm:text-4xl text-platinum mb-3"><?=e($settings['newsletter_title']??'Join The Inner Circle')?></h2>
      <p class="text-silver/55 text-sm max-w-sm mx-auto mb-9"><?=e($settings['newsletter_subtitle']??'Exclusive drops, luxury guides, and curated recommendations.')?></p>
      <form id="newsletterForm" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
        <input type="email" placeholder="Your email address" required
               class="flex-1 px-5 py-3.5 bg-onyx/60 border border-gold/20 rounded-xl text-sm text-platinum placeholder-silver/35 outline-none focus:border-gold/45 transition-colors">
        <button type="submit" class="btn-primary btn-shine px-7 py-3.5 rounded-xl text-sm font-semibold uppercase tracking-wider">
          <span>Subscribe</span>
        </button>
      </form>
      <div id="newsletterMsg" class="mt-5 text-sm text-gold hidden">✓ Welcome to the inner circle!</div>
      <p class="text-[11px] text-silver/30 mt-4">No spam. Unsubscribe any time.</p>
    </div>
  </div>
</section>

<!-- ─── FOOTER ────────────────────────────────────────────── -->
<footer class="border-t border-gold/8 pt-16 pb-8 px-5 sm:px-8">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-14">
      <!-- Brand col -->
      <div class="col-span-2 md:col-span-1">
        <a href="/" class="flex items-center gap-2.5 mb-5 group">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#C8A46B,#A07540)">
            <span class="font-display font-bold text-onyx text-xs">O</span>
          </div>
          <span class="font-display text-platinum text-base">Onyx <span class="gold-text">&amp;</span> Outer</span>
        </a>
        <p class="text-silver/45 text-[12px] leading-relaxed max-w-[200px] mb-6"><?=e($settings['footer_tagline']??'Curated Luxury Beyond Ordinary')?></p>
        <div class="flex gap-2.5">
          <?php
          $siMap=['instagram'=>'instagram','twitter'=>'twitter','facebook'=>'facebook','youtube'=>'youtube','tiktok'=>'music','pinterest'=>'pinterest'];
          $hasSocial=false;
          foreach($siMap as $p=>$ic):
            if(empty($socialMap[$p])||$socialMap[$p]==='#') continue;
            $hasSocial=true;
          ?>
          <a href="<?=e($socialMap[$p])?>" target="_blank" rel="noopener"
             class="w-8 h-8 rounded-full border border-gold/15 flex items-center justify-center hover:bg-gold/10 hover:border-gold/35 transition-all">
            <i data-lucide="<?=$ic?>" class="w-3.5 h-3.5 text-silver/60"></i>
          </a>
          <?php endforeach;
          if(!$hasSocial): foreach(['instagram','twitter','facebook'] as $p): ?>
          <a href="#" class="w-8 h-8 rounded-full border border-gold/15 flex items-center justify-center hover:bg-gold/10 hover:border-gold/35 transition-all">
            <i data-lucide="<?=$p?>" class="w-3.5 h-3.5 text-silver/60"></i>
          </a>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Collections -->
      <div>
        <h4 class="font-display text-[13px] text-platinum mb-5">Collections</h4>
        <ul class="space-y-3">
          <?php foreach($categories as $cat): if(!$cat['active'])continue; ?>
          <li><a href="#<?=e($cat['slug'])?>" class="text-[12px] text-silver/50 hover:text-gold transition-colors"><?=e($cat['name'])?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Company -->
      <div>
        <h4 class="font-display text-[13px] text-platinum mb-5">Company</h4>
        <ul class="space-y-3">
          <li><a href="#about" class="text-[12px] text-silver/50 hover:text-gold transition-colors">About Us</a></li>
          <li><a href="/journal" class="text-[12px] text-silver/50 hover:text-gold transition-colors">Journal</a></li>
          <li><a href="/page/contact" class="text-[12px] text-silver/50 hover:text-gold transition-colors">Contact</a></li>
          <li><a href="/page/faq" class="text-[12px] text-silver/50 hover:text-gold transition-colors">FAQ</a></li>
        </ul>
      </div>

      <!-- Legal -->
      <div>
        <h4 class="font-display text-[13px] text-platinum mb-5">Legal</h4>
        <ul class="space-y-3">
          <li><a href="/page/privacy-policy" class="text-[12px] text-silver/50 hover:text-gold transition-colors">Privacy Policy</a></li>
          <li><a href="/page/terms-conditions" class="text-[12px] text-silver/50 hover:text-gold transition-colors">Terms &amp; Conditions</a></li>
          <li><a href="/page/affiliate-disclosure" class="text-[12px] text-silver/50 hover:text-gold transition-colors">Affiliate Disclosure</a></li>
          <li><a href="/page/editorial-policy" class="text-[12px] text-silver/50 hover:text-gold transition-colors">Editorial Policy</a></li>
        </ul>
      </div>
    </div>

    <div class="border-t border-gold/8 pt-7 flex flex-col sm:flex-row items-center justify-between gap-3">
      <p class="text-[11px] text-silver/30">© <?=date('Y')?> Onyx &amp; Outer. All rights reserved.</p>
      <p class="text-[11px] text-silver/25">Curated luxury, affiliate commissions disclosed.</p>
    </div>
  </div>
</footer>

</main><!-- /main -->

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- AI CONCIERGE CHAT                                          -->
<!-- ═══════════════════════════════════════════════════════════ -->
<button id="chatToggle" class="fixed bottom-6 right-6 z-50 w-13 h-13 w-[52px] h-[52px] rounded-full shadow-xl shadow-gold/25 flex items-center justify-center hover:scale-110 transition-transform"
        style="background:linear-gradient(135deg,#C8A46B,#a07540)">
  <i data-lucide="message-circle" class="w-5 h-5 text-onyx"></i>
</button>

<div id="chatPanel" class="fixed bottom-24 right-6 z-50 w-[340px] sm:w-[380px] glass rounded-2xl overflow-hidden flex-col shadow-2xl shadow-black/60" style="display:none;max-height:500px">
  <div class="px-4 py-3.5 border-b border-gold/10 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background:linear-gradient(135deg,#C8A46B,#a07540)">
        <span class="text-onyx text-[10px] font-bold">AI</span>
      </div>
      <div>
        <p class="text-[13px] text-platinum font-medium">Onyx Concierge</p>
        <p class="text-[10px] text-emerald-400" id="chatStatus">Online · Ready to help</p>
      </div>
    </div>
    <button id="chatClose" class="text-silver/60 hover:text-gold transition-colors">
      <i data-lucide="x" class="w-4 h-4"></i>
    </button>
  </div>
  <div id="chatMessages" class="flex-1 p-4 space-y-3 overflow-y-auto" style="max-height:320px">
    <div class="chat-bubble bg-graphite/80 rounded-xl rounded-tl-none p-3">
      <p class="text-[12px] text-silver/80">Welcome to Onyx &amp; Outer. I'm your luxury concierge. How may I assist you today?</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <button class="quick-reply px-3 py-1.5 glass-light rounded-full text-[10px] text-gold hover:bg-gold/10 transition-all">Recommend a watch</button>
      <button class="quick-reply px-3 py-1.5 glass-light rounded-full text-[10px] text-gold hover:bg-gold/10 transition-all">Gift ideas</button>
      <button class="quick-reply px-3 py-1.5 glass-light rounded-full text-[10px] text-gold hover:bg-gold/10 transition-all">Best perfumes</button>
    </div>
  </div>
  <div class="p-3 border-t border-gold/10">
    <form id="chatForm" class="flex gap-2">
      <input id="chatInput" type="text" placeholder="Ask anything..." maxlength="500"
             class="flex-1 px-3 py-2 bg-onyx/60 border border-gold/15 rounded-lg text-[12px] text-platinum placeholder-silver/35 outline-none focus:border-gold/35 transition-colors">
      <button type="submit" class="w-9 h-9 rounded-lg bg-gold/15 hover:bg-gold/30 flex items-center justify-center transition-all hover:scale-105">
        <i data-lucide="send" class="w-3.5 h-3.5 text-gold"></i>
      </button>
    </form>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- WISHLIST PANEL                                             -->
<!-- ═══════════════════════════════════════════════════════════ -->
<aside id="wishlistPanel" class="fixed top-0 right-0 bottom-0 z-[60] w-[320px] sm:w-[360px] glass flex flex-col"
       style="transform:translateX(100%);transition:transform .4s cubic-bezier(.4,0,.2,1);border-left:1px solid rgba(200,164,107,0.12)">
  <div class="flex items-center justify-between px-6 py-5 border-b border-gold/10 flex-shrink-0">
    <div>
      <h3 class="font-display text-[1.05rem] text-platinum">My Wishlist</h3>
      <p class="text-[11px] text-silver/40 mt-0.5">Saved luxury pieces</p>
    </div>
    <button id="closeWishlist" class="w-8 h-8 rounded-full glass-light flex items-center justify-center text-silver/60 hover:text-gold transition-colors">
      <i data-lucide="x" class="w-4 h-4"></i>
    </button>
  </div>
  <div id="wishlistItems" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
    <div class="flex items-center justify-center py-14">
      <div class="text-center">
        <i data-lucide="heart" class="w-8 h-8 text-gold/20 mx-auto mb-3"></i>
        <p class="text-[13px] text-silver/40">Your wishlist is empty</p>
        <p class="text-[11px] text-silver/25 mt-1">Hover over products to save them</p>
      </div>
    </div>
  </div>
  <div class="px-5 py-4 border-t border-gold/10 flex-shrink-0">
    <button onclick="document.getElementById('wishlistPanel').style.transform='translateX(100%)';document.querySelector('#categories-section').scrollIntoView({behavior:'smooth'})"
            class="btn-primary btn-shine w-full py-3 rounded-xl text-xs font-bold uppercase tracking-widest text-center">
      <span>Explore Collection</span>
    </button>
  </div>
</aside>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- PRODUCT DETAIL MODAL                                       -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div id="productModal" class="fixed inset-0 z-[80] hidden">
  <div class="absolute inset-0 bg-onyx/90 backdrop-blur-xl" onclick="closeProductModal()"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
    <div class="w-full max-w-xl glass-card rounded-2xl overflow-hidden pointer-events-auto" style="animation:scaleIn .4s cubic-bezier(.34,1.56,.64,1)">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gold/10">
        <h2 id="modalProductName" class="font-display text-lg sm:text-xl text-platinum pr-4"></h2>
        <button onclick="closeProductModal()" class="flex-shrink-0 w-8 h-8 rounded-full glass-light flex items-center justify-center text-silver/60 hover:text-gold transition-colors">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
      <div class="p-5 sm:p-6 max-h-[72vh] overflow-y-auto">
        <div class="relative rounded-xl overflow-hidden mb-6 bg-graphite" style="height:280px">
          <img id="modalProductImage" src="" alt="Product" class="w-full h-full object-cover transition-opacity duration-200">
          <button id="prevImage" onclick="prevProductImage()" class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full glass flex items-center justify-center text-gold transition-all hover:scale-110 hidden">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
          </button>
          <button id="nextImage" onclick="nextProductImage()" class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full glass flex items-center justify-center text-gold transition-all hover:scale-110 hidden">
            <i data-lucide="chevron-right" class="w-5 h-5"></i>
          </button>
          <div id="imageCounter" class="absolute bottom-3 right-3 px-3 py-1 bg-onyx/70 rounded-full text-[11px] text-gold/80 hidden">1/1</div>
        </div>
        <p id="modalProductDesc" class="text-silver/70 text-sm leading-relaxed mb-6"></p>
        <div class="flex gap-3">
          <a id="modalProductLink" href="#" target="_blank" rel="noopener noreferrer sponsored"
             class="btn-primary btn-shine flex-1 inline-flex items-center gap-2 px-5 py-3.5 rounded-xl text-sm font-bold uppercase tracking-wider justify-center">
            <span>Visit Store</span><i data-lucide="external-link" class="w-4 h-4 relative z-10"></i>
          </a>
          <button id="modalCopyLink" onclick="copyProductLink(null,this)"
                  class="btn-ghost px-4 py-3.5 rounded-xl flex items-center gap-2 text-sm">
            <i data-lucide="link" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Admin access (very low opacity) -->
<button id="adminAccessBtn" onclick="window.location='/admin/'" title="Admin">
  <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
  </svg>
</button>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- SCRIPTS                                                    -->
<!-- ═══════════════════════════════════════════════════════════ -->
<script>
// ── State ──────────────────────────────────────────────────────
let currentProduct=null, currentImageIndex=0;
let wishlist=JSON.parse(localStorage.getItem('onyx_wishlist')||'[]');
let chatHistory=[];
const SITE_URL=<?=json_encode(SITE_URL)?>;

// ── Utilities ──────────────────────────────────────────────────
function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function observeReveals(){
  const io=new IntersectionObserver(entries=>{
    entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');io.unobserve(e.target);}});
  },{threshold:.08});
  document.querySelectorAll('.reveal:not(.visible)').forEach(el=>io.observe(el));
}

// ── Particles ──────────────────────────────────────────────────
function createParticles(){
  const c=document.getElementById('particles');
  for(let i=0;i<35;i++){
    const p=document.createElement('div');
    p.className='particle';
    const size=Math.random()<0.3?3:2;
    p.style.cssText=`left:${Math.random()*100}%;top:${Math.random()*100}%;width:${size}px;height:${size}px;opacity:${.15+Math.random()*.35};animation:float ${4+Math.random()*6}s ease-in-out infinite;animation-delay:${Math.random()*5}s`;
    c.appendChild(p);
  }
}

// ── Three.js background ────────────────────────────────────────
function init3D(){
  try{
    const el=document.querySelector('body');
    if(!el||el.querySelector('#canvas3d'))return;
    const scene=new THREE.Scene(),camera=new THREE.PerspectiveCamera(75,innerWidth/innerHeight,.1,2000);
    const renderer=new THREE.WebGLRenderer({antialias:true,alpha:true});
    renderer.setSize(innerWidth,innerHeight);renderer.setClearColor(0x000000,0);
    renderer.domElement.id='canvas3d';
    renderer.domElement.style.cssText='position:fixed;top:0;left:0;z-index:0;pointer-events:none';
    el.insertBefore(renderer.domElement,el.firstChild);
    const geo=new THREE.BufferGeometry();
    const N=100,pos=new Float32Array(N*3),vel=new Float32Array(N*3);
    for(let i=0;i<N*3;i+=3){pos[i]=(Math.random()-.5)*900;pos[i+1]=(Math.random()-.5)*900;pos[i+2]=(Math.random()-.5)*900;vel[i]=(Math.random()-.5)*1.5;vel[i+1]=(Math.random()-.5)*1.5;vel[i+2]=(Math.random()-.5)*1.5;}
    geo.setAttribute('position',new THREE.BufferAttribute(pos,3));geo.userData.velocity=vel;
    const mat=new THREE.PointsMaterial({size:2.5,color:0xC8A46B,sizeAttenuation:true,transparent:true,opacity:.18});
    const pts=new THREE.Points(geo,mat);scene.add(pts);camera.position.z=180;
    (function anim(){requestAnimationFrame(anim);pts.rotation.x+=.00004;pts.rotation.y+=.00007;
      const p=geo.attributes.position.array,v=geo.userData.velocity;
      for(let i=0;i<p.length;i+=3){p[i]+=v[i];p[i+1]+=v[i+1];p[i+2]+=v[i+2];if(Math.abs(p[i])>450)v[i]*=-1;if(Math.abs(p[i+1])>450)v[i+1]*=-1;if(Math.abs(p[i+2])>450)v[i+2]*=-1;}
      geo.attributes.position.needsUpdate=true;renderer.render(scene,camera);})();
    addEventListener('resize',()=>{camera.aspect=innerWidth/innerHeight;camera.updateProjectionMatrix();renderer.setSize(innerWidth,innerHeight);});
  }catch(e){}
}

// ── Navbar scroll effect ───────────────────────────────────────
(function(){
  const nav=document.getElementById('navbar');
  let ticking=false;
  window.addEventListener('scroll',()=>{
    if(!ticking){requestAnimationFrame(()=>{
      nav.classList.toggle('scrolled',window.scrollY>40);
      ticking=false;
    });ticking=true;}
  },{passive:true});
})();

// ── Product Modal ──────────────────────────────────────────────
async function openProductModal(id){
  try{
    const r=await fetch(`/api/data.php?action=product&id=${id}`);
    const d=await r.json();
    if(!d.product)return;
    currentProduct=d.product;currentImageIndex=0;
    document.getElementById('modalProductName').textContent=currentProduct.name;
    document.getElementById('modalProductDesc').textContent=currentProduct.description;
    document.getElementById('modalProductLink').href=currentProduct.affiliate_url||'#';
    document.getElementById('modalProductImage').src=currentProduct.images[0]||'';
    document.getElementById('modalCopyLink').dataset.productId=id;
    document.getElementById('productModal').classList.remove('hidden');
    updateImageNav();lucide.createIcons();
  }catch(e){console.error(e);}
}
function closeProductModal(){document.getElementById('productModal').classList.add('hidden');currentProduct=null;}
function updateImageNav(){
  if(!currentProduct)return;
  const multi=currentProduct.images.length>1;
  document.getElementById('prevImage').style.display=multi?'flex':'none';
  document.getElementById('nextImage').style.display=multi?'flex':'none';
  document.getElementById('imageCounter').style.display=multi?'block':'none';
  if(multi)document.getElementById('imageCounter').textContent=`${currentImageIndex+1}/${currentProduct.images.length}`;
}
function nextProductImage(){if(!currentProduct)return;currentImageIndex=(currentImageIndex+1)%currentProduct.images.length;const img=document.getElementById('modalProductImage');img.style.opacity='.4';setTimeout(()=>{img.src=currentProduct.images[currentImageIndex];img.style.opacity='1';},180);updateImageNav();}
function prevProductImage(){if(!currentProduct)return;currentImageIndex=(currentImageIndex-1+currentProduct.images.length)%currentProduct.images.length;const img=document.getElementById('modalProductImage');img.style.opacity='.4';setTimeout(()=>{img.src=currentProduct.images[currentImageIndex];img.style.opacity='1';},180);updateImageNav();}

// ── Copy Link ──────────────────────────────────────────────────
function copyProductLink(productId,btn){
  const id=productId||(btn&&btn.dataset.productId)||(currentProduct&&currentProduct.id);
  if(!id)return;
  const link=SITE_URL+'/?product='+id;
  navigator.clipboard.writeText(link).then(()=>{
    const t=document.getElementById('copyToast');t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2500);
  }).catch(()=>prompt('Copy this link:',link));
}

// ── Category Filter ────────────────────────────────────────────
async function filterByCategory(catId,catName){
  document.getElementById('trendingTitle').textContent=catName;
  document.getElementById('viewAllBtn').classList.remove('hidden');
  const r=await fetch(`/api/data.php?action=search&q=${encodeURIComponent(catName)}`);
  const d=await r.json();renderProducts(d.products||[]);
  document.getElementById('trending-section').scrollIntoView({behavior:'smooth',block:'start'});
}
async function clearFilter(){
  document.getElementById('trendingTitle').textContent='Trending Now';
  document.getElementById('viewAllBtn').classList.add('hidden');
  const r=await fetch('/api/data.php?action=trending');
  const d=await r.json();renderProducts(d.products||[]);
}

function renderProducts(products){
  const grid=document.getElementById('productGrid');
  if(!products.length){grid.innerHTML='<div class="col-span-3 text-center py-20 text-silver/35 text-sm">No products found in this category.</div>';return;}
  grid.innerHTML=products.map((p,i)=>`
    <div class="product-card glass-card rounded-2xl overflow-hidden group cursor-pointer border border-gold/[0.09]" style="transition-delay:${i*.08}s" onclick="openProductModal(${p.id})">
      <div class="relative h-72 overflow-hidden bg-graphite">
        <img src="${escHtml(p.images[0]||'')}" alt="${escHtml(p.name)}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
        <div class="absolute inset-0 bg-gradient-to-t from-onyx/60 via-transparent to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-onyx/90 via-onyx/40 to-onyx/10 opacity-0 group-hover:opacity-100 transition-all duration-400 flex flex-col items-center justify-end pb-5 gap-2.5 px-4">
          <button onclick="event.stopPropagation();openProductModal(${p.id})" class="btn-primary btn-shine w-full py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2"><span>View Details</span></button>
          <a href="${escHtml(p.affiliate_url||'#')}" target="_blank" rel="noopener noreferrer sponsored" onclick="event.stopPropagation()" class="btn-ghost w-full py-2.5 rounded-xl text-xs font-medium tracking-wider flex items-center justify-center gap-2">Shop Now ↗</a>
        </div>
        <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-2 group-hover:translate-x-0">
          <button onclick="event.stopPropagation();toggleWishlist(${p.id})" class="wishlist-btn-${p.id} w-8 h-8 rounded-full glass flex items-center justify-center hover:scale-110 transition-transform"><i data-lucide="heart" class="w-3.5 h-3.5 ${wishlist.includes(p.id)?'text-gold':'text-silver'}"></i></button>
        </div>
        <div class="absolute top-3 left-3"><span class="px-2.5 py-1 glass text-[10px] text-gold/80 uppercase tracking-wider rounded-full">${escHtml(p.category)}</span></div>
      </div>
      <div class="px-5 py-5">
        <h3 class="font-display text-[0.95rem] text-platinum group-hover:text-gold transition-colors mb-1.5 line-clamp-1">${escHtml(p.name)}</h3>
        <p class="text-[12px] text-silver/50 line-clamp-2 leading-relaxed mb-3">${escHtml(p.description)}</p>
        <div class="flex items-center justify-between">
          <span class="text-[11px] text-gold/60 uppercase tracking-widest font-medium">Discover More</span>
          <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gold/60 transition-transform group-hover:translate-x-1.5"></i>
        </div>
      </div>
    </div>
  `).join('');
  lucide.createIcons();observeReveals();
}

// ── Wishlist ───────────────────────────────────────────────────
function toggleWishlist(id){
  if(wishlist.includes(id)){wishlist=wishlist.filter(w=>w!==id);}
  else{wishlist.push(id);}
  localStorage.setItem('onyx_wishlist',JSON.stringify(wishlist));
  updateWishlistCount();
  syncWishlistButtons();
  if(document.getElementById('wishlistPanel').style.transform==='translateX(0px)'||
     document.getElementById('wishlistPanel').style.transform==='translateX(0)'){
    renderWishlistPanel();
  }
}
function updateWishlistCount(){
  const el=document.getElementById('wishCount');
  if(wishlist.length>0){el.textContent=wishlist.length;el.classList.remove('hidden');}
  else el.classList.add('hidden');
}
function syncWishlistButtons(){
  document.querySelectorAll('[class*="wishlist-btn-"]').forEach(btn=>{
    const match=btn.className.match(/wishlist-btn-(\d+)/);
    if(!match)return;
    const id=parseInt(match[1]);
    const icon=btn.querySelector('i');
    if(icon){
      if(wishlist.includes(id)){icon.classList.replace('text-silver','text-gold');}
      else{icon.classList.replace('text-gold','text-silver');}
    }
  });
}
async function renderWishlistPanel(){
  const container=document.getElementById('wishlistItems');
  if(!wishlist.length){
    container.innerHTML='<div class="flex items-center justify-center py-14"><div class="text-center"><i data-lucide="heart" class="w-8 h-8 text-gold/20 mx-auto mb-3"></i><p class="text-[13px] text-silver/40">Your wishlist is empty</p><p class="text-[11px] text-silver/25 mt-1">Hover over products to save them</p></div></div>';
    lucide.createIcons();return;
  }
  container.innerHTML='<div class="text-center py-8 text-silver/30 text-xs">Loading...</div>';
  try{
    const results=await Promise.all(wishlist.map(id=>fetch(`/api/data.php?action=product&id=${id}`).then(r=>r.json()).catch(()=>null)));
    container.innerHTML=results.filter(r=>r&&r.product).map(r=>{
      const p=r.product;
      const img=p.images&&p.images[0]?p.images[0]:'';
      return `<div class="wishlist-item flex items-start gap-3 p-3 glass-card rounded-xl hover:border-gold/20 transition-all duration-300">
        <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 bg-graphite">
          ${img?`<img src="${escHtml(img)}" class="wishlist-item-img w-full h-full object-cover">`:
            '<div class="w-full h-full flex items-center justify-center"><i data-lucide="image" class="w-5 h-5 text-gold/20"></i></div>'}
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[13px] text-platinum font-display truncate">${escHtml(p.name)}</p>
          <p class="text-[11px] text-silver/45 mt-0.5">${escHtml(p.category||'')}</p>
          <div class="flex items-center gap-3 mt-2">
            <a href="${escHtml(p.affiliate_url||'#')}" target="_blank" rel="noopener" class="text-[11px] text-gold hover:underline underline-offset-2">Shop Now ↗</a>
            <button onclick="toggleWishlist(${p.id});renderWishlistPanel()" class="text-[11px] text-silver/35 hover:text-red-400 transition-colors">Remove</button>
          </div>
        </div>
      </div>`;
    }).join('');
    lucide.createIcons();
  }catch(e){container.innerHTML='<div class="text-center py-8 text-silver/30 text-xs">Error loading items</div>';}
}
updateWishlistCount();

// ── Search ─────────────────────────────────────────────────────
let searchTimer;
document.getElementById('searchInput').addEventListener('input',function(){
  clearTimeout(searchTimer);const q=this.value.trim();
  if(q.length<2){document.getElementById('searchResults').innerHTML='';return;}
  searchTimer=setTimeout(async()=>{
    const r=await fetch(`/api/data.php?action=search&q=${encodeURIComponent(q)}`);
    const d=await r.json();
    const results=document.getElementById('searchResults');
    if(!d.products||!d.products.length){results.innerHTML='<p class="text-sm text-silver/40 py-4">No results found</p>';return;}
    results.innerHTML=d.products.map(p=>`
      <div class="flex items-center gap-4 p-3 glass-card rounded-xl hover:border-gold/20 transition-all cursor-pointer" onclick="document.getElementById('searchModal').classList.add('hidden');openProductModal(${p.id})">
        <div class="w-11 h-11 rounded-lg overflow-hidden bg-graphite flex-shrink-0">
          <img src="${escHtml(p.images[0]||'')}" class="w-full h-full object-cover" loading="lazy">
        </div>
        <div>
          <p class="text-[13px] text-platinum">${escHtml(p.name)}</p>
          <p class="text-[11px] text-silver/45 mt-0.5">${escHtml(p.category)}</p>
        </div>
        <i data-lucide="arrow-right" class="w-4 h-4 text-gold/40 ml-auto flex-shrink-0"></i>
      </div>
    `).join('');
    lucide.createIcons();
  },280);
});
document.querySelectorAll('.quick-search').forEach(btn=>{
  btn.addEventListener('click',()=>{document.getElementById('searchInput').value=btn.textContent;document.getElementById('searchInput').dispatchEvent(new Event('input'));});
});

// ── Chat ───────────────────────────────────────────────────────
document.getElementById('chatToggle').addEventListener('click',()=>{
  const p=document.getElementById('chatPanel');
  p.style.display=p.style.display==='none'?'flex':'none';
});
document.getElementById('chatClose').addEventListener('click',()=>{document.getElementById('chatPanel').style.display='none';});
document.getElementById('chatForm').addEventListener('submit',async(e)=>{
  e.preventDefault();const input=document.getElementById('chatInput');const msg=input.value.trim();
  if(!msg)return;input.value='';addChatMessage(msg,true);chatHistory.push({role:'user',content:msg});showTyping();
  try{
    const r=await fetch('/api/chat.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:msg,history:chatHistory.slice(-8)})});
    const d=await r.json();removeTyping();const reply=d.reply||d.error||'How else may I assist?';
    addChatMessage(reply,false);chatHistory.push({role:'assistant',content:reply});
  }catch(e){removeTyping();addChatMessage('I apologize for the inconvenience. Please try again.',false);}
});
document.querySelectorAll('.quick-reply').forEach(btn=>{
  btn.addEventListener('click',async()=>{
    const msg=btn.textContent.trim();addChatMessage(msg,true);chatHistory.push({role:'user',content:msg});showTyping();
    try{const r=await fetch('/api/chat.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:msg,history:[]})});const d=await r.json();removeTyping();addChatMessage(d.reply||'How may I assist?',false);}
    catch(e){removeTyping();addChatMessage('Please try again.',false);}
  });
});
function addChatMessage(text,isUser){
  const c=document.getElementById('chatMessages');
  const d=document.createElement('div');
  d.className=`chat-bubble ${isUser?'bg-gold/15 rounded-xl rounded-tr-none ml-auto':'bg-graphite/80 rounded-xl rounded-tl-none'} p-3`;
  d.innerHTML=`<p class="text-[12px] ${isUser?'text-gold/90':'text-silver/80'}">${escHtml(text)}</p>`;
  c.appendChild(d);c.scrollTop=c.scrollHeight;
}
function showTyping(){
  const c=document.getElementById('chatMessages');
  const d=document.createElement('div');d.id='typing';
  d.className='flex gap-1.5 p-3 bg-graphite/80 rounded-xl rounded-tl-none w-fit';
  d.innerHTML='<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
  c.appendChild(d);c.scrollTop=c.scrollHeight;
}
function removeTyping(){const el=document.getElementById('typing');if(el)el.remove();}

// ── Navigation ─────────────────────────────────────────────────
document.getElementById('menuBtn').addEventListener('click',()=>document.getElementById('mobileMenu').classList.remove('hidden'));
document.getElementById('closeMenu').addEventListener('click',()=>document.getElementById('mobileMenu').classList.add('hidden'));
function closeMobileMenu(){document.getElementById('mobileMenu').classList.add('hidden');}
document.getElementById('searchBtn').addEventListener('click',()=>{document.getElementById('searchModal').classList.remove('hidden');document.getElementById('searchInput').focus();});
document.getElementById('closeSearch').addEventListener('click',()=>document.getElementById('searchModal').classList.add('hidden'));
document.getElementById('searchInput').addEventListener('keydown',e=>{if(e.key==='Escape')document.getElementById('searchModal').classList.add('hidden');});
document.getElementById('wishlistBtn').addEventListener('click',()=>{
  document.getElementById('wishlistPanel').style.transform='translateX(0)';
  renderWishlistPanel();
});
document.getElementById('closeWishlist').addEventListener('click',()=>{
  document.getElementById('wishlistPanel').style.transform='translateX(100%)';
});

// ── Newsletter ─────────────────────────────────────────────────
document.getElementById('newsletterForm').addEventListener('submit',async(e)=>{
  e.preventDefault();const email=e.target.querySelector('input[type="email"]').value.trim();
  if(!email)return;
  const btn=e.target.querySelector('button[type="submit"]');btn.querySelector('span').textContent='...';btn.disabled=true;
  try{
    const r=await fetch('/api/subscribe.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email})});
    const d=await r.json();
    const msg=document.getElementById('newsletterMsg');msg.textContent=d.message||'✓ Welcome to the inner circle!';msg.classList.remove('hidden');
    e.target.reset();
  }catch(err){document.getElementById('newsletterMsg').classList.remove('hidden');}
  btn.querySelector('span').textContent='Subscribe';btn.disabled=false;
});

// ── Keyboard shortcuts ─────────────────────────────────────────
document.addEventListener('keydown',e=>{
  if(e.key==='Escape'){closeProductModal();document.getElementById('searchModal').classList.add('hidden');}
  if(currentProduct){if(e.key==='ArrowRight')nextProductImage();if(e.key==='ArrowLeft')prevProductImage();}
});

// ── Product from URL ───────────────────────────────────────────
<?php if($productView):?>
addEventListener('DOMContentLoaded',()=>openProductModal(<?=$productView['id']?>));
<?php endif;?>

// ── Init ───────────────────────────────────────────────────────
createParticles();
observeReveals();
lucide.createIcons();
init3D();
syncWishlistButtons();
</script>
</body>
</html>

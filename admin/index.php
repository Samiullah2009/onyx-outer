<?php
// admin/index.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setSecurityHeaders();
requireAdmin();

$csrf = generateCsrf();
$stats = SiteData::getDashboardStats();
$settings = SiteData::getAllSettings();
$social = SiteData::getSocialLinks();
$socialMap = [];
foreach ($social as $s) $socialMap[$s['platform']] = $s['url'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — Onyx & Outer</title>
<meta name="robots" content="noindex, nofollow">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body { background: #050505; font-family: 'Inter', sans-serif; color: #D9D9D9; }
  .glass { background: rgba(17,17,17,0.9); border: 1px solid rgba(200,164,107,0.12); }
  .glass-light { background: rgba(28,28,28,0.8); border: 1px solid rgba(200,164,107,0.08); }
  .gold { color: #C8A46B; }
  .sidebar-link { transition: all 0.2s; border-left: 2px solid transparent; }
  .sidebar-link.active, .sidebar-link:hover { border-left-color: #C8A46B; background: rgba(200,164,107,0.08); color: #C8A46B; }
  .tab-btn { transition: all 0.2s; }
  .tab-btn.active { background: rgba(200,164,107,0.15); color: #C8A46B; border-color: rgba(200,164,107,0.4); }
  input, textarea, select { background: rgba(0,0,0,0.5) !important; border-color: rgba(200,164,107,0.2) !important; color: #D9D9D9 !important; }
  input:focus, textarea:focus, select:focus { border-color: rgba(200,164,107,0.5) !important; outline: none !important; }
  .btn-gold { background: linear-gradient(135deg,#C8A46B,#b8935a); color: #050505; }
  .btn-gold:hover { opacity: 0.9; }
  .product-row:hover { background: rgba(200,164,107,0.05); }
  .rating-star { cursor: pointer; font-size: 1.2rem; }
  .modal-bg { background: rgba(5,5,5,0.95); backdrop-filter: blur(10px); }
  ::-webkit-scrollbar { width: 5px; }
  ::-webkit-scrollbar-track { background: #111; }
  ::-webkit-scrollbar-thumb { background: #C8A46B; border-radius: 3px; }
  .toast { position: fixed; bottom: 24px; right: 24px; z-index: 9999; padding: 12px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; transition: all 0.3s; transform: translateY(100px); opacity: 0; }
  .toast.show { transform: translateY(0); opacity: 1; }
  .toast.success { background: #065f46; color: #6ee7b7; border: 1px solid #047857; }
  .toast.error { background: #7f1d1d; color: #fca5a5; border: 1px solid #991b1b; }
</style>
</head>
<body class="min-h-screen">

<!-- Toast -->
<div id="toast" class="toast"></div>

<!-- Layout -->
<div class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside class="w-56 glass flex-shrink-0 flex flex-col overflow-y-auto">
    <div class="p-4 border-b border-yellow-800/20">
      <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-yellow-600 to-yellow-400 flex items-center justify-center">
          <span class="text-black font-bold text-xs">O</span>
        </div>
        <div>
          <p class="text-white text-sm font-semibold" style="font-family:'Playfair Display',serif">Onyx & Outer</p>
          <p class="text-gray-500 text-[10px]">Admin Panel</p>
        </div>
      </div>
    </div>
    <nav class="flex-1 p-3 space-y-1">
      <?php
      $tabs = [
        ['dashboard','Dashboard','layout-dashboard'],
        ['products','Products','package'],
        ['categories','Categories','grid'],
        ['reviews','Reviews','star'],
        ['settings','Site Settings','settings'],
        ['social','Social Links','share-2'],
        ['apikeys','API Keys','key'],
      ];
      foreach ($tabs as [$id,$label,$icon]):
      ?>
      <button onclick="switchTab('<?= $id ?>')"
              class="sidebar-link w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 text-left"
              id="sidebar-<?= $id ?>">
        <i data-lucide="<?= $icon ?>" class="w-4 h-4 flex-shrink-0"></i>
        <?= $label ?>
      </button>
      <?php endforeach; ?>
    </nav>
    <div class="p-3 border-t border-yellow-800/20">
      <a href="/" target="_blank" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-500 hover:text-yellow-600 transition-colors">
        <i data-lucide="external-link" class="w-3.5 h-3.5"></i> View Site
      </a>
      <a href="/admin/logout.php" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-500 hover:text-red-400 transition-colors">
        <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Logout
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto">
    <div class="p-6">

      <!-- DASHBOARD TAB -->
      <div id="tab-dashboard" class="tab-content">
        <h1 class="text-2xl font-semibold text-white mb-6" style="font-family:'Playfair Display',serif">Dashboard</h1>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <div class="glass rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Products</p>
            <p class="text-3xl text-white font-semibold"><?= $stats['total_products'] ?></p>
          </div>
          <div class="glass rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Categories</p>
            <p class="text-3xl text-white font-semibold"><?= $stats['total_categories'] ?></p>
          </div>
          <div class="glass rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Reviews</p>
            <p class="text-3xl text-white font-semibold"><?= $stats['total_reviews'] ?></p>
          </div>
          <div class="glass rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Featured</p>
            <p class="text-3xl text-white font-semibold"><?= $stats['featured_products'] ?></p>
          </div>
        </div>
        <div class="glass rounded-xl p-6">
          <h2 class="text-sm font-semibold text-white mb-4">Quick Actions</h2>
          <div class="flex flex-wrap gap-3">
            <button onclick="switchTab('products'); openProductModal()" class="btn-gold px-4 py-2 rounded-lg text-sm font-medium">+ Add Product</button>
            <button onclick="switchTab('categories'); openCatModal()" class="btn-gold px-4 py-2 rounded-lg text-sm font-medium">+ Add Category</button>
            <button onclick="switchTab('reviews'); openReviewModal()" class="btn-gold px-4 py-2 rounded-lg text-sm font-medium">+ Add Review</button>
            <button onclick="rotateTrending()" class="px-4 py-2 rounded-lg text-sm font-medium border border-yellow-700/40 text-yellow-600 hover:bg-yellow-600/10 transition-colors">↻ Rotate Trending</button>
          </div>
        </div>
      </div>

      <!-- PRODUCTS TAB -->
      <div id="tab-products" class="tab-content hidden">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-2xl font-semibold text-white" style="font-family:'Playfair Display',serif">Products</h1>
          <button onclick="openProductModal()" class="btn-gold px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Product
          </button>
        </div>
        <div class="glass rounded-xl overflow-hidden">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-yellow-800/20">
                <th class="text-left p-4 text-xs text-gray-500 uppercase tracking-wider">Product</th>
                <th class="text-left p-4 text-xs text-gray-500 uppercase tracking-wider hidden md:table-cell">Category</th>
                <th class="text-left p-4 text-xs text-gray-500 uppercase tracking-wider hidden lg:table-cell">Status</th>
                <th class="text-right p-4 text-xs text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody id="products-table">
              <tr><td colspan="4" class="p-8 text-center text-gray-500">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- CATEGORIES TAB -->
      <div id="tab-categories" class="tab-content hidden">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-2xl font-semibold text-white" style="font-family:'Playfair Display',serif">Categories</h1>
          <button onclick="openCatModal()" class="btn-gold px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Category
          </button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4" id="categories-grid">
          <div class="glass rounded-xl p-8 text-center text-gray-500">Loading...</div>
        </div>
      </div>

      <!-- REVIEWS TAB -->
      <div id="tab-reviews" class="tab-content hidden">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-2xl font-semibold text-white" style="font-family:'Playfair Display',serif">Reviews</h1>
          <button onclick="openReviewModal()" class="btn-gold px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Review
          </button>
        </div>
        <div class="space-y-4" id="reviews-list">
          <div class="glass rounded-xl p-8 text-center text-gray-500">Loading...</div>
        </div>
      </div>

      <!-- SETTINGS TAB -->
      <div id="tab-settings" class="tab-content hidden">
        <h1 class="text-2xl font-semibold text-white mb-6" style="font-family:'Playfair Display',serif">Site Settings</h1>
        <form id="settings-form" class="space-y-6">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <div class="glass rounded-xl p-6">
            <h2 class="text-sm font-semibold text-yellow-600 mb-4 uppercase tracking-wider">Hero Section</h2>
            <div class="space-y-4">
              <div>
                <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Hero Heading</label>
                <input type="text" name="hero_heading" value="<?= e($settings['hero_heading'] ?? '') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Hero Subtext</label>
                <textarea name="hero_subtext" rows="2" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm resize-none"><?= e($settings['hero_subtext'] ?? '') ?></textarea>
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">CTA Button Text</label>
                <input type="text" name="cta_text" value="<?= e($settings['cta_text'] ?? '') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
            </div>
          </div>
          <div class="glass rounded-xl p-6">
            <h2 class="text-sm font-semibold text-yellow-600 mb-4 uppercase tracking-wider">Stats Bar</h2>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-xs text-gray-400 mb-1.5">Products</label>
                <input type="text" name="stats_products" value="<?= e($settings['stats_products'] ?? '500+') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1.5">Brands</label>
                <input type="text" name="stats_brands" value="<?= e($settings['stats_brands'] ?? '50+') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1.5">Happy Clients</label>
                <input type="text" name="stats_clients" value="<?= e($settings['stats_clients'] ?? '10K+') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
            </div>
          </div>
          <div class="glass rounded-xl p-6">
            <h2 class="text-sm font-semibold text-yellow-600 mb-4 uppercase tracking-wider">About & Footer</h2>
            <div class="space-y-4">
              <div>
                <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">About Us Text</label>
                <textarea name="about_text" rows="3" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm resize-none"><?= e($settings['about_text'] ?? '') ?></textarea>
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Footer Tagline</label>
                <input type="text" name="footer_tagline" value="<?= e($settings['footer_tagline'] ?? '') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
            </div>
          </div>
          <div class="glass rounded-xl p-6">
            <h2 class="text-sm font-semibold text-yellow-600 mb-4 uppercase tracking-wider">Journal / Blog Section</h2>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs text-gray-400 mb-1.5">Title</label>
                <input type="text" name="journal_title" value="<?= e($settings['journal_title'] ?? '') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1.5">Subtitle</label>
                <input type="text" name="journal_subtitle" value="<?= e($settings['journal_subtitle'] ?? '') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
            </div>
          </div>
          <div class="glass rounded-xl p-6">
            <h2 class="text-sm font-semibold text-yellow-600 mb-4 uppercase tracking-wider">Newsletter Section</h2>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs text-gray-400 mb-1.5">Title</label>
                <input type="text" name="newsletter_title" value="<?= e($settings['newsletter_title'] ?? '') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1.5">Subtitle</label>
                <input type="text" name="newsletter_subtitle" value="<?= e($settings['newsletter_subtitle'] ?? '') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
            </div>
          </div>
          <div class="glass rounded-xl p-6">
            <h2 class="text-sm font-semibold text-yellow-600 mb-4 uppercase tracking-wider">SEO / Analytics</h2>
            <div class="space-y-4">
              <div>
                <label class="block text-xs text-gray-400 mb-1.5">Meta Description</label>
                <textarea name="meta_description" rows="2" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm resize-none"><?= e($settings['meta_description'] ?? '') ?></textarea>
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1.5">Google Analytics ID (e.g. G-XXXXXXXX)</label>
                <input type="text" name="google_analytics_id" value="<?= e($settings['google_analytics_id'] ?? '') ?>" class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
              </div>
            </div>
          </div>
          <button type="submit" class="btn-gold px-8 py-3 rounded-lg font-semibold text-sm">Save All Settings</button>
        </form>
      </div>

      <!-- SOCIAL TAB -->
      <div id="tab-social" class="tab-content hidden">
        <h1 class="text-2xl font-semibold text-white mb-6" style="font-family:'Playfair Display',serif">Social Media Links</h1>
        <form id="social-form" class="glass rounded-xl p-6 space-y-4">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <?php
          $platforms = [
            'instagram' => 'Instagram',
            'twitter'   => 'Twitter / X',
            'facebook'  => 'Facebook',
            'youtube'   => 'YouTube',
            'tiktok'    => 'TikTok',
            'pinterest' => 'Pinterest',
            'linkedin'  => 'LinkedIn',
          ];
          foreach ($platforms as $key => $label):
          ?>
          <div>
            <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider"><?= $label ?></label>
            <input type="url" name="<?= $key ?>" value="<?= e($socialMap[$key] ?? '') ?>"
                   placeholder="https://<?= $key ?>.com/yourhandle"
                   class="w-full px-3 py-2.5 bg-black/40 border border-gray-700 rounded-lg text-sm">
          </div>
          <?php endforeach; ?>
          <button type="submit" class="btn-gold px-8 py-3 rounded-lg font-semibold text-sm">Save Social Links</button>
        </form>
      </div>


      <!-- API KEYS TAB -->
      <div id="tab-apikeys" class="tab-content hidden">
        <h1 class="text-2xl font-semibold text-white mb-2" style="font-family:'Playfair Display',serif">API Keys</h1>
        <p class="text-gray-500 text-sm mb-6">Set your AI provider API keys here. They are stored securely in the database — no file editing required.</p>

        <form id="apikeys-form" class="space-y-6">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

          <div class="glass rounded-xl p-6 space-y-5">
            <h2 class="text-sm font-semibold text-yellow-600 uppercase tracking-wider">Anthropic (Claude) — Used for the AI Chat</h2>
            <div>
              <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Anthropic API Key</label>
              <div class="flex gap-2">
                <input type="text" id="anthropic_api_key" name="anthropic_api_key"
                       placeholder="sk-ant-api03-..."
                       class="flex-1 px-3 py-2.5 bg-black/50 border border-gray-700 rounded-lg text-sm font-mono"
                       autocomplete="off" spellcheck="false">
                <button type="button" onclick="toggleKeyVis('anthropic_api_key')"
                        class="px-3 py-2 border border-gray-700 rounded-lg text-xs text-gray-400 hover:text-white transition-colors">Show</button>
              </div>
              <p class="mt-1.5 text-xs text-gray-600">Get your key at <a href="https://console.anthropic.com" target="_blank" class="text-yellow-700 hover:text-yellow-500">console.anthropic.com</a></p>
            </div>
          </div>

          <div class="glass rounded-xl p-6 space-y-5">
            <h2 class="text-sm font-semibold text-yellow-600 uppercase tracking-wider">OpenAI (optional — for future use)</h2>
            <div>
              <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">OpenAI API Key</label>
              <div class="flex gap-2">
                <input type="text" id="openai_api_key" name="openai_api_key"
                       placeholder="sk-..."
                       class="flex-1 px-3 py-2.5 bg-black/50 border border-gray-700 rounded-lg text-sm font-mono"
                       autocomplete="off" spellcheck="false">
                <button type="button" onclick="toggleKeyVis('openai_api_key')"
                        class="px-3 py-2 border border-gray-700 rounded-lg text-xs text-gray-400 hover:text-white transition-colors">Show</button>
              </div>
            </div>
          </div>

          <div class="glass rounded-xl p-6 space-y-5">
            <h2 class="text-sm font-semibold text-yellow-600 uppercase tracking-wider">Google Gemini (optional — for future use)</h2>
            <div>
              <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Gemini API Key</label>
              <div class="flex gap-2">
                <input type="text" id="gemini_api_key" name="gemini_api_key"
                       placeholder="AIza..."
                       class="flex-1 px-3 py-2.5 bg-black/50 border border-gray-700 rounded-lg text-sm font-mono"
                       autocomplete="off" spellcheck="false">
                <button type="button" onclick="toggleKeyVis('gemini_api_key')"
                        class="px-3 py-2 border border-gray-700 rounded-lg text-xs text-gray-400 hover:text-white transition-colors">Show</button>
              </div>
            </div>
          </div>

          <div class="glass rounded-xl p-4 border border-yellow-800/30">
            <p class="text-xs text-yellow-700">⚠️ Keys are stored in your database, not in any PHP file. They are never exposed to visitors — only used server-side when calling the AI API.</p>
          </div>

          <button type="submit" class="btn-gold px-8 py-3 rounded-lg font-semibold text-sm">Save API Keys</button>
        </form>
      </div>

    </div>
  </main>
</div>

<!-- PRODUCT MODAL -->
<div id="productModal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 modal-bg" onclick="closeProductModal()"></div>
  <div class="absolute inset-0 flex items-start justify-center p-4 pt-8 overflow-y-auto">
    <div class="relative w-full max-w-2xl glass rounded-2xl overflow-hidden mb-8" style="pointer-events:auto">
      <div class="flex items-center justify-between p-5 border-b border-yellow-800/20">
        <h2 id="productModalTitle" class="text-lg font-semibold text-white" style="font-family:'Playfair Display',serif">Add Product</h2>
        <button onclick="closeProductModal()" class="text-gray-500 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form id="productForm" class="p-5 space-y-4">
        <input type="hidden" id="productId" name="id" value="">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Product Name *</label>
            <input type="text" name="name" id="pName" required class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Category</label>
            <select name="category_id" id="pCategory" class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
              <option value="">-- Select --</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Sort Order</label>
            <input type="number" name="sort_order" id="pSort" value="0" class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
          </div>
          <div class="col-span-2">
            <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Description</label>
            <textarea name="description" id="pDesc" rows="3" class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm resize-none"></textarea>
          </div>
          <div class="col-span-2">
            <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Affiliate URL</label>
            <input type="url" name="affiliate_url" id="pUrl" placeholder="https://..." class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
          </div>
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="featured" id="pFeatured" class="w-4 h-4 accent-yellow-600">
              <span class="text-sm text-gray-300">Featured</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="active" id="pActive" checked class="w-4 h-4 accent-yellow-600">
              <span class="text-sm text-gray-300">Active</span>
            </label>
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" onclick="closeProductModal()" class="px-5 py-2 rounded-lg text-sm border border-gray-700 text-gray-400 hover:text-white transition-colors">Cancel</button>
          <button type="submit" class="btn-gold px-5 py-2 rounded-lg text-sm font-medium">Save Product</button>
        </div>
      </form>
      <!-- Images section (shows after product is saved) -->
      <div id="productImagesSection" class="hidden p-5 border-t border-yellow-800/20">
        <h3 class="text-sm font-semibold text-yellow-600 mb-3">Product Images</h3>
        <div id="currentImages" class="grid grid-cols-3 gap-3 mb-4"></div>
        <div class="space-y-3">
          <div>
            <label class="block text-xs text-gray-400 mb-1.5">Add Image by URL</label>
            <div class="flex gap-2">
              <input type="url" id="newImageUrl" placeholder="https://..." class="flex-1 px-3 py-2 border border-gray-700 rounded-lg text-sm">
              <button onclick="addImageUrl()" class="btn-gold px-4 py-2 rounded-lg text-sm">Add</button>
            </div>
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1.5">Or Upload Image</label>
            <input type="file" id="imageUpload" accept="image/*" class="w-full text-sm text-gray-400">
            <button onclick="uploadImage()" class="btn-gold px-4 py-2 rounded-lg text-sm mt-2">Upload</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CATEGORY MODAL -->
<div id="catModal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 modal-bg" onclick="closeCatModal()"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative w-full max-w-md glass rounded-2xl overflow-hidden" style="pointer-events:auto">
      <div class="flex items-center justify-between p-5 border-b border-yellow-800/20">
        <h2 id="catModalTitle" class="text-lg font-semibold text-white" style="font-family:'Playfair Display',serif">Add Category</h2>
        <button onclick="closeCatModal()" class="text-gray-500 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form id="catForm" class="p-5 space-y-4">
        <input type="hidden" id="catId" name="id" value="">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div>
          <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Name *</label>
          <input type="text" name="name" id="cName" required class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Subtitle</label>
          <input type="text" name="subtitle" id="cSubtitle" class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Thumbnail URL</label>
          <input type="url" name="thumbnail" id="cThumb" placeholder="https://..." class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Or Upload Thumbnail</label>
          <input type="file" name="thumbnail_file" id="cThumbFile" accept="image/*" class="w-full text-sm text-gray-400">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1.5">Sort Order</label>
            <input type="number" name="sort_order" id="cSort" value="0" class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
          </div>
          <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer pb-2.5">
              <input type="checkbox" name="active" id="cActive" checked class="w-4 h-4 accent-yellow-600">
              <span class="text-sm text-gray-300">Active</span>
            </label>
          </div>
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" onclick="closeCatModal()" class="px-5 py-2 rounded-lg text-sm border border-gray-700 text-gray-400">Cancel</button>
          <button type="submit" class="btn-gold px-5 py-2 rounded-lg text-sm font-medium">Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- REVIEW MODAL -->
<div id="reviewModal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 modal-bg" onclick="closeReviewModal()"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative w-full max-w-md glass rounded-2xl overflow-hidden" style="pointer-events:auto">
      <div class="flex items-center justify-between p-5 border-b border-yellow-800/20">
        <h2 id="reviewModalTitle" class="text-lg font-semibold text-white" style="font-family:'Playfair Display',serif">Add Review</h2>
        <button onclick="closeReviewModal()" class="text-gray-500 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form id="reviewForm" class="p-5 space-y-4">
        <input type="hidden" id="reviewId" name="id" value="">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div>
          <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Reviewer Name *</label>
          <input type="text" name="reviewer_name" id="rName" required class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Title / Role</label>
          <input type="text" name="reviewer_title" id="rTitle" placeholder="e.g. Watch Enthusiast" class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1.5 uppercase tracking-wider">Review Text *</label>
          <textarea name="review_text" id="rText" rows="3" required class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm resize-none"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1.5">Rating</label>
            <select name="rating" id="rRating" class="w-full px-3 py-2.5 border border-gray-700 rounded-lg text-sm">
              <option value="5">★★★★★ (5)</option>
              <option value="4">★★★★☆ (4)</option>
              <option value="3">★★★☆☆ (3)</option>
              <option value="2">★★☆☆☆ (2)</option>
              <option value="1">★☆☆☆☆ (1)</option>
            </select>
          </div>
          <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer pb-2.5">
              <input type="checkbox" name="active" id="rActive" checked class="w-4 h-4 accent-yellow-600">
              <span class="text-sm text-gray-300">Active</span>
            </label>
          </div>
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" onclick="closeReviewModal()" class="px-5 py-2 rounded-lg text-sm border border-gray-700 text-gray-400">Cancel</button>
          <button type="submit" class="btn-gold px-5 py-2 rounded-lg text-sm font-medium">Save Review</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
let currentProductId = null;

// ---- TABS ----
function switchTab(name) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));
  document.querySelectorAll('[id^="sidebar-"]').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.remove('hidden');
  document.getElementById('sidebar-' + name).classList.add('active');
  
  // Load data
  if (name === 'products') loadProducts();
  if (name === 'categories') loadCategories();
  if (name === 'reviews') loadReviews();
  if (name === 'apikeys') loadApiKeys();
}

// ---- TOAST ----
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = `toast ${type} show`;
  setTimeout(() => t.classList.remove('show'), 3000);
}

// ---- AJAX HELPER ----
async function adminAjax(data) {
  const form = new FormData();
  for (const [k,v] of Object.entries(data)) form.append(k, v);
  form.append('csrf_token', CSRF);
  const res = await fetch('/admin/ajax.php', { method: 'POST', body: form });
  return res.json();
}

async function adminAjaxForm(formElement, extraData = {}) {
  const form = new FormData(formElement);
  for (const [k,v] of Object.entries(extraData)) form.append(k, v);
  const res = await fetch('/admin/ajax.php', { method: 'POST', body: form });
  return res.json();
}

// ---- PRODUCTS ----
async function loadProducts() {
  const res = await fetch('/admin/ajax.php?action=get_products');
  const data = await res.json();
  const tbody = document.getElementById('products-table');
  if (!data.data || !data.data.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">No products yet</td></tr>';
    return;
  }
  tbody.innerHTML = data.data.map(p => `
    <tr class="product-row border-b border-gray-800/50 transition-colors">
      <td class="p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-800 flex-shrink-0">
            ${p.images && p.images[0] ? `<img src="${p.images[0].image_path}" class="w-full h-full object-cover">` : '<div class="w-full h-full bg-gray-700"></div>'}
          </div>
          <div>
            <p class="text-sm text-white font-medium">${escHtml(p.name)}</p>
            <p class="text-xs text-gray-500">${p.featured ? '⭐ Featured' : ''}</p>
          </div>
        </div>
      </td>
      <td class="p-4 hidden md:table-cell text-sm text-gray-400">${escHtml(p.category_name || '—')}</td>
      <td class="p-4 hidden lg:table-cell">
        <span class="px-2 py-0.5 rounded-full text-[10px] ${p.active ? 'bg-green-900/40 text-green-400' : 'bg-gray-800 text-gray-500'}">${p.active ? 'Active' : 'Inactive'}</span>
      </td>
      <td class="p-4 text-right">
        <div class="flex items-center justify-end gap-2">
          <button onclick="editProduct(${p.id})" class="p-1.5 text-gray-500 hover:text-yellow-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
          <button onclick="deleteProduct(${p.id},'${escHtml(p.name)}')" class="p-1.5 text-gray-500 hover:text-red-400 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
        </div>
      </td>
    </tr>
  `).join('');
  
  // Populate category dropdown
  const res2 = await fetch('/admin/ajax.php?action=get_categories');
  const cats = await res2.json();
  const sel = document.getElementById('pCategory');
  sel.innerHTML = '<option value="">-- Select --</option>' + 
    (cats.data || []).map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
  
  lucide.createIcons();
}

function openProductModal(productId = null) {
  document.getElementById('productModal').classList.remove('hidden');
  document.getElementById('productImagesSection').classList.add('hidden');
  document.getElementById('productModalTitle').textContent = productId ? 'Edit Product' : 'Add Product';
  document.getElementById('productForm').reset();
  document.getElementById('productId').value = productId || '';
  currentProductId = productId;
  
  if (!productId) return;
  
  // Load product data
  fetch(`/admin/ajax.php?action=get_product&id=${productId}`)
    .then(r => r.json()).then(data => {
      if (!data.data) return;
      const p = data.data;
      document.getElementById('pName').value = p.name;
      document.getElementById('pDesc').value = p.description;
      document.getElementById('pUrl').value = p.affiliate_url;
      document.getElementById('pSort').value = p.sort_order;
      document.getElementById('pFeatured').checked = p.featured == 1;
      document.getElementById('pActive').checked = p.active == 1;
      
      // Set category
      const sel = document.getElementById('pCategory');
      if (p.category_id) sel.value = p.category_id;
      
      // Show images
      currentProductId = p.id;
      loadProductImages(p.id);
    });
}

async function loadProductImages(productId) {
  const res = await fetch(`/admin/ajax.php?action=get_product&id=${productId}`);
  const data = await res.json();
  const images = data.data?.images || [];
  
  document.getElementById('productImagesSection').classList.remove('hidden');
  
  const container = document.getElementById('currentImages');
  container.innerHTML = images.length ? images.map(img => `
    <div class="relative group rounded-lg overflow-hidden bg-gray-800 aspect-square">
      <img src="${img.image_path}" class="w-full h-full object-cover">
      <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
        <button onclick="setImagePrimary(${img.id},${productId})" title="Set as primary" class="p-1 bg-yellow-600/80 rounded text-black text-xs">★</button>
        <button onclick="deleteImage(${img.id})" title="Delete" class="p-1 bg-red-600/80 rounded text-white text-xs">✕</button>
      </div>
      ${img.is_primary ? '<span class="absolute top-1 left-1 px-1.5 py-0.5 bg-yellow-600 text-black text-[9px] rounded">Primary</span>' : ''}
    </div>
  `).join('') : '<p class="col-span-3 text-xs text-gray-500 py-4 text-center">No images yet</p>';
}

function closeProductModal() {
  document.getElementById('productModal').classList.add('hidden');
  currentProductId = null;
}

document.getElementById('productForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await adminAjaxForm(this, { action: 'save_product' });
  if (data.success) {
    showToast(data.message);
    if (!currentProductId && data.id) {
      currentProductId = data.id;
      document.getElementById('productId').value = data.id;
      loadProductImages(data.id);
    }
    loadProducts();
  } else {
    showToast(data.error || 'Error saving product', 'error');
  }
});

async function deleteProduct(id, name) {
  if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
  const data = await adminAjax({ action: 'delete_product', id });
  if (data.success) { showToast('Product deleted'); loadProducts(); }
  else showToast(data.error, 'error');
}

function editProduct(id) { openProductModal(id); }

async function addImageUrl() {
  if (!currentProductId) { showToast('Save the product first', 'error'); return; }
  const url = document.getElementById('newImageUrl').value.trim();
  if (!url) return;
  const data = await adminAjax({ action: 'add_image_url', product_id: currentProductId, url });
  if (data.success) { showToast('Image added'); loadProductImages(currentProductId); document.getElementById('newImageUrl').value = ''; }
  else showToast(data.error, 'error');
}

async function uploadImage() {
  if (!currentProductId) { showToast('Save the product first', 'error'); return; }
  const fileInput = document.getElementById('imageUpload');
  if (!fileInput.files[0]) return;
  const form = new FormData();
  form.append('action', 'upload_image');
  form.append('csrf_token', CSRF);
  form.append('product_id', currentProductId);
  form.append('image', fileInput.files[0]);
  const res = await fetch('/admin/ajax.php', { method: 'POST', body: form });
  const data = await res.json();
  if (data.success) { showToast('Image uploaded'); loadProductImages(currentProductId); fileInput.value = ''; }
  else showToast(data.error, 'error');
}

async function setImagePrimary(imageId, productId) {
  const data = await adminAjax({ action: 'set_primary_image', image_id: imageId, product_id: productId });
  if (data.success) { showToast('Primary image set'); loadProductImages(productId); }
}

async function deleteImage(imageId) {
  if (!confirm('Delete this image?')) return;
  const data = await adminAjax({ action: 'delete_image', image_id: imageId });
  if (data.success) { showToast('Image deleted'); loadProductImages(currentProductId); }
}

// ---- CATEGORIES ----
async function loadCategories() {
  const res = await fetch('/admin/ajax.php?action=get_categories');
  const data = await res.json();
  const grid = document.getElementById('categories-grid');
  if (!data.data || !data.data.length) {
    grid.innerHTML = '<div class="glass rounded-xl p-8 text-center text-gray-500 col-span-4">No categories yet</div>';
    return;
  }
  grid.innerHTML = data.data.map(c => `
    <div class="glass rounded-xl overflow-hidden">
      <div class="aspect-square bg-gray-800">
        ${c.thumbnail ? `<img src="${c.thumbnail}" class="w-full h-full object-cover">` : '<div class="w-full h-full bg-gray-700 flex items-center justify-center text-gray-500 text-2xl">?</div>'}
      </div>
      <div class="p-3">
        <p class="text-sm text-white font-medium">${escHtml(c.name)}</p>
        <p class="text-xs text-gray-500">${escHtml(c.subtitle || '')}</p>
        <span class="inline-block mt-1 px-1.5 py-0.5 rounded-full text-[10px] ${c.active ? 'bg-green-900/40 text-green-400' : 'bg-gray-800 text-gray-500'}">${c.active ? 'Active' : 'Hidden'}</span>
        <div class="flex gap-2 mt-3">
          <button onclick="editCat(${JSON.stringify(c).replace(/"/g,'&quot;')})" class="flex-1 px-2 py-1.5 text-xs border border-gray-700 text-gray-400 hover:text-yellow-600 rounded-lg transition-colors">Edit</button>
          <button onclick="deleteCat(${c.id},'${escHtml(c.name)}')" class="px-2 py-1.5 text-xs border border-red-800/40 text-red-500 hover:bg-red-900/20 rounded-lg transition-colors">Del</button>
        </div>
      </div>
    </div>
  `).join('');
}

function openCatModal() {
  document.getElementById('catModal').classList.remove('hidden');
  document.getElementById('catModalTitle').textContent = 'Add Category';
  document.getElementById('catForm').reset();
  document.getElementById('catId').value = '';
}

function editCat(c) {
  document.getElementById('catModal').classList.remove('hidden');
  document.getElementById('catModalTitle').textContent = 'Edit Category';
  document.getElementById('catId').value = c.id;
  document.getElementById('cName').value = c.name;
  document.getElementById('cSubtitle').value = c.subtitle || '';
  document.getElementById('cThumb').value = c.thumbnail || '';
  document.getElementById('cSort').value = c.sort_order;
  document.getElementById('cActive').checked = c.active == 1;
}

function closeCatModal() { document.getElementById('catModal').classList.add('hidden'); }

document.getElementById('catForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await adminAjaxForm(this, { action: 'save_category' });
  if (data.success) { showToast(data.message); closeCatModal(); loadCategories(); }
  else showToast(data.error || 'Error', 'error');
});

async function deleteCat(id, name) {
  if (!confirm(`Delete category "${name}"?`)) return;
  const data = await adminAjax({ action: 'delete_category', id });
  if (data.success) { showToast('Category deleted'); loadCategories(); }
  else showToast(data.error, 'error');
}

// ---- REVIEWS ----
async function loadReviews() {
  const res = await fetch('/admin/ajax.php?action=get_reviews');
  const data = await res.json();
  const list = document.getElementById('reviews-list');
  if (!data.data || !data.data.length) {
    list.innerHTML = '<div class="glass rounded-xl p-8 text-center text-gray-500">No reviews yet</div>';
    return;
  }
  list.innerHTML = data.data.map(r => `
    <div class="glass rounded-xl p-5 flex items-start justify-between gap-4">
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-1">
          <span class="text-yellow-500 text-sm">${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</span>
          <span class="inline-block px-1.5 py-0.5 rounded-full text-[10px] ${r.active ? 'bg-green-900/40 text-green-400' : 'bg-gray-800 text-gray-500'}">${r.active ? 'Active' : 'Hidden'}</span>
        </div>
        <p class="text-sm text-gray-300 italic mb-2">"${escHtml(r.review_text)}"</p>
        <p class="text-sm text-white font-medium">${escHtml(r.reviewer_name)}</p>
        <p class="text-xs text-gray-500">${escHtml(r.reviewer_title || '')}</p>
      </div>
      <div class="flex gap-2 flex-shrink-0">
        <button onclick="editReview(${JSON.stringify(r).replace(/"/g,'&quot;')})" class="p-1.5 text-gray-500 hover:text-yellow-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
        <button onclick="deleteReview(${r.id})" class="p-1.5 text-gray-500 hover:text-red-400 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
      </div>
    </div>
  `).join('');
  lucide.createIcons();
}

function openReviewModal() {
  document.getElementById('reviewModal').classList.remove('hidden');
  document.getElementById('reviewModalTitle').textContent = 'Add Review';
  document.getElementById('reviewForm').reset();
  document.getElementById('reviewId').value = '';
}

function editReview(r) {
  document.getElementById('reviewModal').classList.remove('hidden');
  document.getElementById('reviewModalTitle').textContent = 'Edit Review';
  document.getElementById('reviewId').value = r.id;
  document.getElementById('rName').value = r.reviewer_name;
  document.getElementById('rTitle').value = r.reviewer_title || '';
  document.getElementById('rText').value = r.review_text;
  document.getElementById('rRating').value = r.rating;
  document.getElementById('rActive').checked = r.active == 1;
}

function closeReviewModal() { document.getElementById('reviewModal').classList.add('hidden'); }

document.getElementById('reviewForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await adminAjaxForm(this, { action: 'save_review' });
  if (data.success) { showToast(data.message); closeReviewModal(); loadReviews(); }
  else showToast(data.error || 'Error', 'error');
});

async function deleteReview(id) {
  if (!confirm('Delete this review?')) return;
  const data = await adminAjax({ action: 'delete_review', id });
  if (data.success) { showToast('Review deleted'); loadReviews(); }
  else showToast(data.error, 'error');
}

// ---- SETTINGS ----
document.getElementById('settings-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await adminAjaxForm(this, { action: 'save_settings_bulk' });
  if (data.success) showToast('Settings saved successfully!');
  else showToast(data.error || 'Error saving settings', 'error');
});

document.getElementById('social-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await adminAjaxForm(this, { action: 'save_social' });
  if (data.success) showToast('Social links saved!');
  else showToast(data.error || 'Error', 'error');
});

// ---- TRENDING ----
async function rotateTrending() {
  const data = await adminAjax({ action: 'rotate_trending' });
  if (data.success) showToast('Trending products rotated!');
  else showToast(data.error, 'error');
}

// ---- UTILITY ----
function escHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}


// ---- API KEYS TAB ----
function toggleKeyVis(fieldId) {
  const el = document.getElementById(fieldId);
  if (!el) return;
  el.type = el.type === 'password' ? 'text' : 'password';
  const btn = el.nextElementSibling;
  if (btn) btn.textContent = el.type === 'password' ? 'Show' : 'Hide';
}

async function loadApiKeys() {
  try {
    const res = await fetch('/admin/ajax.php?action=get_api_keys');
    const data = await res.json();
    if (data.success && data.data) {
      ['anthropic_api_key','openai_api_key','gemini_api_key'].forEach(k => {
        const el = document.getElementById(k);
        if (el && data.data[k]) {
          el.value = data.data[k];
          el.type = 'password'; // start masked
        }
      });
    }
  } catch(e) { /* silent */ }
}

document.getElementById('apikeys-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  fd.append('action', 'save_api_keys');
  const res = await fetch('/admin/ajax.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) showToast('API keys saved successfully!');
  else showToast(data.error || 'Error saving keys', 'error');
});

// ---- INIT ----
switchTab('dashboard');
lucide.createIcons();

// Load categories for product form
async function loadCategoriesForSelect() {
  const res = await fetch('/admin/ajax.php?action=get_categories');
  const data = await res.json();
  const sel = document.getElementById('pCategory');
  if (sel && data.data) {
    sel.innerHTML = '<option value="">-- Select --</option>' + 
      data.data.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
  }
}
loadCategoriesForSelect();
</script>
</body>
</html>

<?php
// api/migrate.php — Run to add new tables (journal, pages, faqs)
// Access: /setup.php?key=onyx_setup_2025_secret  (uses same key as setup)

$setupKey = getenv('SETUP_KEY') ?: 'onyx_setup_2025_secret';
if (($_GET['key'] ?? '') !== $setupKey) {
    http_response_code(403);
    die('403 Forbidden. Provide correct setup key via ?key= parameter.');
}

require_once __DIR__ . '/../config/database.php';
$db  = Database::getInstance();
$pdo = $db->getConnection();

$log = [];

function run(PDO $pdo, string $sql, array &$log): void {
    try {
        $pdo->exec($sql);
        $log[] = ['ok', substr($sql, 0, 90)];
    } catch (PDOException $e) {
        $m = $e->getMessage();
        if (str_contains($m,'already exists') || str_contains($m,'duplicate key') || str_contains($m,'unique constraint')) {
            $log[] = ['skip', 'Already exists: ' . substr($sql,0,80)];
        } else {
            $log[] = ['err', $m];
        }
    }
}

// ---- Journal Posts ----
run($pdo, "CREATE TABLE IF NOT EXISTS journal_posts (
  id           SERIAL PRIMARY KEY,
  title        VARCHAR(255) NOT NULL,
  slug         VARCHAR(255) NOT NULL UNIQUE,
  excerpt      TEXT DEFAULT '',
  content      TEXT DEFAULT '',
  cover_image  VARCHAR(500) DEFAULT '',
  category     VARCHAR(100) DEFAULT 'Buying Guide',
  published    SMALLINT DEFAULT 0,
  meta_title   VARCHAR(255) DEFAULT '',
  meta_description TEXT DEFAULT '',
  created_at   TIMESTAMP DEFAULT NOW(),
  updated_at   TIMESTAMP DEFAULT NOW()
)", $log);

// ---- Static Pages ----
run($pdo, "CREATE TABLE IF NOT EXISTS pages (
  id           SERIAL PRIMARY KEY,
  slug         VARCHAR(100) NOT NULL UNIQUE,
  title        VARCHAR(255) NOT NULL,
  content      TEXT DEFAULT '',
  meta_title   VARCHAR(255) DEFAULT '',
  meta_description TEXT DEFAULT '',
  published    SMALLINT DEFAULT 1,
  updated_at   TIMESTAMP DEFAULT NOW()
)", $log);

// ---- FAQs ----
run($pdo, "CREATE TABLE IF NOT EXISTS faqs (
  id         SERIAL PRIMARY KEY,
  question   TEXT NOT NULL,
  answer     TEXT NOT NULL,
  sort_order INTEGER DEFAULT 0,
  active     SMALLINT DEFAULT 1
)", $log);

// ---- Seed Journal Posts ----
$posts = [
  [
    'The Ultimate Guide to Luxury Watches in 2025',
    'ultimate-guide-luxury-watches-2025',
    'Discover the timepieces that define modern luxury — from the iconic Rolex Submariner to Patek Philippe complications that take years to craft.',
    '<h2>Why Luxury Watches Are the Ultimate Status Symbol</h2><p>A fine timepiece is more than an instrument for telling time — it is a testament to human craftsmanship, an heirloom in the making, and a statement of refined taste. In 2025, the luxury watch market continues to flourish as collectors and first-time buyers alike seek the permanence and prestige that only a mechanical marvel can provide.</p><h2>The Icons</h2><p><strong>Rolex Submariner</strong> remains the definitive luxury sports watch. Water-resistant to 300 metres, featuring the iconic Oyster case and ceramic bezel, it is equally at home on a yacht or in a boardroom. Prices start from £8,000 for pre-owned pieces.</p><p><strong>Patek Philippe Nautilus</strong> is arguably the most coveted watch of the modern era. Its distinctive porthole case design by Gérald Genta has made it the holy grail for serious collectors.</p><h2>Buying Your First Luxury Watch</h2><p>Begin with your budget and consider whether you prefer mechanical or quartz movement. For those new to horology, a Swiss-made automatic watch from brands like TAG Heuer or Longines offers exceptional quality at a more accessible price point.</p>',
    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&h=500&fit=crop',
    'Watches',
  ],
  [
    'The Art of Fragrance: Building Your Luxury Scent Collection',
    'art-of-fragrance-luxury-scent-collection',
    'From Dior Sauvage to Tom Ford Oud Wood — our experts guide you through building a fragrance wardrobe that speaks before you do.',
    '<h2>Fragrance as Identity</h2><p>Scent is the most intimate of luxury indulgences. Unlike a watch or a wallet, a fragrance becomes part of you — absorbed into your skin, lingering in rooms you have long left. Choosing the right fragrance is choosing how the world remembers you.</p><h2>Understanding Fragrance Families</h2><p><strong>Fresh & Citrus</strong> fragrances are ideal for daytime and warm weather. Acqua di Gio by Giorgio Armani is the archetypal fresh fragrance, clean and effortlessly sophisticated.</p><p><strong>Oriental & Woody</strong> scents are bolder and more complex. Tom Ford Oud Wood combines rare oud wood with sandalwood and amber for a scent that is unmistakably luxurious.</p><p><strong>Aromatic & Fougère</strong> scents, typified by Dior Sauvage, occupy the space between fresh and earthy — versatile enough for any occasion.</p><h2>Building Your Collection</h2><p>Start with three fragrances: a fresh daytime scent, a woody evening scent, and a seasonal special. This gives you versatility without overwhelming your senses or your dressing table.</p>',
    'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=800&h=500&fit=crop',
    'Fragrance',
  ],
  [
    'Leather Goods That Last a Lifetime: The Connoisseur\'s Guide',
    'leather-goods-that-last-a-lifetime',
    'Quality leather tells a story. We examine the wallets, cardholders and accessories that improve with age — and the brands that make them.',
    '<h2>The Philosophy of Lasting Luxury</h2><p>In an era of fast fashion and disposable goods, genuine leather accessories represent a counter-philosophy: buy once, buy well. The finest leather improves with age, developing a patina that is uniquely yours — a record of your journeys, your habits, your story.</p><h2>What to Look For in a Luxury Wallet</h2><p><strong>Full-grain leather</strong> is the highest quality available, retaining the complete outer layer of the hide with all its natural character. It is more durable than corrected-grain leather and develops a beautiful patina over time.</p><p><strong>Hand-stitching</strong> indicates premium construction. Where machine stitching can unravel from a single broken thread, saddle stitching — two needles working in opposite directions — holds firm even if one thread breaks.</p><h2>Our Top Recommendations</h2><p>The <strong>Mont Blanc Carbon Wallet</strong> combines sophisticated carbon fibre with RFID-blocking technology, making it the choice of the modern professional. For purists, the <strong>Bellroy Hide & Seek</strong> in full-grain leather offers elegant minimalism without compromise.</p>',
    'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=500&fit=crop',
    'Leather Goods',
  ],
];

foreach ($posts as [$title, $slug, $excerpt, $content, $cover, $cat]) {
    try {
        $pdo->prepare(
            "INSERT INTO journal_posts (title,slug,excerpt,content,cover_image,category,published)
             VALUES (?,?,?,?,?,?,1)
             ON CONFLICT (slug) DO NOTHING"
        )->execute([$title, $slug, $excerpt, $content, $cover, $cat]);
        $log[] = ['ok', "Seeded journal post: $slug"];
    } catch (Exception $e) {
        $log[] = ['err', "Seed post error: " . $e->getMessage()];
    }
}

// ---- Seed Static Pages ----
$defaultPages = [
  ['privacy-policy', 'Privacy Policy',
   '<h2>Information We Collect</h2><p>Onyx &amp; Outer is an affiliate website. We collect minimal personal information: your email address if you subscribe to our newsletter, and standard server logs (IP address, browser type, pages visited). We do not operate an e-commerce platform and do not collect payment information.</p><h2>Cookies</h2><p>We use cookies to analyse site traffic via Google Analytics. You may disable cookies in your browser settings at any time. Our wishlist feature uses your browser\'s local storage — this data never leaves your device.</p><h2>Third-Party Links</h2><p>Our site contains affiliate links to third-party retailers. When you click these links and make a purchase, we may earn a small commission at no extra cost to you. Each retailer has its own privacy policy, which we encourage you to review.</p><h2>Your Rights</h2><p>You may request deletion of your email from our newsletter list at any time by contacting us. We do not sell your data to third parties.</p><h2>Contact</h2><p>For privacy enquiries, please use the contact form on our website.</p>',
   'Privacy Policy — Onyx & Outer',
   'Learn how Onyx & Outer collects, uses and protects your personal data.'],
  ['terms-conditions', 'Terms &amp; Conditions',
   '<h2>Acceptance of Terms</h2><p>By accessing and using the Onyx &amp; Outer website, you accept and agree to be bound by these Terms and Conditions.</p><h2>Nature of Service</h2><p>Onyx &amp; Outer is a curated luxury affiliate platform. We provide editorial content, product recommendations, and affiliate links to authorised third-party retailers. We do not sell products directly and are not responsible for transactions that occur on third-party websites.</p><h2>Affiliate Relationships</h2><p>We participate in affiliate programmes. When you click a link and make a purchase, we may earn a commission. This does not affect the price you pay and does not influence our editorial recommendations.</p><h2>Intellectual Property</h2><p>All content on this site — including text, images, logos and design — is the property of Onyx &amp; Outer or its licensors. You may not reproduce or distribute our content without written permission.</p><h2>Limitation of Liability</h2><p>Onyx &amp; Outer is not liable for the quality, accuracy or availability of products featured on third-party retailer websites. Purchase decisions are made at your own risk.</p>',
   'Terms & Conditions — Onyx & Outer',
   'Read the terms and conditions governing use of the Onyx & Outer website.'],
  ['affiliate-disclosure', 'Affiliate Disclosure',
   '<h2>Our Affiliate Commitment</h2><p>Onyx &amp; Outer participates in affiliate marketing programmes. This means we earn a small commission when you click certain links on our site and subsequently make a purchase from the retailer — at absolutely no additional cost to you.</p><h2>How It Works</h2><p>When you click a "Visit Store" or product link on Onyx &amp; Outer, you are directed to an authorised retailer\'s website. If you complete a purchase within a defined window (typically 24-72 hours), we receive a small referral fee from the retailer.</p><h2>Our Editorial Independence</h2><p>Affiliate relationships do not influence our product selections or editorial reviews. We curate only products we believe represent genuine luxury value. Our recommendations are based on quality, craftsmanship, brand reputation and verified customer satisfaction — not commission rates.</p><h2>Transparency</h2><p>In accordance with FTC guidelines and UK ASA standards, affiliate links on this site are clearly indicated. We believe transparency builds trust, and trust is the foundation of the Onyx &amp; Outer experience.</p>',
   'Affiliate Disclosure — Onyx & Outer',
   'Onyx & Outer is an affiliate website. Learn how our affiliate relationships work.'],
  ['editorial-policy', 'Editorial Policy',
   '<h2>Our Curation Standard</h2><p>Every product featured on Onyx &amp; Outer is selected by our editorial team according to strict criteria: brand heritage, material quality, craftsmanship, customer reviews from verified purchasers, and our team\'s hands-on assessment where possible.</p><h2>Independence</h2><p>Our editorial decisions are made independently of commercial relationships. Brands cannot pay to be featured. Our affiliate partnerships are established after — not before — editorial selection.</p><h2>Accuracy</h2><p>We strive to ensure all product information is accurate at time of publication. Product specifications, availability and pricing can change; we encourage you to verify details on the retailer\'s website before purchasing.</p><h2>Updates & Corrections</h2><p>We review and update our content regularly. If you believe any information is inaccurate, please contact us — we take corrections seriously and will address them promptly.</p><h2>AI Concierge</h2><p>Our AI concierge is powered by large language models and is designed to assist you in discovering products. AI responses are informational and should not be considered professional advice. Always verify product details with the retailer.</p>',
   'Editorial Policy — Onyx & Outer',
   'How Onyx & Outer selects, reviews and presents luxury products.'],
  ['contact', 'Contact Us',
   '<h2>Get in Touch</h2><p>We would love to hear from you. Whether you have a question about a product, a partnership enquiry, or simply wish to share your experience with Onyx &amp; Outer — our team is here to help.</p><contact-form></contact-form><h2>Response Times</h2><p>We aim to respond to all enquiries within 2 business days.</p><h2>Press &amp; Partnerships</h2><p>For press enquiries, brand collaborations, or affiliate programme applications, please include "Press" or "Partnership" in your subject line.</p>',
   'Contact — Onyx & Outer',
   'Contact the Onyx & Outer team with questions, partnerships or press enquiries.'],
];

foreach ($defaultPages as [$slug, $title, $content, $metaTitle, $metaDesc]) {
    try {
        $pdo->prepare(
            "INSERT INTO pages (slug,title,content,meta_title,meta_description,published)
             VALUES (?,?,?,?,?,1)
             ON CONFLICT (slug) DO NOTHING"
        )->execute([$slug, $title, $content, $metaTitle, $metaDesc]);
        $log[] = ['ok', "Seeded page: $slug"];
    } catch (Exception $e) {
        $log[] = ['err', "Seed page error: " . $e->getMessage()];
    }
}

// ---- Seed FAQs ----
$faqs = [
  ['Is Onyx & Outer a real shop?', 'Onyx & Outer is a curated luxury affiliate platform. We showcase and recommend premium products from authorised retailers. When you click "Visit Store", you are taken to the retailer\'s website to complete your purchase. We earn a small commission at no extra cost to you.', 1],
  ['Are the products authentic?', 'Absolutely. We only link to authorised retailers and reputable sellers of genuine luxury goods. We never feature counterfeit or grey-market products.', 2],
  ['Can I return a product?', 'Returns are handled by the retailer where you made your purchase. Each retailer has its own returns policy, which you can find on their website. We recommend reviewing the returns policy before purchasing.', 3],
  ['How does the AI concierge work?', 'Our AI concierge is powered by advanced language models trained to assist you in discovering luxury products. It can recommend items, compare categories, and answer questions about our collection. Simply type your question in the chat window.', 4],
  ['How do I add items to my wishlist?', 'Hover over any product card and click the heart icon to add it to your wishlist. Your wishlist is saved in your browser and persists across visits. Click the heart icon in the navigation bar to view your saved items.', 5],
  ['Do you ship products?', 'As an affiliate platform, we do not handle shipping. Delivery is managed by the retailer where you purchase. Delivery times and costs vary by retailer and destination.', 6],
  ['How can I contact you?', 'Use our Contact page to reach the Onyx & Outer team. We typically respond within 2 business days.', 7],
];

foreach ($faqs as [$q, $a, $sort]) {
    try {
        $pdo->prepare(
            "INSERT INTO faqs (question,answer,sort_order,active)
             VALUES (?,?,?,1)
             ON CONFLICT DO NOTHING"
        )->execute([$q, $a, $sort]);
        $log[] = ['ok', "Seeded FAQ: " . substr($q, 0, 60)];
    } catch (Exception $e) {
        $log[] = ['err', "Seed FAQ error: " . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration — Onyx & Outer</title>
<style>
body { font-family: monospace; background: #111; color: #ccc; padding: 2rem; max-width: 900px; margin: 0 auto; }
h1 { color: #C8A46B; }
.ok   { color: #6ee7b7; }
.err  { color: #fca5a5; }
.skip { color: #94a3b8; }
.box  { background: #1c1c1c; border: 1px solid #333; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
a { color: #C8A46B; }
</style>
</head>
<body>
<h1>⚙️ Onyx & Outer — Migration v2</h1>
<div class="box">
<?php foreach ($log as [$type, $msg]): ?>
  <p class="<?= $type ?>">
    <?= $type === 'ok' ? '✓' : ($type === 'err' ? '✗' : '↷') ?>
    <?= htmlspecialchars($msg) ?>
  </p>
<?php endforeach; ?>
</div>
<?php $errors = array_filter($log, fn($l) => $l[0] === 'err'); ?>
<?php if (!$errors): ?>
<div class="box">
  <p class="ok">✅ Migration complete! New tables and seed data are ready.</p>
  <p>You can now:</p>
  <ul>
    <li>Visit <a href="/journal">/journal</a> to see the blog</li>
    <li>Visit <a href="/page/privacy-policy">/page/privacy-policy</a> for static pages</li>
    <li>Visit <a href="/admin/">/admin/</a> → Journal tab to manage posts</li>
  </ul>
</div>
<?php else: ?>
<div class="box">
  <p class="err">⚠️ <?= count($errors) ?> error(s) occurred. Some tables may not have been created.</p>
</div>
<?php endif; ?>
</body>
</html>

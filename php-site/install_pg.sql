-- install_pg.sql — PostgreSQL schema for Onyx & Outer
-- Run this once via the Vercel setup route or psql.

SET client_encoding = 'UTF8';

-- PHP Sessions (database-backed, required for Vercel)
CREATE TABLE IF NOT EXISTS php_sessions (
    session_id   VARCHAR(128) NOT NULL,
    session_data TEXT         NOT NULL DEFAULT '',
    expires_at   TIMESTAMP    NOT NULL,
    PRIMARY KEY  (session_id)
);

-- Rate limiting cache
CREATE TABLE IF NOT EXISTS rate_limit_cache (
    id        SERIAL PRIMARY KEY,
    rate_key  VARCHAR(255) NOT NULL,
    hit_time  INTEGER      NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_rate_limit_key ON rate_limit_cache (rate_key, hit_time);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    slug       VARCHAR(100)  NOT NULL,
    subtitle   VARCHAR(100)  DEFAULT NULL,
    thumbnail  VARCHAR(255)  DEFAULT NULL,
    sort_order INTEGER       DEFAULT 0,
    active     SMALLINT      DEFAULT 1,
    created_at TIMESTAMP     NOT NULL DEFAULT NOW(),
    UNIQUE (slug)
);

-- Products
CREATE TABLE IF NOT EXISTS products (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(200) NOT NULL,
    category_id   INTEGER      DEFAULT NULL REFERENCES categories(id) ON DELETE SET NULL,
    description   TEXT         DEFAULT NULL,
    affiliate_url VARCHAR(500) DEFAULT NULL,
    featured      SMALLINT     DEFAULT 0,
    active        SMALLINT     DEFAULT 1,
    sort_order    INTEGER      DEFAULT 0,
    created_at    TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Auto-update updated_at on products
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN NEW.updated_at = NOW(); RETURN NEW; END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_products_updated ON products;
CREATE TRIGGER trg_products_updated
    BEFORE UPDATE ON products
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- Product Images
CREATE TABLE IF NOT EXISTS product_images (
    id         SERIAL   PRIMARY KEY,
    product_id INTEGER  NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    image_path VARCHAR(255) NOT NULL,
    is_primary SMALLINT DEFAULT 0,
    sort_order INTEGER  DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_product_images_pid ON product_images (product_id);

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    id             SERIAL   PRIMARY KEY,
    reviewer_name  VARCHAR(100) NOT NULL,
    reviewer_title VARCHAR(100) DEFAULT NULL,
    review_text    TEXT         NOT NULL,
    rating         SMALLINT     DEFAULT 5,
    active         SMALLINT     DEFAULT 1,
    sort_order     INTEGER      DEFAULT 0,
    created_at     TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Site Settings
CREATE TABLE IF NOT EXISTS site_settings (
    id            SERIAL   PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL,
    setting_value TEXT         DEFAULT NULL,
    updated_at    TIMESTAMP    NOT NULL DEFAULT NOW(),
    UNIQUE (setting_key)
);

-- Social Links
CREATE TABLE IF NOT EXISTS social_links (
    id       SERIAL   PRIMARY KEY,
    platform VARCHAR(50)  NOT NULL,
    url      VARCHAR(500) DEFAULT NULL,
    active   SMALLINT     DEFAULT 1,
    UNIQUE (platform)
);

-- Chat Context Cache
CREATE TABLE IF NOT EXISTS chat_context (
    id            SERIAL   PRIMARY KEY,
    context_key   VARCHAR(100) NOT NULL,
    context_value TEXT         DEFAULT NULL,
    updated_at    TIMESTAMP    NOT NULL DEFAULT NOW(),
    UNIQUE (context_key)
);

-- Admin Sessions (extra security layer)
CREATE TABLE IF NOT EXISTS admin_sessions (
    id            SERIAL   PRIMARY KEY,
    session_token VARCHAR(64)  NOT NULL,
    ip_address    VARCHAR(45)  DEFAULT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT NOW(),
    expires_at    TIMESTAMP    NOT NULL,
    UNIQUE (session_token)
);

-- Trending Log
CREATE TABLE IF NOT EXISTS trending_log (
    id          SERIAL    PRIMARY KEY,
    product_ids TEXT      NOT NULL,
    rotated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Subscribers (created lazily in subscribe.php, pre-create here too)
CREATE TABLE IF NOT EXISTS subscribers (
    id            SERIAL    PRIMARY KEY,
    email         VARCHAR(255) NOT NULL,
    subscribed_at TIMESTAMP    NOT NULL DEFAULT NOW(),
    ip_address    VARCHAR(45)  DEFAULT NULL,
    UNIQUE (email)
);

-- ====================================================================
-- Default Data
-- ====================================================================

INSERT INTO categories (name, slug, subtitle, thumbnail, sort_order) VALUES
('Watches',     'watches',     'Timepieces',     'https://images.unsplash.com/photo-1523170335684-f042f1f83556?w=200&h=200&fit=crop', 1),
('Perfumes',    'perfumes',    'Fragrances',     'https://images.unsplash.com/photo-1594736797933-d0501ba2fe65?w=200&h=200&fit=crop', 2),
('Wallets',     'wallets',     'Leather Goods',  'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=200&h=200&fit=crop', 3),
('Gifts',       'gifts',       'Luxury Gifting', 'https://images.unsplash.com/photo-1607705703571-076512801547?w=200&h=200&fit=crop', 4),
('Purses',      'purses',      'Designer Bags',  'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=200&h=200&fit=crop', 5),
('Keychains',   'keychains',   'Accessories',    'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=200&h=200&fit=crop', 6),
('Phone Cases', 'phone-cases', 'Premium Cases',  'https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=200&h=200&fit=crop', 7),
('All Luxury',  'all',         'Full Collection','https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=200&h=200&fit=crop', 8)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO products (name, category_id, description, affiliate_url, featured, active) VALUES
('Rolex Submariner',       1, 'Iconic diving watch with unmatched precision and Swiss craftsmanship. Water-resistant to 300m.', '#', 1, 1),
('Dior Sauvage',           2, 'A bold, fresh fragrance for the modern man. Notes of ambroxan and spicy pepper.', '#', 1, 1),
('Mont Blanc Carbon Wallet',3, 'Sleek carbon fiber with Italian leather. Premium construction for the discerning professional.', '#', 1, 1),
('Hermès Birkin Mini',     5, 'The pinnacle of luxury handbag craftsmanship. Hand-stitched with the finest leather.', '#', 1, 1),
('Cartier Keychain',       6, 'Panthère de Cartier in brushed palladium. Elegant luxury for your essentials.', '#', 1, 1),
('Pitaka MagEZ Case',      7, 'Aramid fiber precision for iPhone Pro. Lightweight yet incredibly durable.', '#', 1, 1);

INSERT INTO product_images (product_id, image_path, is_primary, sort_order)
SELECT p.id, d.image_path, d.is_primary, d.sort_order
FROM (VALUES
    ('Rolex Submariner',        'https://images.unsplash.com/photo-1523170335684-f042f1f83556?w=600&h=600&fit=crop', 1, 0),
    ('Rolex Submariner',        'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=600&h=600&fit=crop', 0, 1),
    ('Dior Sauvage',            'https://images.unsplash.com/photo-1594736797933-d0501ba2fe65?w=600&h=600&fit=crop', 1, 0),
    ('Dior Sauvage',            'https://images.unsplash.com/photo-1576426863848-c21cb6999d4d?w=600&h=600&fit=crop', 0, 1),
    ('Mont Blanc Carbon Wallet','https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=600&h=600&fit=crop', 1, 0),
    ('Mont Blanc Carbon Wallet','https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&h=600&fit=crop', 0, 1),
    ('Hermès Birkin Mini',      'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&h=600&fit=crop', 1, 0),
    ('Hermès Birkin Mini',      'https://images.unsplash.com/photo-1556821552-5f06b5c00508?w=600&h=600&fit=crop', 0, 1),
    ('Cartier Keychain',        'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=600&h=600&fit=crop', 1, 0),
    ('Cartier Keychain',        'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&h=600&fit=crop', 0, 1),
    ('Pitaka MagEZ Case',       'https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=600&h=600&fit=crop', 1, 0),
    ('Pitaka MagEZ Case',       'https://images.unsplash.com/photo-1592286927505-1def25115558?w=600&h=600&fit=crop', 0, 1)
) AS d(pname, image_path, is_primary, sort_order)
JOIN products p ON p.name = d.pname;

INSERT INTO reviews (reviewer_name, reviewer_title, review_text, rating, active, sort_order) VALUES
('Alexander M.', 'Watch Enthusiast',  'Finally a platform that curates luxury without the noise. Found my perfect timepiece through their recommendation.', 5, 1, 1),
('Sophia R.',    'Fragrance Collector','The editorial content helped me understand what makes a fragrance truly premium. Exceptional taste.',                5, 1, 2),
('James T.',     'Loyal Client',       'Their gift guides saved me during the holidays. Every recommendation was spot-on and beautifully presented.',        5, 1, 3)
ON CONFLICT DO NOTHING;

INSERT INTO site_settings (setting_key, setting_value) VALUES
('hero_heading',       'Curated Luxury Beyond Ordinary'),
('hero_subtext',       'Discover handpicked premium accessories from the world''s finest brands. Elegance meets craftsmanship.'),
('cta_text',           'Explore Collection'),
('stats_products',     '500+'),
('stats_brands',       '50+'),
('stats_clients',      '10K+'),
('about_text',         'Onyx & Outer is a curated luxury affiliate platform dedicated to connecting discerning shoppers with the world''s finest products. Every item is hand-selected by our team of luxury experts.'),
('footer_tagline',     'Curated Luxury Beyond Ordinary'),
('newsletter_title',   'Join The Inner Circle'),
('newsletter_subtitle','Receive exclusive product drops, luxury guides, and curated recommendations.'),
('journal_title',      'Luxury Buying Guides'),
('journal_subtitle',   'Expert curation, honest reviews, and insider knowledge to help you discover the finest luxury products.'),
('meta_description',   'Onyx & Outer — Curated luxury accessories, watches, perfumes, wallets and more from the world''s finest brands.'),
('google_analytics_id',''),
('trending_last_rotated','0')
ON CONFLICT (setting_key) DO NOTHING;

INSERT INTO social_links (platform, url, active) VALUES
('instagram', '#', 1),
('twitter',   '#', 1),
('facebook',  '#', 1)
ON CONFLICT (platform) DO NOTHING;

-- ====================================================
-- v2 migration: Journal, Pages, FAQs
-- ====================================================

CREATE TABLE IF NOT EXISTS journal_posts (
  id SERIAL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  excerpt TEXT DEFAULT '',
  content TEXT DEFAULT '',
  cover_image VARCHAR(500) DEFAULT '',
  category VARCHAR(100) DEFAULT 'Buying Guide',
  published SMALLINT DEFAULT 0,
  meta_title VARCHAR(255) DEFAULT '',
  meta_description TEXT DEFAULT '',
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS pages (
  id SERIAL PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  content TEXT DEFAULT '',
  meta_title VARCHAR(255) DEFAULT '',
  meta_description TEXT DEFAULT '',
  published SMALLINT DEFAULT 1,
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS faqs (
  id SERIAL PRIMARY KEY,
  question TEXT NOT NULL,
  answer TEXT NOT NULL,
  sort_order INTEGER DEFAULT 0,
  active SMALLINT DEFAULT 1
);

INSERT INTO journal_posts (title,slug,excerpt,content,cover_image,category,published) VALUES
('The Ultimate Guide to Luxury Watches in 2025','ultimate-guide-luxury-watches-2025','Discover the timepieces that define modern luxury — from the iconic Rolex Submariner to Patek Philippe complications that take years to craft.','<h2>Why Luxury Watches Are the Ultimate Status Symbol</h2><p>A fine timepiece is more than an instrument for telling time — it is a testament to human craftsmanship, an heirloom in the making, and a statement of refined taste.</p><h2>The Icons</h2><p><strong>Rolex Submariner</strong> remains the definitive luxury sports watch. Water-resistant to 300 metres, featuring the iconic Oyster case and ceramic bezel, it is equally at home on a yacht or in a boardroom.</p><h2>Buying Your First Luxury Watch</h2><p>Begin with your budget and consider whether you prefer mechanical or quartz movement. For those new to horology, a Swiss-made automatic watch from brands like TAG Heuer or Longines offers exceptional quality at a more accessible price point.</p>','https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&h=500&fit=crop','Watches',1),
('The Art of Fragrance: Building Your Luxury Scent Collection','art-of-fragrance-luxury-scent-collection','From Dior Sauvage to Tom Ford Oud Wood — our experts guide you through building a fragrance wardrobe that speaks before you do.','<h2>Fragrance as Identity</h2><p>Scent is the most intimate of luxury indulgences. Unlike a watch or a wallet, a fragrance becomes part of you — absorbed into your skin, lingering in rooms you have long left.</p><h2>Understanding Fragrance Families</h2><p><strong>Fresh and Citrus</strong> fragrances are ideal for daytime and warm weather. <strong>Oriental and Woody</strong> scents like Tom Ford Oud Wood are bolder and more complex.</p>','https://images.unsplash.com/photo-1594035910387-fea47794261f?w=800&h=500&fit=crop','Fragrance',1),
('Leather Goods That Last a Lifetime: The Connoisseur Guide','leather-goods-that-last-a-lifetime','Quality leather tells a story. We examine the wallets, cardholders and accessories that improve with age — and the brands that make them.','<h2>The Philosophy of Lasting Luxury</h2><p>In an era of fast fashion and disposable goods, genuine leather accessories represent a counter-philosophy: buy once, buy well. The finest leather improves with age, developing a patina that is uniquely yours.</p><h2>What to Look For</h2><p><strong>Full-grain leather</strong> is the highest quality available. <strong>Hand-stitching</strong> indicates premium construction.</p>','https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=500&fit=crop','Leather Goods',1)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO pages (slug,title,content,meta_title,meta_description,published) VALUES
('privacy-policy','Privacy Policy','<h2>Information We Collect</h2><p>Onyx &amp; Outer is an affiliate website. We collect minimal personal information: your email address if you subscribe to our newsletter, and standard server logs. We do not collect payment information.</p><h2>Cookies</h2><p>We use cookies to analyse site traffic via Google Analytics. Our wishlist feature uses your browser local storage — this data never leaves your device.</p><h2>Third-Party Links</h2><p>Our site contains affiliate links to third-party retailers. Each retailer has its own privacy policy, which we encourage you to review.</p><h2>Your Rights</h2><p>You may request deletion of your email from our newsletter list at any time by contacting us.</p>','Privacy Policy — Onyx & Outer','Learn how Onyx & Outer collects, uses and protects your personal data.',1),
('terms-conditions','Terms & Conditions','<h2>Acceptance of Terms</h2><p>By accessing and using the Onyx &amp; Outer website, you accept and agree to be bound by these Terms and Conditions.</p><h2>Nature of Service</h2><p>Onyx &amp; Outer is a curated luxury affiliate platform. We provide editorial content, product recommendations, and affiliate links to authorised third-party retailers.</p><h2>Affiliate Relationships</h2><p>We participate in affiliate programmes. When you click a link and make a purchase, we may earn a commission. This does not affect the price you pay.</p><h2>Limitation of Liability</h2><p>Onyx &amp; Outer is not liable for the quality, accuracy or availability of products featured on third-party retailer websites.</p>','Terms & Conditions — Onyx & Outer','Read the terms and conditions governing use of the Onyx & Outer website.',1),
('affiliate-disclosure','Affiliate Disclosure','<h2>Our Affiliate Commitment</h2><p>Onyx &amp; Outer participates in affiliate marketing programmes. We earn a small commission when you click certain links on our site and subsequently make a purchase from the retailer — at no additional cost to you.</p><h2>Editorial Independence</h2><p>Affiliate relationships do not influence our product selections or editorial reviews. We curate only products we believe represent genuine luxury value.</p>','Affiliate Disclosure — Onyx & Outer','Onyx & Outer is an affiliate website. Learn how our affiliate relationships work.',1),
('editorial-policy','Editorial Policy','<h2>Our Curation Standard</h2><p>Every product featured on Onyx &amp; Outer is selected according to strict criteria: brand heritage, material quality, craftsmanship, and verified customer satisfaction.</p><h2>Independence</h2><p>Our editorial decisions are made independently of commercial relationships. Brands cannot pay to be featured.</p>','Editorial Policy — Onyx & Outer','How Onyx & Outer selects, reviews and presents luxury products.',1),
('faq','Frequently Asked Questions','','FAQ — Onyx & Outer','Answers to common questions about Onyx & Outer.',1),
('contact','Contact Us','<h2>Get in Touch</h2><p>We would love to hear from you.</p><contact-form></contact-form>','Contact — Onyx & Outer','Contact the Onyx & Outer team.',1)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO faqs (question,answer,sort_order,active) VALUES
('Is Onyx & Outer a real shop?','Onyx & Outer is a curated luxury affiliate platform. When you click "Visit Store", you are taken to the retailer website to complete your purchase.',1,1),
('Are the products authentic?','Absolutely. We only link to authorised retailers and reputable sellers of genuine luxury goods.',2,1),
('Can I return a product?','Returns are handled by the retailer where you made your purchase. Each retailer has its own returns policy.',3,1),
('How does the AI concierge work?','Our AI concierge is powered by advanced language models and can recommend items, compare categories, and answer questions about our collection.',4,1),
('How do I add items to my wishlist?','Hover over any product card and click the heart icon to add it to your wishlist. Your wishlist is saved in your browser.',5,1),
('How can I contact you?','Use our Contact page to reach the Onyx & Outer team. We typically respond within 2 business days.',6,1)
ON CONFLICT DO NOTHING;

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

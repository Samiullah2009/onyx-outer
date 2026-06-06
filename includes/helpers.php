<?php
// includes/helpers.php — PostgreSQL edition

require_once __DIR__ . '/../config/database.php';

class SiteData {
    private static Database $db;

    private static function db(): Database {
        if (!isset(self::$db)) self::$db = Database::getInstance();
        return self::$db;
    }

    // ---- Settings ----
    public static function getSetting(string $key, string $default = ''): string {
        $row = self::db()->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
        return $row ? ($row['setting_value'] ?? $default) : $default;
    }

    public static function getAllSettings(): array {
        $rows = self::db()->fetchAll("SELECT setting_key, setting_value FROM site_settings");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

    public static function saveSetting(string $key, string $value): void {
        self::db()->query(
            "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
             ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value",
            [$key, $value]
        );
        self::invalidateContext();
    }

    // ---- Categories ----
    public static function getCategories(bool $activeOnly = true): array {
        $where = $activeOnly ? 'WHERE active = 1' : '';
        return self::db()->fetchAll("SELECT * FROM categories $where ORDER BY sort_order ASC, id ASC");
    }

    public static function getCategoryById(int $id): ?array {
        return self::db()->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
    }

    public static function saveCategory(array $data, ?int $id = null): int {
        if (empty($data['slug'])) {
            $data['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $data['name']));
        }
        if ($id) {
            self::db()->update('categories', $data, 'id = ?', [$id]);
            self::invalidateContext();
            return $id;
        }
        $newId = self::db()->insert('categories', $data);
        self::invalidateContext();
        return $newId;
    }

    public static function deleteCategory(int $id): void {
        self::db()->delete('categories', 'id = ?', [$id]);
        self::invalidateContext();
    }

    // ---- Products ----
    public static function getProducts(array $filters = []): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['active_only']))   { $where[] = 'p.active = 1'; }
        if (!empty($filters['featured_only'])) { $where[] = 'p.featured = 1'; }
        if (!empty($filters['category_id']))   { $where[] = 'p.category_id = ?'; $params[] = $filters['category_id']; }
        if (!empty($filters['ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['ids']), '?'));
            $where[]  = "p.id IN ($placeholders)";
            $params   = array_merge($params, $filters['ids']);
        }

        $whereStr = implode(' AND ', $where);
        $limit    = isset($filters['limit'])  ? 'LIMIT '  . (int)$filters['limit']  : '';
        $offset   = isset($filters['offset']) ? 'OFFSET ' . (int)$filters['offset'] : '';
        $order    = $filters['order'] ?? 'p.sort_order ASC, p.id ASC';

        $products = self::db()->fetchAll(
            "SELECT p.*, c.name as category_name, c.slug as category_slug
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE $whereStr ORDER BY $order $limit $offset",
            $params
        );

        foreach ($products as &$product) {
            $product['images'] = self::getProductImages($product['id']);
        }
        return $products;
    }

    public static function getProductById(int $id): ?array {
        $product = self::db()->fetchOne(
            "SELECT p.*, c.name as category_name FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id = ?",
            [$id]
        );
        if ($product) {
            $product['images'] = self::getProductImages($id);
        }
        return $product;
    }

    public static function getProductImages(int $productId): array {
        return self::db()->fetchAll(
            "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC",
            [$productId]
        );
    }

    public static function getTrendingProducts(int $count = 6): array {
        $lastRotated = (int)self::getSetting('trending_last_rotated', '0');
        if (time() - $lastRotated > 86400) {
            self::rotateTrending();
        }

        $trendingIds = self::getSetting('trending_product_ids', '');

        if (empty($trendingIds)) {
            return self::getProducts(['active_only' => true, 'featured_only' => true, 'limit' => $count]);
        }

        $ids = json_decode($trendingIds, true);
        if (empty($ids)) {
            return self::getProducts(['active_only' => true, 'limit' => $count]);
        }

        return self::getProducts(['ids' => $ids, 'active_only' => true]);
    }

    public static function rotateTrending(): void {
        // PostgreSQL: RANDOM() instead of RAND()
        $all = self::db()->fetchAll("SELECT id FROM products WHERE active = 1 ORDER BY RANDOM() LIMIT 6");
        $ids = array_column($all, 'id');

        if (!empty($ids)) {
            self::saveSetting('trending_product_ids', json_encode($ids));
            self::saveSetting('trending_last_rotated', (string)time());
            self::db()->insert('trending_log', ['product_ids' => json_encode($ids)]);
        }
    }

    public static function saveProduct(array $data, ?int $id = null): int {
        $productData = [
            'name'          => $data['name'],
            'category_id'   => $data['category_id'] ?? null,
            'description'   => $data['description'] ?? '',
            'affiliate_url' => $data['affiliate_url'] ?? '#',
            'featured'      => isset($data['featured']) ? 1 : 0,
            'active'        => isset($data['active']) ? 1 : 0,
            'sort_order'    => $data['sort_order'] ?? 0,
        ];

        if ($id) {
            self::db()->update('products', $productData, 'id = ?', [$id]);
            self::invalidateContext();
            return $id;
        }
        $newId = self::db()->insert('products', $productData);
        self::invalidateContext();
        return $newId;
    }

    public static function deleteProduct(int $id): void {
        self::db()->delete('products', 'id = ?', [$id]);
        self::invalidateContext();
    }

    public static function addProductImage(int $productId, string $imagePath, bool $isPrimary = false): void {
        if ($isPrimary) {
            self::db()->query("UPDATE product_images SET is_primary = 0 WHERE product_id = ?", [$productId]);
        }
        self::db()->insert('product_images', [
            'product_id' => $productId,
            'image_path' => $imagePath,
            'is_primary' => $isPrimary ? 1 : 0,
            'sort_order' => 0,
        ]);
    }

    public static function deleteProductImage(int $imageId): void {
        self::db()->delete('product_images', 'id = ?', [$imageId]);
    }

    // ---- Reviews ----
    public static function getReviews(bool $activeOnly = true): array {
        $where = $activeOnly ? 'WHERE active = 1' : '';
        return self::db()->fetchAll("SELECT * FROM reviews $where ORDER BY sort_order ASC, id ASC");
    }

    public static function saveReview(array $data, ?int $id = null): int {
        $reviewData = [
            'reviewer_name'  => $data['reviewer_name'],
            'reviewer_title' => $data['reviewer_title'] ?? '',
            'review_text'    => $data['review_text'],
            'rating'         => min(5, max(1, (int)($data['rating'] ?? 5))),
            'active'         => isset($data['active']) ? 1 : 0,
            'sort_order'     => $data['sort_order'] ?? 0,
        ];

        if ($id) {
            self::db()->update('reviews', $reviewData, 'id = ?', [$id]);
            return $id;
        }
        return self::db()->insert('reviews', $reviewData);
    }

    public static function deleteReview(int $id): void {
        self::db()->delete('reviews', 'id = ?', [$id]);
    }

    // ---- Social Links ----
    public static function getSocialLinks(): array {
        return self::db()->fetchAll("SELECT * FROM social_links WHERE active = 1");
    }

    public static function saveSocialLink(string $platform, string $url): void {
        self::db()->query(
            "INSERT INTO social_links (platform, url, active) VALUES (?, ?, 1)
             ON CONFLICT (platform) DO UPDATE SET url = EXCLUDED.url, active = 1",
            [$platform, $url]
        );
        self::invalidateContext();
    }

    // ---- AI Context Cache ----
    public static function getChatContext(): string {
        $row = self::db()->fetchOne("SELECT context_value FROM chat_context WHERE context_key = 'site_context'");
        if ($row && !empty($row['context_value'])) {
            return $row['context_value'];
        }
        return self::buildChatContext();
    }

    public static function buildChatContext(): string {
        $settings   = self::getAllSettings();
        $categories = self::getCategories();
        $products   = self::getProducts(['active_only' => true]);
        $socials    = self::getSocialLinks();

        $catList     = implode(', ', array_column($categories, 'name'));
        $productList = '';
        foreach ($products as $p) {
            $productList .= "- {$p['name']} (Category: {$p['category_name']}, Description: {$p['description']})\n";
        }

        $socialList = '';
        foreach ($socials as $s) {
            $socialList .= "- {$s['platform']}: {$s['url']}\n";
        }

        $context = "You are the AI concierge for Onyx & Outer, a curated luxury affiliate store. " .
            "You are helpful, elegant, knowledgeable and speak like a premium luxury brand assistant.\n\n" .
            "SITE NAME: " . ($settings['hero_heading'] ?? 'Onyx & Outer') . "\n" .
            "SITE TAGLINE: " . ($settings['hero_subtext'] ?? 'Curated Luxury Beyond Ordinary') . "\n" .
            "ABOUT: " . ($settings['about_text'] ?? '') . "\n\n" .
            "PRODUCT CATEGORIES: $catList\n\n" .
            "AVAILABLE PRODUCTS:\n$productList\n" .
            "STATS: " . ($settings['stats_products'] ?? '500+') . " products, " .
            ($settings['stats_brands'] ?? '50+') . " brands, " .
            ($settings['stats_clients'] ?? '10K+') . " happy clients.\n\n" .
            "SOCIAL MEDIA:\n$socialList\n\n" .
            "INSTRUCTIONS:\n" .
            "- Respond naturally and helpfully like a luxury brand concierge\n" .
            "- Recommend specific products from the list above when relevant\n" .
            "- Keep responses concise (2-4 sentences unless more detail is needed)\n" .
            "- Be warm, professional and elegant in tone\n" .
            "- If asked about pricing, say prices vary and direct them to check the product page\n" .
            "- Do not make up products not listed above\n" .
            "- Greet users warmly when they first connect";

        self::db()->query(
            "INSERT INTO chat_context (context_key, context_value) VALUES ('site_context', ?)
             ON CONFLICT (context_key) DO UPDATE SET context_value = EXCLUDED.context_value",
            [$context]
        );

        return $context;
    }

    public static function invalidateContext(): void {
        self::db()->query("DELETE FROM chat_context WHERE context_key = 'site_context'");
    }

    // ---- Admin Dashboard Stats ----
    public static function getDashboardStats(): array {
        $db = self::db();
        return [
            'total_products'    => $db->fetchOne("SELECT COUNT(*) AS c FROM products WHERE active = 1")['c'] ?? 0,
            'total_categories'  => $db->fetchOne("SELECT COUNT(*) AS c FROM categories WHERE active = 1")['c'] ?? 0,
            'total_reviews'     => $db->fetchOne("SELECT COUNT(*) AS c FROM reviews WHERE active = 1")['c'] ?? 0,
            'featured_products' => $db->fetchOne("SELECT COUNT(*) AS c FROM products WHERE featured = 1 AND active = 1")['c'] ?? 0,
        ];
    }
}

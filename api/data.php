<?php
// api/data.php — PostgreSQL edition

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=300');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_GET['action'] ?? 'all';

try {
    switch ($action) {
        case 'trending':
            $products = SiteData::getTrendingProducts(6);
            jsonResponse(['products' => formatProducts($products)]);
            break;

        case 'categories':
            $cats = SiteData::getCategories();
            jsonResponse(['categories' => $cats]);
            break;

        case 'product':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'Invalid ID'], 400);
            $product = SiteData::getProductById($id);
            if (!$product) jsonResponse(['error' => 'Not found'], 404);
            jsonResponse(['product' => formatProduct($product)]);
            break;

        case 'search':
            $q = sanitize($_GET['q'] ?? '');
            if (strlen($q) < 2) jsonResponse(['products' => []]);

            $db      = Database::getInstance();
            // PostgreSQL ILIKE for case-insensitive search
            $results = $db->fetchAll(
                "SELECT p.*, c.name as category_name FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.active = 1 AND (p.name ILIKE ? OR p.description ILIKE ? OR c.name ILIKE ?)
                 LIMIT 10",
                ["%$q%", "%$q%", "%$q%"]
            );
            foreach ($results as &$r) {
                $r['images'] = SiteData::getProductImages($r['id']);
            }
            jsonResponse(['products' => formatProducts($results)]);
            break;

        case 'settings':
            $settings  = SiteData::getAllSettings();
            $social    = SiteData::getSocialLinks();
            $socialMap = [];
            foreach ($social as $s) $socialMap[$s['platform']] = $s['url'];

            $safe = [
                'hero_heading','hero_subtext','cta_text',
                'stats_products','stats_brands','stats_clients',
                'about_text','footer_tagline',
                'newsletter_title','newsletter_subtitle',
                'journal_title','journal_subtitle',
                'google_analytics_id',
            ];
            $filtered = [];
            foreach ($safe as $k) $filtered[$k] = $settings[$k] ?? '';
            jsonResponse(['settings' => $filtered, 'social' => $socialMap]);
            break;

        case 'reviews':
            jsonResponse(['reviews' => SiteData::getReviews()]);
            break;

        case 'all':
        default:
            $settings = SiteData::getAllSettings();
            $safe = [
                'hero_heading','hero_subtext','cta_text',
                'stats_products','stats_brands','stats_clients',
                'about_text','footer_tagline',
                'newsletter_title','newsletter_subtitle',
                'journal_title','journal_subtitle',
            ];
            $filtered = [];
            foreach ($safe as $k) $filtered[$k] = $settings[$k] ?? '';

            $social    = SiteData::getSocialLinks();
            $socialMap = [];
            foreach ($social as $s) $socialMap[$s['platform']] = $s['url'];

            jsonResponse([
                'settings'   => $filtered,
                'social'     => $socialMap,
                'categories' => SiteData::getCategories(),
                'reviews'    => SiteData::getReviews(),
                'trending'   => formatProducts(SiteData::getTrendingProducts(6)),
            ]);
    }
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    jsonResponse(['error' => 'Server error'], 500);
}

function formatProducts(array $products): array {
    return array_map('formatProduct', $products);
}

function formatProduct(array $p): array {
    $images = array_column($p['images'] ?? [], 'image_path');
    if (empty($images)) $images = ['https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&h=600&fit=crop'];
    return [
        'id'            => $p['id'],
        'name'          => $p['name'],
        'category'      => $p['category_name'] ?? '',
        'description'   => $p['description'],
        'images'        => $images,
        'affiliate_url' => $p['affiliate_url'] ?? '#',
        'permalink'     => SITE_URL . '/?product=' . $p['id'],
        'featured'      => (bool)($p['featured'] ?? false),
    ];
}

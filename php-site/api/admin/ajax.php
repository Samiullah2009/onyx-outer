<?php
// admin/ajax.php
// Handles all admin CRUD operations

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers.php';

startSecureSession();

// All admin AJAX requires login
if (!isAdminLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

// Validate CSRF for all state-changing operations
$stateChanging = ['save_product','delete_product','save_category','delete_category',
                  'save_review','delete_review','save_setting','save_social',
                  'save_api_keys','upload_image','delete_image','set_primary_image','rotate_trending',
                  'save_journal_post','delete_journal_post','save_page','delete_page',
                  'save_faq','delete_faq','delete_subscriber','save_settings_bulk'];

if (in_array($action, $stateChanging) && !validateCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'error' => 'Invalid security token'], 403);
}

try {
    switch ($action) {

        // ---- PRODUCTS ----
        case 'get_products':
            $products = SiteData::getProducts();
            jsonResponse(['success' => true, 'data' => $products]);
            break;

        case 'get_product':
            $id = (int)($_GET['id'] ?? 0);
            $product = SiteData::getProductById($id);
            if (!$product) jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            jsonResponse(['success' => true, 'data' => $product]);
            break;

        case 'save_product':
            $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
            $data = [
                'name'          => sanitize($_POST['name'] ?? ''),
                'category_id'   => (int)($_POST['category_id'] ?? 0) ?: null,
                'description'   => sanitize($_POST['description'] ?? ''),
                'affiliate_url' => sanitizeUrl($_POST['affiliate_url'] ?? '#'),
                'featured'      => isset($_POST['featured']) ? 1 : 0,
                'active'        => isset($_POST['active']) ? 1 : 0,
                'sort_order'    => (int)($_POST['sort_order'] ?? 0),
            ];
            if (empty($data['name'])) {
                jsonResponse(['success' => false, 'error' => 'Product name is required']);
            }
            $newId = SiteData::saveProduct($data, $id);
            jsonResponse(['success' => true, 'id' => $newId, 'message' => $id ? 'Product updated' : 'Product created']);
            break;

        case 'delete_product':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'Invalid ID']);
            SiteData::deleteProduct($id);
            jsonResponse(['success' => true, 'message' => 'Product deleted']);
            break;

        case 'upload_image':
            $productId = (int)($_POST['product_id'] ?? 0);
            if (!$productId) jsonResponse(['success' => false, 'error' => 'Product ID required']);
            
            $result = handleUpload('image', 'products');
            if (!$result['success']) jsonResponse(['success' => false, 'error' => $result['error']]);
            
            $isPrimary = isset($_POST['is_primary']);
            SiteData::addProductImage($productId, $result['path'], $isPrimary);
            jsonResponse(['success' => true, 'path' => $result['path'], 'message' => 'Image uploaded']);
            break;

        case 'add_image_url':
            $productId = (int)($_POST['product_id'] ?? 0);
            $url = sanitizeUrl($_POST['url'] ?? '');
            $isPrimary = isset($_POST['is_primary']);
            
            if (!$productId || $url === '#') {
                jsonResponse(['success' => false, 'error' => 'Valid product ID and URL required']);
            }
            SiteData::addProductImage($productId, $url, $isPrimary);
            jsonResponse(['success' => true, 'message' => 'Image added']);
            break;

        case 'delete_image':
            $imageId = (int)($_POST['image_id'] ?? 0);
            if (!$imageId) jsonResponse(['success' => false, 'error' => 'Invalid ID']);
            SiteData::deleteProductImage($imageId);
            jsonResponse(['success' => true, 'message' => 'Image deleted']);
            break;

        case 'set_primary_image':
            $imageId = (int)($_POST['image_id'] ?? 0);
            $productId = (int)($_POST['product_id'] ?? 0);
            if (!$imageId || !$productId) jsonResponse(['success' => false, 'error' => 'Invalid IDs']);
            $db = Database::getInstance();
            $db->query("UPDATE product_images SET is_primary = 0 WHERE product_id = ?", [$productId]);
            $db->query("UPDATE product_images SET is_primary = 1 WHERE id = ?", [$imageId]);
            jsonResponse(['success' => true, 'message' => 'Primary image set']);
            break;

        // ---- CATEGORIES ----
        case 'get_categories':
            $cats = SiteData::getCategories(false);
            jsonResponse(['success' => true, 'data' => $cats]);
            break;

        case 'save_category':
            $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
            $data = [
                'name'       => sanitize($_POST['name'] ?? ''),
                'slug'       => sanitize($_POST['slug'] ?? ''),
                'subtitle'   => sanitize($_POST['subtitle'] ?? ''),
                'thumbnail'  => sanitizeUrl($_POST['thumbnail'] ?? ''),
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'active'     => isset($_POST['active']) ? 1 : 0,
            ];
            if (empty($data['name'])) jsonResponse(['success' => false, 'error' => 'Category name required']);
            
            // Handle thumbnail upload
            if (!empty($_FILES['thumbnail_file']['name'])) {
                $result = handleUpload('thumbnail_file', 'categories');
                if ($result['success']) $data['thumbnail'] = $result['path'];
            }
            
            $newId = SiteData::saveCategory($data, $id);
            jsonResponse(['success' => true, 'id' => $newId, 'message' => $id ? 'Category updated' : 'Category created']);
            break;

        case 'delete_category':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'Invalid ID']);
            SiteData::deleteCategory($id);
            jsonResponse(['success' => true, 'message' => 'Category deleted']);
            break;

        // ---- REVIEWS ----
        case 'get_reviews':
            $reviews = SiteData::getReviews(false);
            jsonResponse(['success' => true, 'data' => $reviews]);
            break;

        case 'save_review':
            $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
            $data = [
                'reviewer_name'  => sanitize($_POST['reviewer_name'] ?? ''),
                'reviewer_title' => sanitize($_POST['reviewer_title'] ?? ''),
                'review_text'    => sanitize($_POST['review_text'] ?? ''),
                'rating'         => (int)($_POST['rating'] ?? 5),
                'active'         => isset($_POST['active']) ? 1 : 0,
                'sort_order'     => (int)($_POST['sort_order'] ?? 0),
            ];
            if (empty($data['reviewer_name']) || empty($data['review_text'])) {
                jsonResponse(['success' => false, 'error' => 'Name and review text required']);
            }
            $newId = SiteData::saveReview($data, $id);
            jsonResponse(['success' => true, 'id' => $newId, 'message' => $id ? 'Review updated' : 'Review created']);
            break;

        case 'delete_review':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'Invalid ID']);
            SiteData::deleteReview($id);
            jsonResponse(['success' => true, 'message' => 'Review deleted']);
            break;

        // ---- SETTINGS ----
        case 'save_setting':
            $key = sanitize($_POST['key'] ?? '');
            $value = $_POST['value'] ?? '';
            
            $allowedKeys = [
                'hero_heading', 'hero_subtext', 'cta_text',
                'stats_products', 'stats_brands', 'stats_clients',
                'about_text', 'footer_tagline',
                'newsletter_title', 'newsletter_subtitle',
                'journal_title', 'journal_subtitle',
                'google_analytics_id', 'meta_description'
            ];
            if (!in_array($key, $allowedKeys)) {
                jsonResponse(['success' => false, 'error' => 'Invalid setting key']);
            }
            SiteData::saveSetting($key, sanitize($value));
            jsonResponse(['success' => true, 'message' => 'Setting saved']);
            break;

        case 'save_settings_bulk':
            $allowedKeys = [
                'hero_heading', 'hero_subtext', 'cta_text',
                'stats_products', 'stats_brands', 'stats_clients',
                'about_text', 'footer_tagline',
                'newsletter_title', 'newsletter_subtitle',
                'journal_title', 'journal_subtitle',
                'google_analytics_id', 'meta_description'
            ];
            $saved = 0;
            foreach ($allowedKeys as $key) {
                if (isset($_POST[$key])) {
                    SiteData::saveSetting($key, sanitize($_POST[$key]));
                    $saved++;
                }
            }
            jsonResponse(['success' => true, 'message' => "$saved settings saved"]);
            break;

        case 'save_social':
            $platforms = ['instagram', 'twitter', 'facebook', 'youtube', 'tiktok', 'pinterest', 'linkedin'];
            $saved = 0;
            foreach ($platforms as $p) {
                if (isset($_POST[$p])) {
                    SiteData::saveSocialLink($p, sanitizeUrl($_POST[$p]));
                    $saved++;
                }
            }
            jsonResponse(['success' => true, 'message' => "Social links saved"]);
            break;

        case 'get_social':
            $links = SiteData::getSocialLinks();
            $map = [];
            foreach ($links as $l) $map[$l['platform']] = $l['url'];
            jsonResponse(['success' => true, 'data' => $map]);
            break;

        case 'get_settings':
            $settings = SiteData::getAllSettings();
            jsonResponse(['success' => true, 'data' => $settings]);
            break;

        // ---- JOURNAL POSTS ----
        case 'get_journal_posts':
            $posts = SiteData::getJournalPosts(false);
            jsonResponse(['success' => true, 'data' => $posts]);
            break;

        case 'get_journal_post':
            $id = (int)($_GET['id'] ?? 0);
            $post = $id ? SiteData::getJournalPostById($id) : null;
            if (!$post) jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            jsonResponse(['success' => true, 'data' => $post]);
            break;

        case 'save_journal_post':
            $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
            $data = [
                'title'            => sanitize($_POST['title'] ?? ''),
                'slug'             => sanitize($_POST['slug'] ?? ''),
                'excerpt'          => sanitize($_POST['excerpt'] ?? ''),
                'content'          => $_POST['content'] ?? '',
                'cover_image'      => sanitizeUrl($_POST['cover_image'] ?? ''),
                'category'         => sanitize($_POST['category'] ?? 'Buying Guide'),
                'meta_title'       => sanitize($_POST['meta_title'] ?? ''),
                'meta_description' => sanitize($_POST['meta_description'] ?? ''),
            ];
            if (isset($_POST['published'])) $data['published'] = 1;
            if (empty($data['title'])) jsonResponse(['success' => false, 'error' => 'Title is required']);
            $newId = SiteData::saveJournalPost($data, $id);
            jsonResponse(['success' => true, 'id' => $newId, 'message' => $id ? 'Post updated' : 'Post created']);
            break;

        case 'delete_journal_post':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'Invalid ID']);
            SiteData::deleteJournalPost($id);
            jsonResponse(['success' => true, 'message' => 'Post deleted']);
            break;

        // ---- PAGES ----
        case 'get_pages':
            jsonResponse(['success' => true, 'data' => SiteData::getAllPages()]);
            break;

        case 'get_page':
            $id = (int)($_GET['id'] ?? 0);
            $db2 = Database::getInstance();
            $pg  = $db2->fetchOne("SELECT * FROM pages WHERE id = ?", [$id]);
            if (!$pg) jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            jsonResponse(['success' => true, 'data' => $pg]);
            break;

        case 'save_page':
            $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
            $data = [
                'slug'             => sanitize($_POST['slug'] ?? ''),
                'title'            => sanitize($_POST['title'] ?? ''),
                'content'          => $_POST['content'] ?? '',
                'meta_title'       => sanitize($_POST['meta_title'] ?? ''),
                'meta_description' => sanitize($_POST['meta_description'] ?? ''),
            ];
            if (isset($_POST['published'])) $data['published'] = 1;
            if (empty($data['title']) || empty($data['slug'])) {
                jsonResponse(['success' => false, 'error' => 'Title and slug are required']);
            }
            $newId = SiteData::savePage($data, $id);
            jsonResponse(['success' => true, 'id' => $newId, 'message' => 'Page saved']);
            break;

        case 'delete_page':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'Invalid ID']);
            SiteData::deletePage($id);
            jsonResponse(['success' => true, 'message' => 'Page deleted']);
            break;

        // ---- FAQS ----
        case 'get_faqs':
            jsonResponse(['success' => true, 'data' => SiteData::getFaqs(false)]);
            break;

        case 'save_faq':
            $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
            $data = [
                'question'   => sanitize($_POST['question'] ?? ''),
                'answer'     => sanitize($_POST['answer'] ?? ''),
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
            ];
            if (isset($_POST['active'])) $data['active'] = 1;
            if (empty($data['question']) || empty($data['answer'])) {
                jsonResponse(['success' => false, 'error' => 'Question and answer required']);
            }
            $newId = SiteData::saveFaq($data, $id);
            jsonResponse(['success' => true, 'id' => $newId, 'message' => 'FAQ saved']);
            break;

        case 'delete_faq':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'Invalid ID']);
            SiteData::deleteFaq($id);
            jsonResponse(['success' => true, 'message' => 'FAQ deleted']);
            break;

        // ---- SUBSCRIBERS ----
        case 'get_subscribers':
            jsonResponse(['success' => true, 'data' => SiteData::getSubscribers()]);
            break;

        case 'delete_subscriber':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'Invalid ID']);
            SiteData::deleteSubscriber($id);
            jsonResponse(['success' => true, 'message' => 'Subscriber removed']);
            break;

        // ---- TRENDING ----
        case 'rotate_trending':
            SiteData::rotateTrending();
            jsonResponse(['success' => true, 'message' => 'Trending products rotated']);
            break;

        // ---- DASHBOARD ----
        case 'dashboard_stats':
            $stats = SiteData::getDashboardStats();
            jsonResponse(['success' => true, 'data' => $stats]);
            break;

        // ---- API KEYS ----
        case 'save_api_keys':
            if (!validateCsrf($_POST['csrf_token'] ?? '')) {
                jsonResponse(['success' => false, 'error' => 'Invalid security token'], 403);
            }
            $allowed = ['anthropic_api_key', 'openai_api_key', 'gemini_api_key'];
            foreach ($allowed as $key) {
                if (array_key_exists($key, $_POST)) {
                    // Store raw (not sanitized) — API keys contain special chars
                    $val = trim($_POST[$key]);
                    SiteData::saveSetting($key, $val);
                }
            }
            jsonResponse(['success' => true, 'message' => 'API keys saved']);
            break;

        case 'get_api_keys':
            // Return masked keys for display
            $keys = ['anthropic_api_key', 'openai_api_key', 'gemini_api_key'];
            $result = [];
            foreach ($keys as $k) {
                $val = SiteData::getSetting($k, '');
                $result[$k] = $val; // full value so admin can see/edit it
            }
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Exception $e) {
    error_log('Admin AJAX error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
}

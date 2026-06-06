<?php
// sitemap.php - Dynamic XML sitemap
header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$products = SiteData::getProducts(['active_only' => true]);
$categories = SiteData::getCategories();
$base = SITE_URL;

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= $base ?>/</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <?php foreach ($categories as $cat): ?>
  <url>
    <loc><?= $base ?>/#<?= htmlspecialchars($cat['slug']) ?></loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <?php endforeach; ?>
  <?php foreach ($products as $p): ?>
  <url>
    <loc><?= $base ?>/?product=<?= $p['id'] ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($p['updated_at'] ?? 'now')) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php endforeach; ?>
</urlset>

<?php
// includes/security.php — Vercel/PostgreSQL edition
// Uses database-backed sessions and rate limiting (no file I/O).

require_once __DIR__ . '/../config/database.php';

// -------------------------------------------------------
// Database-backed PDO session handler
// Required on Vercel — filesystem is ephemeral per invocation.
// -------------------------------------------------------
class PdoSessionHandler implements SessionHandlerInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT session_data FROM php_sessions WHERE session_id = ? AND expires_at > NOW()"
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? ($row['session_data'] ?? '') : '';
        } catch (PDOException $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool {
        try {
            $expires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
            $this->pdo->prepare(
                "INSERT INTO php_sessions (session_id, session_data, expires_at)
                 VALUES (?, ?, ?)
                 ON CONFLICT (session_id) DO UPDATE
                 SET session_data = EXCLUDED.session_data,
                     expires_at   = EXCLUDED.expires_at"
            )->execute([$id, $data, $expires]);
            return true;
        } catch (PDOException $e) {
            error_log('Session write error: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool {
        try {
            $this->pdo->prepare("DELETE FROM php_sessions WHERE session_id = ?")->execute([$id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE expires_at < NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return false;
        }
    }
}

// -------------------------------------------------------
// Session bootstrap
// -------------------------------------------------------
function startSecureSession(): void {
    if (session_status() !== PHP_SESSION_NONE) return;

    try {
        $db      = Database::getInstance();
        $handler = new PdoSessionHandler($db->getConnection());
        session_set_save_handler($handler, true);
    } catch (Throwable $e) {
        // DB not available yet — fall back to default (setup page)
    }

    $cookieParams = [
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => isHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    session_set_cookie_params($cookieParams);
    session_name(SESSION_NAME);
    session_start();

    if (empty($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

function isHttps(): bool {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
        (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );
}

// -------------------------------------------------------
// CSRF
// -------------------------------------------------------
function generateCsrf(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf(?string $token): bool {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// -------------------------------------------------------
// Admin auth
// -------------------------------------------------------
function isAdminLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION['admin_logged_in']) &&
           !empty($_SESSION['admin_user']) &&
           $_SESSION['admin_user'] === ADMIN_USERNAME &&
           !empty($_SESSION['admin_expires']) &&
           $_SESSION['admin_expires'] > time();
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        http_response_code(401);
        if (isAjaxRequest()) {
            die(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }
        header('Location: /admin/login.php');
        exit;
    }
}

function adminLogin(string $username, string $password): bool {
    if ($username !== ADMIN_USERNAME) return false;
    if (!password_verify($password, ADMIN_PASSWORD_HASH)) return false;

    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user']      = $username;
    $_SESSION['admin_expires']   = time() + SESSION_LIFETIME;
    $_SESSION['admin_ip']        = $_SERVER['REMOTE_ADDR'] ?? '';
    return true;
}

function adminLogout(): void {
    startSecureSession();
    $_SESSION = [];
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

// -------------------------------------------------------
// Rate limiting — database backed (works on Vercel)
// -------------------------------------------------------
function checkRateLimit(string $key, int $maxRequests = 20, int $window = 60): bool {
    try {
        $db  = Database::getInstance();
        $pdo = $db->getConnection();
        $now = time();
        $cutoff = $now - $window;

        // Prune old entries
        $pdo->prepare(
            "DELETE FROM rate_limit_cache WHERE rate_key = ? AND hit_time < ?"
        )->execute([$key, $cutoff]);

        // Count recent hits
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS cnt FROM rate_limit_cache WHERE rate_key = ? AND hit_time >= ?"
        );
        $stmt->execute([$key, $cutoff]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = (int)($row['cnt'] ?? 0);

        if ($count >= $maxRequests) return false;

        // Record this hit
        $pdo->prepare(
            "INSERT INTO rate_limit_cache (rate_key, hit_time) VALUES (?, ?)"
        )->execute([$key, $now]);

        return true;
    } catch (PDOException $e) {
        // If DB not available, allow the request (graceful degradation)
        return true;
    }
}

// -------------------------------------------------------
// File upload (Note: on Vercel the filesystem is read-only
// except /tmp. Uploaded files are stored in /tmp and are
// NOT persistent. Use URL-based images via admin panel instead.)
// -------------------------------------------------------
function handleUpload(string $fileKey, string $subDir = 'products'): array {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error'];
    }

    $file = $_FILES[$fileKey];

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }

    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    if (!isset($allowed[$mime])) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WebP, GIF allowed.'];
    }

    $ext       = $allowed[$mime];
    $filename  = bin2hex(random_bytes(16)) . '.' . $ext;
    $uploadDir = '/tmp/uploads/' . $subDir . '/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $dest = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }

    // NOTE: On Vercel /tmp is not publicly accessible.
    // This path is only useful for inline processing.
    // For persistent images, use "Add Image URL" in the admin panel.
    return [
        'success'  => false,
        'error'    => 'Direct file upload is not supported on Vercel. Please use "Add Image URL" to add images from Unsplash or any public CDN.',
        'path'     => '',
        'filename' => $filename,
    ];
}

// -------------------------------------------------------
// Helpers
// -------------------------------------------------------
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitize(string $input): string {
    return trim(strip_tags($input));
}

function isAjaxRequest(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data);
    exit;
}

function sanitizeUrl(string $url): string {
    $url = filter_var(trim($url), FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL) && $url !== '#') {
        return '#';
    }
    return $url;
}

function productPermalink(int $id, string $name): string {
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    return SITE_URL . '/product/' . $id . '-' . trim($slug, '-');
}

function setSecurityHeaders(): void {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

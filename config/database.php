<?php
// config/database.php — Vercel/PostgreSQL edition
// All credentials come from environment variables set in Vercel dashboard.

define('DB_HOST',    getenv('PGHOST')     ?: getenv('DB_HOST')     ?: '');
define('DB_PORT',    getenv('PGPORT')     ?: getenv('DB_PORT')     ?: '5432');
define('DB_NAME',    getenv('PGDATABASE') ?: getenv('DB_NAME')     ?: '');
define('DB_USER',    getenv('PGUSER')     ?: getenv('DB_USER')     ?: '');
define('DB_PASS',    getenv('PGPASSWORD') ?: getenv('DB_PASS')     ?: '');
define('DB_URL',     getenv('POSTGRES_URL') ?: getenv('DATABASE_URL') ?: '');

define('SITE_URL',     rtrim(getenv('SITE_URL') ?: 'https://your-project.vercel.app', '/'));
define('SITE_NAME',    'Onyx & Outer');
define('UPLOAD_PATH',  '/tmp/uploads/');
define('UPLOAD_URL',   SITE_URL . '/assets/uploads/');

define('ADMIN_USERNAME',      getenv('ADMIN_USERNAME')      ?: 'phantom');
define('ADMIN_PASSWORD_HASH', getenv('ADMIN_PASSWORD_HASH') ?: '$2y$12$IpBCqL6I820Om/m7o/d.ce5LgSDdwQQc3l6QYxfVsuvHQ1Is6HWH2');

define('SESSION_NAME',     'onyx_session');
define('SESSION_LIFETIME', 3600);

define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: 'YOUR_ANTHROPIC_API_KEY_HERE');

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct() {
        try {
            $dbUrl = DB_URL;
            if (!empty($dbUrl)) {
                // Parse postgres:// URL  (Vercel provides POSTGRES_URL)
                $parsed  = parse_url($dbUrl);
                $host    = $parsed['host'] ?? DB_HOST;
                $port    = $parsed['port'] ?? DB_PORT;
                $dbname  = ltrim($parsed['path'] ?? '', '/') ?: DB_NAME;
                $user    = $parsed['user']    ?? DB_USER;
                $pass    = urldecode($parsed['pass'] ?? DB_PASS);
                $dsn     = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
            } else {
                $host   = DB_HOST;
                $port   = DB_PORT;
                $dbname = DB_NAME;
                $user   = DB_USER;
                $pass   = DB_PASS;
                $dsn    = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
            }

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->pdo = new PDO($dsn, $user ?? $parsed['user'] ?? DB_USER, $pass ?? $parsed['pass'] ?? DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed. Please check your environment variables.']));
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function insert(string $table, array $data): int {
        $cols         = implode(',', array_map(fn($k) => "\"$k\"", array_keys($data)));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $stmt = $this->query(
            "INSERT INTO \"$table\" ($cols) VALUES ($placeholders) RETURNING id",
            array_values($data)
        );
        $row = $stmt->fetch();
        return (int)($row['id'] ?? 0);
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set  = implode(',', array_map(fn($k) => "\"$k\" = ?", array_keys($data)));
        $stmt = $this->query(
            "UPDATE \"$table\" SET $set WHERE $where",
            array_merge(array_values($data), $whereParams)
        );
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int {
        $stmt = $this->query("DELETE FROM \"$table\" WHERE $where", $params);
        return $stmt->rowCount();
    }
}

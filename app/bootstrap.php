<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Ho_Chi_Minh');
if (session_status() === PHP_SESSION_NONE) {
    $sessionDirectory = (getenv('CLINIC_STORAGE_DIR') ?: dirname(__DIR__) . '/storage') . '/sessions';
    if (!is_dir($sessionDirectory) && !mkdir($sessionDirectory, 0770, true) && !is_dir($sessionDirectory)) throw new RuntimeException('Session directory cannot be created.');
    session_save_path($sessionDirectory);
    session_name('wellnest_session');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'path' => '/']);
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store');

function e($value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function base_url(string $path = ''): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/public/index.php');
    $base = preg_replace('~/(public|pages)/[^/]+$~', '', $script);
    return rtrim((string) $base, '/') . '/' . ltrim($path, '/');
}
function money(int $price): string { return number_format($price, 0, '.', ',') . ' ₫'; }
function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $directory = getenv('CLINIC_STORAGE_DIR') ?: dirname(__DIR__) . '/storage';
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Storage directory cannot be created.');
    $pdo = new PDO('sqlite:' . $directory . '/clinic.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('INSERT INTO services (id,slug,name,category,price,duration,image,alt,tag,summary,description,includes_json,prepare) VALUES (:id,:slug,:name,:category,:price,:duration,:image,:alt,:tag,:summary,:description,:includes_json,:prepare) ON CONFLICT(id) DO UPDATE SET slug=excluded.slug,name=excluded.name,category=excluded.category,price=excluded.price,duration=excluded.duration,image=excluded.image,alt=excluded.alt,tag=excluded.tag,summary=excluded.summary,description=excluded.description,includes_json=excluded.includes_json,prepare=excluded.prepare');
        foreach (require __DIR__ . '/services.php' as $service) {
            $service['includes_json'] = json_encode($service['includes'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            unset($service['includes']);
            $statement->execute($service);
        }
        $pdo->commit();
    } catch (Throwable $error) { $pdo->rollBack(); throw $error; }
    return $pdo;
}
function services(): array { return db()->query('SELECT * FROM services ORDER BY id')->fetchAll(); }
function find_service($id): ?array {
    if (!is_string($id) && !is_int($id)) return null;
    if (!preg_match('/^[1-9][0-9]*$/', (string) $id)) return null;
    $statement = db()->prepare('SELECT * FROM services WHERE id = ?');
    $statement->execute([(int) $id]);
    return $statement->fetch() ?: null;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function icon(string $name, string $class = ''): string {
    $paths = [
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up' => '<path d="M7 17 17 7M7 7h10v10"/>',
        'search' => '<circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18m-13 5h2m4 0h2"/>',
        'location' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
        'phone' => '<path d="M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-5-2-2 2a14 14 0 0 1-7-7l2-2-2-5Z"/>',
        'shield' => '<path d="m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7l8-4Z"/><path d="m8 12 3 3 5-6"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1 1m12 12 1 1M5 19l1-1M18 6l1-1"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m3 10v-3a6 6 0 0 0-3-5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
    ];
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['plus']) . '</svg>';
}

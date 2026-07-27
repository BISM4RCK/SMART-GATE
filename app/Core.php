<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
class Database
{
    public static function pdo(): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo '<h1>Database connection failed</h1>';
            echo '<p>Check XAMPP MySQL and <code>app/config.php</code>.</p>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            exit;
        }

        return $pdo;
    }
}

class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $path = __DIR__ . '/views/' . ltrim($view, '/') . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('View not found: ' . $view);
        }
        require $path;
    }
}

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(string $email, string $password): ?array
    {
        $user = UserModel::findByEmail($email);
        if (!$user || !password_verify($password, $user['password']) || ($user['status'] ?? 'active') !== 'active') {
            return null;
        }

        $house = '';
        if ($user['role'] === 'resident') {
            $resident = ResidentModel::findByUserId((int)$user['id']);
            $house = $resident['house_number'] ?? '';
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'house' => $house,
        ];

        UserModel::touchLogin((int)$user['id']);
        return $_SESSION['user'];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('login.php');
        }
    }

    public static function requireRole(array|string $roles): void
    {
        self::requireLogin();
        $role = self::user()['role'] ?? '';
        $roles = (array)$roles;
        if (!in_array($role, $roles, true)) {
            redirect('dashboard.php');
        }
    }
}


function app_path(string $path = ''): string
{
    $base = realpath(__DIR__) ?: __DIR__;
    if ($path === '') {
        return $base;
    }

    $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    return $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function root_path(string $path = ''): string
{
    $base = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
    if ($path === '') {
        return $base;
    }

    $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    return $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return '/smart-gate' . ($path !== '' ? '/' . $path : '');
}

function redirect(string $path): void
{
    if (preg_match('~^https?://~i', $path) || str_starts_with($path, '/')) {
        header('Location: ' . $path);
        exit;
    }

    header('Location: ' . url($path));
    exit;
}

function asset(string $path = ''): string
{
    return url('assets/' . ltrim($path, '/'));
}


function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash(): void
{
    if (empty($_SESSION['flash'])) return;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = in_array($flash['type'], ['success','danger','warning','info'], true) ? $flash['type'] : 'info';
    echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show shadow-sm mb-3" role="alert">';
    echo e($flash['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

function current_user(): ?array
{
    return Auth::user();
}

function require_login(): void
{
    Auth::requireLogin();
}

function require_role(array|string $roles): void
{
    Auth::requireRole($roles);
}

function dashboard_url(?array $user = null): string
{
    $user ??= current_user();

    return match ($user['role'] ?? '') {
        'resident' => '/smart-gate/resident/dashboard.php',
        'guard' => '/smart-gate/guard/dashboard.php',
        'admin' => '/smart-gate/admin/dashboard.php',
        default => '/smart-gate/index.php',
    };
}

function bool_from_input($value): bool
{
    return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function store_upload(array $file, string $folder): string
{
    if (empty($file['name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return '';
    }
    $dir = root_path('uploads/' . trim($folder, '/'));
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe = $ext ? '.' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext)) : '';
    $stem = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', pathinfo($file['name'], PATHINFO_FILENAME)));
    $fileName = $stem . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . $safe;
    $target = $dir . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $target)) return '';
    return 'uploads/' . trim($folder, '/') . '/' . $fileName;
}

function gate_badge(string $status): string
{
    return match (strtolower($status)) {
        'approved', 'active', 'open', 'read' => 'bg-success-subtle text-success-emphasis',
        'pending', 'unread' => 'bg-warning-subtle text-warning-emphasis',
        'rejected', 'closed', 'denied' => 'bg-danger-subtle text-danger-emphasis',
        default => 'bg-secondary-subtle text-secondary-emphasis',
    };
}

function count_rows(string $sql, array $params = []): int
{
    $stmt = Database::pdo()->prepare($sql);
    $stmt->execute($params);
    return (int)($stmt->fetch()['c'] ?? 0);
}

function unread_notifications_count(?int $userId = null): int
{
    $userId ??= (int)(current_user()['id'] ?? 0);
    if (!$userId) return 0;
    return NotificationModel::unreadCount($userId);
}

function latest_notifications(?int $userId = null, int $limit = 5): array
{
    $userId ??= (int)(current_user()['id'] ?? 0);
    if (!$userId) return [];
    return NotificationModel::latest($userId, $limit);
}
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026

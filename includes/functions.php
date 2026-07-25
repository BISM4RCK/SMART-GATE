<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('index.php');
    }
}

function require_role(array $allowedRoles): void
{
    require_login();
    $user = current_user();
    if (!$user || !in_array($user['role'] ?? '', $allowedRoles, true)) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!$sessionToken || !$postedToken || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message === null) {
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    $_SESSION['flash'][$key] = $message;
    return null;
}

function audit_log(?int $userId, string $action, ?string $module = null, ?string $details = null): void
{
    try {
        $stmt = db()->prepare("
            INSERT INTO audit_logs (user_id, action, module_name, details, ip_address, user_agent)
            VALUES (:user_id, :action, :module_name, :details, :ip_address, :user_agent)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':module_name' => $module,
            ':details' => $details,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        // Avoid breaking user flow if audit logging fails during early development.
    }
}

function get_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']
        );
    }
    session_destroy();
}

function moneyless_now(): string
{
    return date('Y-m-d H:i:s');
}

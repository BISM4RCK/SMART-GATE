<?php
require_once __DIR__ . '/db.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash(): void
{
    if (empty($_SESSION['flash'])) return;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info';
    echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show shadow-sm" role="alert">';
    echo e($flash['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return (bool) current_user();
}

function require_login(): void
{
    if (!is_logged_in()) redirect('login.php');
}

function logout_user(): void
{
    unset($_SESSION['user']);
}

function dashboard_url(?array $user = null): string
{
    $user ??= current_user();
    return match ($user['role'] ?? '') {
        'resident' => url('resident/dashboard.php'),
        'guard' => url('guard/dashboard.php'),
        'admin' => url('admin/dashboard.php'),
        default => url('index.php'),
    };
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();
    if (($user['role'] ?? '') !== $role) redirect('dashboard.php');
}

function badge_class(string $status): string
{
    return match (strtolower($status)) {
        'approved', 'active', 'open' => 'bg-success-subtle text-success-emphasis',
        'pending' => 'bg-warning-subtle text-warning-emphasis',
        'rejected', 'closed' => 'bg-danger-subtle text-danger-emphasis',
        default => 'bg-secondary-subtle text-secondary-emphasis',
    };
}

function user_by_email(string $email): ?array
{
    $stmt = db()->prepare("SELECT id, full_name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function login_with_credentials(string $email, string $password): ?array
{
    $user = user_by_email($email);
    if (!$user || !password_verify($password, $user['password'])) {
        return null;
    }
    if (($user['status'] ?? 'active') !== 'active') {
        return null;
    }

    $profile = [];
    if ($user['role'] === 'resident') {
        $stmt = db()->prepare("SELECT house_number FROM residents WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch() ?: [];
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'house' => $profile['house_number'] ?? '',
    ];
    return $_SESSION['user'];
}

function user_stats(): array
{
    $user = current_user();
    if (!$user) return ['pending' => 0, 'vehicles' => 0, 'concerns' => 0, 'logs' => 0];

    if ($user['role'] === 'resident') {
        $resident = resident_record($user['id']);
        $residentId = $resident['id'] ?? 0;

        $pending = count_rows("SELECT COUNT(*) c FROM visitor_requests WHERE resident_id = ? AND status = 'pending'", [$residentId]);
        $vehicles = count_rows("SELECT COUNT(*) c FROM vehicles WHERE resident_id = ?", [$residentId]);
        $concerns = count_rows("SELECT COUNT(*) c FROM concerns WHERE resident_id = ? AND sender_role = 'resident'", [$residentId]);
        $logs = count_rows("SELECT COUNT(*) c FROM gate_logs WHERE resident_id = ?", [$residentId]);
        return compact('pending', 'vehicles', 'concerns', 'logs');
    }

    if ($user['role'] === 'guard') {
        return [
            'pending' => count_rows("SELECT COUNT(*) c FROM visitor_requests WHERE status = 'pending'"),
            'vehicles' => count_rows("SELECT COUNT(*) c FROM vehicles"),
            'concerns' => count_rows("SELECT COUNT(*) c FROM concerns"),
            'logs' => count_rows("SELECT COUNT(*) c FROM gate_logs"),
        ];
    }

    return [
        'pending' => count_rows("SELECT COUNT(*) c FROM visitor_requests WHERE status = 'pending'"),
        'vehicles' => count_rows("SELECT COUNT(*) c FROM vehicles"),
        'concerns' => count_rows("SELECT COUNT(*) c FROM concerns"),
        'logs' => count_rows("SELECT COUNT(*) c FROM gate_logs"),
    ];
}

function count_rows(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return (int)($row['c'] ?? 0);
}

function resident_record(int $userId): ?array
{
    $stmt = db()->prepare("SELECT * FROM residents WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function guard_record(int $userId): ?array
{
    $stmt = db()->prepare("SELECT * FROM guards WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_record(int $userId): ?array
{
    $stmt = db()->prepare("SELECT * FROM admins WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_resident_by_house(string $houseNumber): ?array
{
    $stmt = db()->prepare("
        SELECT r.*, u.full_name, u.email
        FROM residents r
        JOIN users u ON u.id = r.user_id
        WHERE r.house_number = ?
        LIMIT 1
    ");
    $stmt->execute([$houseNumber]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function add_log(string $event, string $detail): void
{
    $user = current_user();
    $stmt = db()->prepare("INSERT INTO audit_logs (user_id, action, module_name, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user['id'] ?? null,
        $event,
        'web',
        $detail,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

function add_notification(int $userId, string $title, string $message): void
{
    $stmt = db()->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $title, $message]);
}

function upload_file(array $file): string
{
    if (empty($file['name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return '';
    $dir = __DIR__ . '/../uploads/ids';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = 'id-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . ($ext ? '.' . strtolower($ext) : '');
    $target = $dir . '/' . $name;
    return move_uploaded_file($file['tmp_name'], $target) ? 'uploads/ids/' . $name : '';
}

function concerns_for_role(array $user): array
{
    if (($user['role'] ?? '') === 'resident') {
        $resident = resident_record((int)$user['id']);
        $residentId = $resident['id'] ?? 0;
        $stmt = db()->prepare("SELECT * FROM concerns WHERE resident_id = ? OR sender_role = 'resident' ORDER BY created_at DESC");
        $stmt->execute([$residentId]);
        return $stmt->fetchAll();
    }

    $stmt = db()->query("SELECT * FROM concerns ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

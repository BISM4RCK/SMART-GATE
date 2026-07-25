<?php

require_once __DIR__ . '/config.php';

/* Escape HTML */
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

/* URL helper */
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/* Redirect */
function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

/* Logged in? */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/* Current user */
function current_user()
{
    if (!isLoggedIn()) {
        return null;
    }

    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* Flash Messages */
function flash()
{
    if (!empty($_SESSION['flash'])) {

        $flash = $_SESSION['flash'];

        echo '
        <div class="notice '.$flash['type'].'">
            '.$flash['message'].'
        </div>';

        unset($_SESSION['flash']);
    }
}
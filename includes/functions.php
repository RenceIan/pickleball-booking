<?php
declare(strict_types=1);

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function startUserSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

function csrfToken(): string
{
    startUserSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    startUserSession();

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function currentUser(): ?array
{
    startUserSession();

    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireApprovedMember(): void
{
    requireLogin();

    $user = currentUser();
    if ($user !== null && ($user['role'] ?? '') === 'admin') {
        redirect('admin/dashboard.php');
    }
    if ($user !== null && ($user['status'] ?? '') !== 'approved') {
        flash('error', 'Your account is waiting for admin approval.');
        redirect('pending-approval.php');
    }
}

function requireAdmin(): void
{
    requireLogin();

    $user = currentUser();
    if ($user === null || ($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    startUserSession();

    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return $value;
}

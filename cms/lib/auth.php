<?php
declare(strict_types=1);

function cms_get_credentials(): array
{
    $env = getenv('DISCOVERPARBAT_CMS_PASSWORD');
    if (is_string($env) && trim($env) !== '') {
        return ['username' => 'admin', 'password' => trim($env)];
    }

    $configPath = dirname(__DIR__) . '/cms-config.php';
    if (is_file($configPath)) {
        $config = require $configPath;
        if (is_array($config)) {
            return [
                'username' => (string)($config['username'] ?? 'admin'),
                'password' => trim((string)($config['password'] ?? '')),
            ];
        }
    }

    return ['username' => 'admin', 'password' => ''];
}

function cms_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function cms_is_logged_in(): bool
{
    cms_start_session();
    return !empty($_SESSION['cms_logged_in']);
}

function cms_require_login(): void
{
    if (!cms_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function cms_attempt_login(string $username, string $password): bool
{
    $creds = cms_get_credentials();
    if ($creds['password'] === '') {
        return false;
    }
    if ($username !== $creds['username']) {
        return false;
    }
    if ($password !== $creds['password']) {
        return false;
    }
    cms_start_session();
    $_SESSION['cms_logged_in'] = true;
    $_SESSION['cms_user'] = $username;
    return true;
}

function cms_logout(): void
{
    cms_start_session();
    $_SESSION = [];
    session_destroy();
}

function cms_flash(string $type, string $message): void
{
    cms_start_session();
    $_SESSION['cms_flash'] = ['type' => $type, 'message' => $message];
}

function cms_take_flash(): ?array
{
    cms_start_session();
    if (empty($_SESSION['cms_flash'])) {
        return null;
    }
    $flash = $_SESSION['cms_flash'];
    unset($_SESSION['cms_flash']);
    return $flash;
}

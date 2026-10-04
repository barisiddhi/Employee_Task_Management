<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function current_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function require_login(): void
{
    if (current_user_id() === null) {
        redirect(url('login.php'));
    }
}

function require_admin(): void
{
    require_login();
    if (current_role() !== 'admin') {
        redirect(url('employee/index.php'));
    }
}

function require_employee(): void
{
    require_login();
    if (current_role() !== 'employee') {
        redirect(url('admin/index.php'));
    }
}

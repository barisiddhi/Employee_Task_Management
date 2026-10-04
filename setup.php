<?php
/**
 * One-time setup: creates a default admin if users1 is empty.
 * Default login: admin@local.test / admin123 — change password after first login.
 * Delete or protect this file in production.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$stmt = $conn->query('SELECT COUNT(*) FROM users1');
$count = (int) $stmt->fetchColumn();

if ($count > 0) {
    die('Setup already done: users exist. Remove or rename setup.php.');
}

$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $conn->prepare(
    'INSERT INTO users1 (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute(['Administrator', 'admin@local.test', $hash, 'admin', null]);

echo 'Admin created. Email: admin@local.test / Password: admin123 — delete setup.php now.';

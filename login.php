<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect(url($_SESSION['role'] === 'admin' ? 'admin/index.php' : 'employee/index.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, name, email, password, role FROM users1 WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $stored = (string) $user['password'];
            $valid = false;
            $upgradeHash = false;

            if (strncmp($stored, '$2', 2) === 0 || strncmp($stored, '$argon', 6) === 0) {
                $valid = password_verify($password, $stored);
            } elseif (strlen($stored) === 32 && ctype_xdigit($stored)) {
                $valid = hash_equals(strtolower($stored), md5($password));
                if ($valid) {
                    $upgradeHash = true;
                }
            } elseif (hash_equals($stored, $password)) {
                $valid = true;
                $upgradeHash = true;
            }

            if ($valid && $upgradeHash) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $up = $conn->prepare('UPDATE users1 SET password = ? WHERE id = ?');
                $up->execute([$newHash, (int) $user['id']]);
            }

            if ($valid) {
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                redirect(url($user['role'] === 'admin' ? 'admin/index.php' : 'employee/index.php'));
            }
        }
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Log in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <h1>Sign in</h1>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="text" id="email" name="email" required autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn" style="width:100%">Log in</button>
        </form>
    </div>
</div>
</body>
</html>

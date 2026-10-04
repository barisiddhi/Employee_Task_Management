<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Task Manager';

// --- NEW: Helper function to detect active page ---
function is_active($path) {
    return strpos($_SERVER['PHP_SELF'], $path) !== false ? 'active' : '';
}

// Fetch profile image for the logged-in user
$sidebarImg = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT profile_image FROM profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $res = $stmt->fetch();
    $sidebarImg = $res['profile_image'] ?? null;
}
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

<?php if (isset($_SESSION['user_id'])): ?>
<div class="app-wrapper">
    <aside class="sidebar">
        <!-- User Info Section -->
        <div class="sidebar-profile">
            <?php if ($sidebarImg): ?>
                <!-- Fixed: Circular avatar -->
                <img src="<?= e(url('assets/uploads/' . $sidebarImg)) ?>" class="sidebar-avatar">
            <?php else: ?>
                <div class="sidebar-no-avatar">?</div>
            <?php endif; ?>
            <span class="sidebar-name"><?= e($_SESSION['name']) ?></span>
            <span class="sidebar-role"><?= e($_SESSION['role']) ?></span>
        </div>

        <!-- Navigation List -->
        <nav class="sidebar-nav">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="<?= e(url('admin/index.php')) ?>" class="<?= is_active('admin/index.php') ?>">Dashboard</a></li>
                    <li><a href="<?= e(url('admin/tasks.php')) ?>" class="<?= is_active('admin/tasks.php') ?>">All Tasks</a></li>
                    <li><a href="<?= e(url('admin/users.php')) ?>" class="<?= is_active('admin/users.php') ?>">Manage Users</a></li>
                    <li><a href="<?= e(url('admin/profile.php')) ?>" class="<?= is_active('admin/profile.php') ?>">My Profile</a></li>
                <?php else: ?>
                    <li><a href="<?= e(url('employee/index.php')) ?>" class="<?= is_active('employee/index.php') ?>">My Tasks</a></li>
                    <li><a href="<?= e(url('employee/profile.php')) ?>" class="<?= is_active('employee/profile.php') ?>">My Profile</a></li>
                <?php endif; ?>
                
                <li>
                    <a href="<?= e(url('notifications.php')) ?>" class="<?= is_active('notifications.php') ?>">
                        Notifications 
                        <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                            <span class="badge"><?= (int)$unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li style="margin-top: 2rem; border-top: 1px solid var(--border);">
                    <a href="<?= e(url('logout.php')) ?>" style="color: var(--danger);">Log out</a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
<?php else: ?>
    <main class="container">
<?php endif; ?>
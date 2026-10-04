<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$uid = (int) $_SESSION['user_id'];
$unreadCount = unread_notification_count($conn, $uid);

// Handle individual mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'], $_POST['id'])) {
    $nid = (int) $_POST['id'];
    $stmt = $conn->prepare('UPDATE notifications SET `IS_READ` = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$nid, $uid]);
    redirect(url('notifications.php'));
}

// Handle mark all as read
if (isset($_GET['mark_all'])) {
    $stmt = $conn->prepare('UPDATE notifications SET `IS_READ` = 1 WHERE user_id = ?');
    $stmt->execute([$uid]);
    redirect(url('notifications.php'));
}

$stmt = $conn->prepare(
    'SELECT id, message, `IS_READ` AS is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100'
);
$stmt->execute([$uid]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Notifications';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 style="margin: 0;">Notifications</h1>
    <?php if ($unreadCount > 0): ?>
        <a href="<?= e(url('notifications.php?mark_all=1')) ?>" class="btn btn-sm">Mark all as read</a>
    <?php endif; ?>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <?php if (empty($items)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--muted);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔔</div>
            <p>You have no notifications at the moment.</p>
        </div>
    <?php else: ?>
        <?php foreach ($items as $n): ?>
            <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
                <div class="notif-content">
                    <div class="notif-message"><?= e($n['message']) ?></div>
                    <div class="notif-meta"><?= date('M d, Y • H:i', strtotime($n['created_at'])) ?></div>
                </div>
                
                <?php if (!$n['is_read']): ?>
                    <div class="notif-actions">
                        <form method="post">
                            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                            <button type="submit" name="mark_read" value="1" class="btn btn-sm">Mark read</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$unreadCount = unread_notification_count($conn, (int) $_SESSION['user_id']);

$totalTasks = (int) $conn->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
$pending = (int) $conn->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn();
$completed = (int) $conn->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
$inReview = (int) $conn->query("SELECT COUNT(*) FROM tasks WHERE status = 'in review'")->fetchColumn();

$recent = $conn->query(
    'SELECT t.id, t.title, t.status, t.due_date, e.name AS assignee
     FROM tasks t
     LEFT JOIN users1 e ON t.assigned_to = e.id
     ORDER BY t.created_at DESC
     LIMIT 8'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Dashboard</h1>

<!-- Horizontal Statistics Section -->
<div class="grid-stats">
    <div class="stat card">
        <div class="label">Total tasks</div>
        <div class="value"><?= $totalTasks ?></div>
    </div>
    <div class="stat card">
        <div class="label">Pending</div>
        <div class="value"><?= $pending ?></div>
    </div>
    <div class="stat card">
        <div class="label">Completed</div>
        <div class="value"><?= $completed ?></div>
    </div>
    <div class = "stat card">
        <div class = "label"> In Review</div>
        <div class = "value"><?= $inReview ?></div>
    </div>
</div>

<!-- Recent Tasks Section -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="margin: 0;">Recent tasks</h2>
        <a href="<?= e(url('admin/task-form.php')) ?>" class="btn btn-sm">+ New task</a>
    </div>
    
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Assignee</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="5">No tasks yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><?= e($row['title']) ?></td>
                            <td><?= e($row['assignee'] ?? '—') ?></td>
                            <td><span class="status-pill <?= e(status_css_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                            <td><?= e($row['due_date'] ?? '—') ?></td>
                            <td>
                                <a href="<?= e(url('admin/task-form.php?id=' . (int) $row['id'])) ?>" class="btn btn-sm">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
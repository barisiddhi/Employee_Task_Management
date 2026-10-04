<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$unreadCount = unread_notification_count($conn, (int) $_SESSION['user_id']);
$filter = $_GET['filter'] ?? '';
$params = [];

$sql = 'SELECT t.*, e.name AS assignee FROM tasks t LEFT JOIN users1 e ON t.assigned_to = e.id WHERE 1=1';

if ($filter === 'today') {
    $sql .= " AND due_date = CURRENT_DATE";
} elseif ($filter === 'overdue') {
    $sql .= " AND due_date < CURRENT_DATE AND status != 'completed'";
} elseif (in_array($filter, ['pending', 'in progress', 'in review', 'completed'])) {
    $sql .= " AND status = ?";
    $params[] = $filter;
}

$sql .= ' ORDER BY t.created_at DESC';
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'All tasks';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Filter Card -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="row-actions" style="margin-top: 0; padding-top: 0; border-top: none; display: flex; align-items: center;">
        <a href="tasks.php" class="btn btn-ghost btn-sm">All</a>
        <a href="tasks.php?filter=today" class="btn btn-ghost btn-sm">Due Today</a>
        <a href="tasks.php?filter=overdue" class="btn btn-ghost btn-sm" style="color:#ef4444">Overdue</a>
        <a href="tasks.php?filter=in review" class="btn btn-ghost btn-sm">In Review</a>
        
        <!-- Push the New Task button to the far right -->
        <div style="margin-left: auto;">
            <!-- Change this -->
            <a href="task-form.php" class="btn btn-sm">+ New Task</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
        <thead><tr><th>Title</th><th>Assignee</th><th>Status</th><th>Due</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($tasks as $t): ?>
                <tr>
                    <td><?= e($t['title']) ?></td>
                    <td><?= e($t['assignee'] ?? '—') ?></td>
                    <td><span class="status-pill <?= e(status_css_class($t['status'])) ?>"><?= e($t['status']) ?></span></td>
                    <td><?= e($t['due_date'] ?? '—') ?></td>
                 <!-- Change this -->
                <td>
                 <a href="task-form.php?id=<?= $t['id'] ?>" class="btn btn-sm">Edit</a>
                </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
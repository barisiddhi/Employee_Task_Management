<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_employee();

$uid = (int) $_SESSION['user_id'];
$unreadCount = unread_notification_count($conn, $uid);

// Stats
$totalTasks = (int) $conn->query("SELECT COUNT(*) FROM tasks WHERE assigned_to = $uid")->fetchColumn();
$pending = (int) $conn->query("SELECT COUNT(*) FROM tasks WHERE assigned_to = $uid AND status = 'pending'")->fetchColumn();
$inReview = (int) $conn->query("SELECT COUNT(*) FROM tasks WHERE assigned_to = $uid AND status = 'in review'")->fetchColumn();
$completed = (int) $conn->query("SELECT COUNT(*) FROM tasks WHERE assigned_to = $uid AND status = 'completed'")->fetchColumn();

$filter = $_GET['filter'] ?? '';
$params = [$uid];
$sql = 'SELECT t.* FROM tasks t WHERE t.assigned_to = ?';

if ($filter === 'today') {
    $sql .= " AND due_date = CURRENT_DATE";
} elseif ($filter === 'overdue') {
    $sql .= " AND due_date < CURRENT_DATE AND status != 'completed'";
} elseif (in_array($filter, ['pending', 'in progress', 'in review', 'completed'])) {
    $sql .= " AND status = ?";
    $params[] = $filter;
}
$sql .= ' ORDER BY due_date ASC';

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My tasks';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>My Dashboard</h1>

<!-- <div class="grid-stats">
    <div class="stat"><div class="label">Total</div><div class="value"><?= $totalTasks ?></div></div>
    <div class="stat"><div class="label">Pending</div><div class="value"><?= $pending ?></div></div>
    <div class="stat"><div class="label">In Review</div><div class="value"><?= $inReview ?></div></div>
    <div class="stat"><div class="label">Completed</div><div class="value"><?= $completed ?></div></div>
</div> -->
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

<p class="row-actions">
    <a href="index.php" class="btn btn-ghost btn-sm">All</a>
    <a href="index.php?filter=today" class="btn btn-ghost btn-sm">Due Today</a>
    <a href="index.php?filter=overdue" class="btn btn-ghost btn-sm" style="color:red">Overdue</a>
    <a href="index.php?filter=completed" class="btn btn-ghost btn-sm">Completed</a>
</p>

<div class="card">
    <table>
        <thead><tr><th>Title</th><th>Status</th><th>Due</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($tasks as $t): ?>
                <tr>
                    <td><?= e($t['title']) ?></td>
                    <td><span class="status-pill <?= e(status_css_class($t['status'])) ?>"><?= e($t['status']) ?></span></td>
                    <td><?= e($t['due_date'] ?? '—') ?></td>
                    <td><a href="task.php?id=<?= $t['id'] ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
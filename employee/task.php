<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';

require_employee();

$uid = (int) $_SESSION['user_id'];
$unreadCount = unread_notification_count($conn, $uid);

$taskId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($taskId <= 0) {
    redirect(url('employee/index.php'));
}

$stmt = $conn->prepare(
    'SELECT t.*, a.name AS assigner_name
     FROM tasks t
     LEFT JOIN users1 a ON t.assigned_by = a.id
     WHERE t.id = ? AND t.assigned_to = ?
     LIMIT 1'
);
$stmt->execute([$taskId, $uid]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) {
    redirect(url('employee/index.php'));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $allowed = ['pending', 'in progress', 'completed'];
    $snoozeUntil = trim($_POST['snooze_until'] ?? '');

    if (!in_array($status, $allowed, true)) {
        $error = 'Invalid status.';
    } else {
        $snoozeVal = null;
        if ($snoozeUntil !== '') {
            $ts = strtotime(str_replace('T', ' ', $snoozeUntil));
            $snoozeVal = $ts ? date('Y-m-d H:i:s', $ts) : null;
        }

        $oldStatus = $task['status'];
        $upd = $conn->prepare(
            'UPDATE tasks SET status = ?, snooze_until = ? WHERE id = ? AND assigned_to = ?'
        );
        $upd->execute([$status, $snoozeVal, $taskId, $uid]);

        if ($oldStatus !== $status) {
            log_task_history($conn, $taskId, $uid, $oldStatus, $status);
            $assignerId = (int) ($task['assigned_by'] ?? 0);
            if ($assignerId > 0) {
                notify_user(
                    $conn,
                    $assignerId,
                    'Task "' . $task['title'] . '" is now: ' . $status
                );
            }
        }
        $success = 'Task updated.';
        $stmt->execute([$taskId, $uid]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$history = $conn->prepare(
    'SELECT h.old_status, h.new_status, h.updated_at, u.name AS by_name
     FROM task_history h
     LEFT JOIN users1 u ON h.updated_by = u.id
     WHERE h.task_id = ?
     ORDER BY h.updated_at DESC'
);
$history->execute([$taskId]);
$historyRows = $history->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Task: ' . $task['title'];
require_once __DIR__ . '/../includes/header.php';

$snoozeDisplay = '';
if (!empty($task['snooze_until'])) {
    $snoozeDisplay = str_replace(' ', 'T', substr((string) $task['snooze_until'], 0, 16));
}
?>

<h1><?= e($task['title']) ?></h1>

<?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success !== ''): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card">
    <p><strong>Assigned by:</strong> <?= e($task['assigner_name'] ?? '—') ?></p>
    <p><strong>Due:</strong> <?= e($task['due_date'] ?? '—') ?></p>
    <p><strong>Description</strong></p>
    <p><?= nl2br(e((string) ($task['description'] ?? ''))) ?: '<em>None</em>' ?></p>

    <form method="post" action="">
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['pending', 'in progress', 'completed'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($task['status'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="snooze_until">Snooze until (optional)</label>
            <input type="datetime-local" id="snooze_until" name="snooze_until" value="<?= e($snoozeDisplay) ?>">
        </div>
        <button type="submit" class="btn">Save</button>
        <a href="<?= e(url('employee/index.php')) ?>" class="btn btn-ghost">Back</a>
    </form>
</div>

<?php if (!empty($historyRows)): ?>
<div class="card">
    <h2>Status history</h2>
    <ul style="margin:0;padding-left:1.25rem;color:var(--muted)">
        <?php foreach ($historyRows as $h): ?>
            <li>
                <?= e($h['updated_at']) ?> —
                <?= e($h['by_name'] ?? 'Unknown') ?>:
                <?= e($h['old_status'] ?? '—') ?> → <?= e($h['new_status'] ?? '') ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

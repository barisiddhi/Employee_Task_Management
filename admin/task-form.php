<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$unreadCount = unread_notification_count($conn, (int) $_SESSION['user_id']);
$adminId = (int) $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editing = $taskId > 0;

$employees = $conn->query("SELECT id, name, email FROM users1 WHERE role = 'employee' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$task = ['title' => '', 'description' => '', 'assigned_to' => '', 'status' => 'pending', 'priority' => 'medium', 'due_date' => '', 'snooze_until' => ''];

if ($editing) {
    $stmt = $conn->prepare('SELECT * FROM tasks WHERE id = ? LIMIT 1');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) redirect(url('admin/tasks.php'));
}

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete']) && $editing) {
        $conn->prepare('DELETE FROM tasks WHERE id = ?')->execute([$taskId]);
        redirect(url('admin/tasks.php'));
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'pending';
    $priority = $_POST['priority'] ?? 'medium';
    $dueDate = trim($_POST['due_date'] ?? '');
    $snoozeUntil = trim($_POST['snooze_until'] ?? '');

    if ($title === '') {
        $error = 'Title is required.';
    } elseif (!$editing && empty($_POST['assigned_to'])) {
        $error = 'Please assign to at least one employee.';
    }

    if ($error === '') {
        $dueVal = $dueDate !== '' ? $dueDate : null;
        $snoozeVal = null;
        if ($snoozeUntil !== '') {
            $ts = strtotime(str_replace('T', ' ', $snoozeUntil));
            $snoozeVal = $ts ? date('Y-m-d H:i:s', $ts) : null;
        }

        if ($editing) {
            $assignedTo = (int)$_POST['assigned_to'][0]; // Take first if multiple selected in edit
            $stmt = $conn->prepare('UPDATE tasks SET title=?, description=?, assigned_to=?, assigned_by=?, status=?, priority=?, due_date=?, snooze_until=? WHERE id=?');
            $stmt->execute([$title, $description ?: null, $assignedTo, $adminId, $status, $priority, $dueVal, $snoozeVal, $taskId]);
            $success = 'Task updated.';
        } else {
            // BULK ASSIGN LOGIC
            $assignedToIds = $_POST['assigned_to'] ?? [];
            foreach ($assignedToIds as $user_id) {
                $stmt = $conn->prepare('INSERT INTO tasks (title, description, assigned_to, assigned_by, status, priority, due_date, snooze_until) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([$title, $description ?: null, (int)$user_id, $adminId, $status, $priority, $dueVal, $snoozeVal]);
                notify_user($conn, (int)$user_id, 'New task assigned: ' . $title);
            }
            redirect(url('admin/tasks.php?success=Tasks assigned successfully'));
        }
    }
}

$pageTitle = $editing ? 'Edit task' : 'New task';
require_once __DIR__ . '/../includes/header.php';
$snoozeDisplay = !empty($task['snooze_until']) ? str_replace(' ', 'T', substr((string)$task['snooze_until'], 0, 16)) : '';
?>

<h1><?= e($pageTitle) ?></h1>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="card">
    <form method="post" action="">
        <div class="form-group">
            <label for="title">Task Title</label>
            <input type="text" id="title" name="title" placeholder="Enter task name..." required value="<?= e((string)$task['title']) ?>">
        </div>

        <div class="form-group">
            <label for="description">Detailed Description</label>
            <textarea id="description" name="description" placeholder="Describe the requirements of this task..."><?= e((string)$task['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="assigned_to">Assign To</label>
            <p style="font-size: 0.8rem; color: var(--muted); margin-top: -5px; margin-bottom: 10px;">
                Hold <strong>Ctrl</strong> (or Cmd) to select multiple employees.
            </p>
            <select id="assigned_to" name="assigned_to[]" multiple required>
                <?php foreach ($employees as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= ($editing && $task['assigned_to'] == $e['id']) ? 'selected' : '' ?>>
                        <?= e($e['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

       <div style="display: flex; gap: 20px; width: 100%;">
    <div class="form-group" style="flex: 1;">
        <label for="priority">Priority Level</label>
        <select id="priority" name="priority">
            <option value="low" <?= $task['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
            <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
            <option value="high" <?= $task['priority'] === 'high' ? 'selected' : '' ?>>High</option>
            <option value="urgent" <?= $task['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
    </select>
    </div>

    <div class="form-group" style="flex: 1;">
        <label for="due_date">Deadline (Due Date)</label>
        <input type="date" id="due_date" name="due_date">
    </div>
</div>

        <div style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
            <button type="submit" class="btn" style="padding: 12px 30px;">
                <?= $editing ? 'Update Task' : 'Create Task' ?>
            </button>
            <a href="<?= e(url('admin/tasks.php')) ?>" class="btn btn-ghost" style="margin-left: 10px;">Cancel</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
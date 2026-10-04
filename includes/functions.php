<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function notify_user(PDO $conn, int $userId, string $message): void
{
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
    $stmt->execute([$userId, $message]);
}

function log_task_history(PDO $conn, int $taskId, int $updatedBy, ?string $oldStatus, string $newStatus): void
{
    $stmt = $conn->prepare(
        'INSERT INTO task_history (task_id, updated_by, old_status, new_status) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$taskId, $updatedBy, $oldStatus, $newStatus]);
}

function unread_notification_count(PDO $conn, int $userId): int
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND (`IS_READ` = 0 OR `IS_READ` IS NULL)'
    );
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function status_css_class(string $status): string
{
    return 'status-' . str_replace(' ', '_', $status);
}

function priority_css_class(string $priority): string{
    return 'priority-' . $priority;
}

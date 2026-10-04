<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$unreadCount = unread_notification_count($conn, (int) $_SESSION['user_id']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $conn->prepare(
                'INSERT INTO users1 (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash, 'employee', $phone !== '' ? $phone : null]);
            $newId = (int) $conn->lastInsertId();
            $chk = $conn->prepare('SELECT id FROM profiles WHERE user_id = ? LIMIT 1');
            $chk->execute([$newId]);
            if (!$chk->fetch()) {
                $stmt = $conn->prepare('INSERT INTO profiles (user_id, address, profile_image) VALUES (?, NULL, NULL)');
                $stmt->execute([$newId]);
            }
            $success = 'Employee added.';
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                $error = 'That email is already registered.';
            } else {
                $error = 'Could not save user.';
            }
        }
    }
}

$users = $conn->query(
    "SELECT id, name, email, role, phone, created_at FROM users1 ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Users';
require_once __DIR__ . '/../includes/header.php';
//
if(isset($_POST['delete_user'])){
    $id =(int)$_POST['user_id'];
    $stmt = $conn->prepare('DELETE FROM users1 WHERE id = ?');
    $stmt->execute([$id]);
    redirect(url('admin/users.php'));
    }

$users = $conn->query(
    "SELECT id, name, email, role, phone, created_at FROM users1 ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Users';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Users</h1>

<?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success !== ''): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card">
    <h2>Add employee</h2>
    <form method="post" action="">
        <input type="hidden" name="add_employee" value="1">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" maxlength="15">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        <button type="submit" class="btn">Add employee</button>
    </form>
</div>

<div class="card">
    <h2>All users</h2>
    <div class="table-wrap">
                <table>
                    <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Created</th>
                <th>Actions</th> <!-- Add this line -->
            </tr>
        </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['role']) ?></td>
                        <td><?= e($u['phone'] ?? '—') ?></td>
                        <td><?= e($u['created_at']) ?></td>
                            <td> <!-- Only show delete button for non-admin employees -->
                                <?php if ($u['role'] !== 'admin'): ?>
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" name="delete_user" class="btn btn-sm" style="background-color: var(--danger); color:white;">Delete</button>
                                </form>
                                <?php endif; ?>
                            </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

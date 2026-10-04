<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
// ADDED THIS LINE - This is why you got the error
require_once __DIR__ . '/../includes/auth.php'; 

require_employee(); // Now this function will work

$uid = (int) $_SESSION['user_id'];
$unreadCount = unread_notification_count($conn, $uid);

$error = '';
$success = '';

// To fetch current data
$stmt = $conn->prepare("
    SELECT u.name, u.email, u.phone, p.address, p.profile_image 
    FROM users1 u 
    LEFT JOIN profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $newPassword = $_POST['password'] ?? ''; // Fixed typo: was 'passwword'

    if ($name === '') {
        $error = 'Name is required';
    } else {
        try {
            $conn->beginTransaction();

            // Update users1 table - Fixed typo: removed extra comma after phone=?
            $updUser = $conn->prepare("UPDATE users1 SET name = ?, phone = ? WHERE id = ?");
            $updUser->execute([$name, $phone, $uid]);
            $_SESSION['name'] = $name;

            // Handle image upload
            $imagePath = $user['profile_image'];
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileExt = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $newFileName = 'profile_' . $uid . '_' . time() . '.' . $fileExt;
                $targetFile = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                    $imagePath = $newFileName;
                }
            }

            // Update profiles table
            $chkProfile = $conn->prepare("SELECT id FROM profiles WHERE user_id = ?");
            $chkProfile->execute([$uid]);
            if ($chkProfile->fetch()) {
                $updProf = $conn->prepare("UPDATE profiles SET address = ?, profile_image = ? WHERE user_id = ?");
                $updProf->execute([$address, $imagePath, $uid]);
            } else {
                $insProf = $conn->prepare("INSERT INTO profiles (user_id, address, profile_image) VALUES (?, ?, ?)");
                $insProf->execute([$uid, $address, $imagePath]);
            }

            // Password update
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    throw new Exception('Password must be at least 6 characters');
                }
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updPass = $conn->prepare("UPDATE users1 SET password = ? WHERE id = ?");
                $updPass->execute([$hash, $uid]);
            }

            $conn->commit();
            // Fixed typo: removed double "Location:"
            header("Location: profile.php?success=1") ;
            
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $error = $e->getMessage();
        }
    }
}

if (isset($_GET['success'])) {
    $success = 'Profile updated successfully.';
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>My Profile</h1>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card"> <!-- Fixed typo: Added missing closing bracket > and removed extra </div> tags -->
    <form action="" method="post" enctype="multipart/form-data">
        <div style="text-align: center; margin-bottom: 2rem;">
            <?php if (!empty($user['profile_image'])): ?>
                <img src="<?= e(url('assets/uploads/' . $user['profile_image'])) ?>" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border: 2px solid var(--border);">
            <?php else: ?>
                <div style="width: 120px; height: 120px; border-radius: 50%; background:var(--bg); line-height:120px; margin:0 auto; border: 1px solid var(--border);">No Image</div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="profile_pic">Change Profile Picture</label>
            <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
        </div>

        <div class="form-group">
            <label>Email (Login ID)</label>
            <input type="text" value="<?= e($user['email']) ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
        </div>

        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="address">Residential Address</label>
            <textarea id="address" name="address"><?= e($user['address'] ?? '') ?></textarea>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 2rem 0;">
        <h3>Security</h3>

        <div class="form-group">
            <label for="password">New Password (Leave blank to keep current)</label>
            <input type="password" id="password" name="password" minlength="6">
        </div>

        <button type="submit" class="btn">Save Changes</button>
        <a href="<?= e(url('employee/index.php')) ?>" class="btn btn-ghost">Back to Tasks</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
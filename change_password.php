<?php
session_start();
if (empty($_SESSION['tech_user'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/includes/db_config.php';

$conn = getDbConnection();

$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new === '' || $confirm === '' || $current === '') {
        $error = 'All fields are required.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $stmt = $conn->prepare('SELECT password_hash FROM technicians WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $_SESSION['tech_user']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows) {
            $row = $res->fetch_assoc();
            if (!password_verify($current, $row['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $conn->prepare('UPDATE technicians SET password_hash = ?, must_change_password = 0 WHERE username = ?');
                $upd->bind_param('ss', $new_hash, $_SESSION['tech_user']);
                if ($upd->execute()) {
                    $success = 'Password changed successfully.';
                    $_SESSION['must_change_password'] = 0;
                } else {
                    $error = 'Could not update password.';
                }
                $upd->close();
            }
        } else {
            $error = 'Account not found.';
        }
        $stmt->close();
    }
}
$conn->close();
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Change Password</title><link rel="stylesheet" href="styles.css"></head><body>
<div style="max-width:520px;margin:40px auto;background:#fff;padding:20px;border-radius:8px">
<h2>Change Password for <?php echo htmlspecialchars($_SESSION['tech_user']); ?></h2>
<?php if ($error): ?><div style="color:#721c24;background:#f8d7da;padding:10px;border-radius:4px"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div style="color:#155724;background:#d4edda;padding:10px;border-radius:4px"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<form method="POST">
  <label>Current password</label>
  <input name="current_password" type="password" required>
  <label>New password</label>
  <input name="new_password" type="password" required>
  <label>Confirm new password</label>
  <input name="confirm_password" type="password" required>
  <div style="margin-top:12px">
    <button type="submit" class="btn">Change Password</button>
    <a href="manage_tickets.php" style="margin-left:8px">Back</a>
    <a href="logout.php" style="margin-left:8px">Log out</a>
  </div>
</form>
</div>
</body></html>

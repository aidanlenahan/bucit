<?php
session_start();
// login.php - Technician login (renamed)
require_once __DIR__ . '/includes/db_config.php';

$conn = getDbConnection();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pw = $_POST['password'] ?? '';

    if ($user === '' || $pw === '') {
        $error = 'Enter username and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, username, display_name, password_hash, active, must_change_password FROM technicians WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows) {
            $row = $res->fetch_assoc();
            if (!$row['active']) {
                $error = 'Account is not active.';
            } elseif (password_verify($pw, $row['password_hash'])) {
                // success
                session_regenerate_id(true);
                $_SESSION['tech_user'] = $row['username'];
                $_SESSION['tech_display'] = $row['display_name'] ?? $row['username'];
                $_SESSION['tech_id'] = $row['id'];
                $_SESSION['must_change_password'] = intval($row['must_change_password']);

                // redirect to change-password if required
                if (!empty($_SESSION['must_change_password'])) {
                    header('Location: change_password.php');
                } else {
                    header('Location: manage_tickets.php');
                }
                exit();
            } else {
                $error = 'Invalid credentials.';
            }
        } else {
            $error = 'Invalid credentials.';
        }
        $stmt->close();
    }
}
$conn->close();
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Technician Login</title><link rel="stylesheet" href="styles.css"></head><body>
<div style="max-width:420px;margin:40px auto;background:#fff;padding:20px;border-radius:8px">
<h2>Technician Login</h2>
<?php if ($error): ?><div style="color:#721c24;background:#f8d7da;padding:10px;border-radius:4px"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="POST">
  <label>Username</label>
  <input name="username" autofocus required>
  <label>Password</label>
  <input name="password" type="password" required>
  <div style="margin-top:12px">
    <button type="submit" class="btn">Log in</button>
    <a href="index.html" style="margin-left:8px">Back</a>
  </div>
</form>
</div>
</body></html>
<?php
session_start();
// login.php - Technician login (renamed)
require_once __DIR__ . '/../includes/db_config.php';

// Connection is already established in db_config.php as $conn

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
if (isset($conn)) {
        $conn->close();
}
?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Technician Login</title>
<link rel="icon" type="image/svg+xml" href="../img/buc.svg">
<link rel="stylesheet" href="../css/styles.css">
</head>
<body class="login-page">
<main class="login-card">
<h2>Technician Login</h2>
<?php if ($error): ?><div class="login-alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="POST" class="login-form">
    <label for="username">Username</label>
    <input id="username" name="username" autofocus required>
    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>
    <div class="login-actions">
        <button type="submit" class="btn">Log in</button>
        <a href="../index.html" class="login-back-link">Back</a>
    </div>
</form>
</main>
</body>
</html>
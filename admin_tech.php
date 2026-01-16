<?php
session_start();
// admin_tech.php - Manage technician accounts (admin only: jmilonas)
if (empty($_SESSION['tech_user']) || $_SESSION['tech_user'] !== 'jmilonas') {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/includes/db_config.php';

$conn = getDbConnection();

// Handle actions: toggle active, reset password, create user, delete user, set custom password
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_active']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare('UPDATE technicians SET active = 1 - active WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $message = 'Toggled active state.';
        } else {
            $error = 'Error toggling active: ' . $conn->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['reset_password']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $hash = password_hash('changeme', PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE technicians SET password_hash = ?, must_change_password = 1 WHERE id = ?');
        $stmt->bind_param('si', $hash, $id);
        if ($stmt->execute()) {
            $message = 'Password reset to "changeme" and must-change flag set.';
        } else {
            $error = 'Error resetting password: ' . $conn->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['set_custom_password']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $new_pw = $_POST['new_password'] ?? '';
        $force_change = isset($_POST['force_change']) ? 1 : 0;
        
        if ($new_pw !== '') {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE technicians SET password_hash = ?, must_change_password = ? WHERE id = ?');
            $stmt->bind_param('sii', $hash, $force_change, $id);
            if ($stmt->execute()) {
                $message = 'Custom password set' . ($force_change ? ' (must change on login)' : '') . '.';
            } else {
                $error = 'Error setting password: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $error = 'Password cannot be empty.';
        }
    }
    
    if (isset($_POST['toggle_must_change']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare('UPDATE technicians SET must_change_password = 1 - must_change_password WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $message = 'Toggled must-change-password flag.';
        } else {
            $error = 'Error toggling flag: ' . $conn->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['delete_user']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        // Prevent self-deletion
        $check = $conn->prepare('SELECT username FROM technicians WHERE id = ?');
        $check->bind_param('i', $id);
        $check->execute();
        $res_check = $check->get_result();
        if ($res_check && $res_check->num_rows) {
            $user_to_delete = $res_check->fetch_assoc()['username'];
            if ($user_to_delete === 'jmilonas') {
                $error = 'Cannot delete admin account.';
            } else {
                $stmt = $conn->prepare('DELETE FROM technicians WHERE id = ?');
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $message = 'User deleted successfully.';
                } else {
                    $error = 'Error deleting user: ' . $conn->error;
                }
                $stmt->close();
            }
        }
        $check->close();
    }
    
    if (isset($_POST['create_user'])) {
        $display = trim($_POST['display_name'] ?? '');
        if ($display !== '') {
            // generate username
            $parts = preg_split('/\s+/', $display);
            $first = $parts[0] ?? '';
            $last = $parts[count($parts)-1] ?? '';
            $username_gen = strtolower(substr($first,0,1) . preg_replace('/[^a-z0-9]/i','', $last));
            $formatted = $username_gen; $i=1;
            while (true) {
                $chk = $conn->prepare('SELECT id FROM technicians WHERE username = ?');
                $chk->bind_param('s', $formatted);
                $chk->execute();
                $rr = $chk->get_result();
                $exists = ($rr && $rr->num_rows>0);
                $chk->close();
                if (!$exists) break;
                $i++; $formatted = $username_gen . $i;
            }
            $hash = password_hash('changeme', PASSWORD_DEFAULT);
            $ins = $conn->prepare('INSERT INTO technicians (username, display_name, password_hash, active, must_change_password) VALUES (?, ?, ?, 1, 1)');
            $ins->bind_param('sss', $formatted, $display, $hash);
            if ($ins->execute()) {
                $message = 'User created: ' . htmlspecialchars($formatted) . ' (password: changeme)';
            } else {
                $error = 'Error creating user: ' . $conn->error;
            }
            $ins->close();
        } else {
            $error = 'Display name cannot be empty.';
        }
    }
}

// Fetch list
$rows = [];
$res = $conn->query('SELECT id, username, display_name, active, must_change_password, created_at FROM technicians ORDER BY username');
if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
}
$conn->close();
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Admin - Technicians</title><link rel="icon" type="image/svg+xml" href="img/buc.svg"><link rel="stylesheet" href="styles.css">
<style>
body { background: #f5f5f5; }
.admin-container { max-width: 1200px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.admin-container h2, .admin-container h3, .admin-container p, .admin-container small, .admin-container a { color: #333; }
.admin-container a { color: #800000; text-decoration: none; }
.admin-container a:hover { text-decoration: underline; }
.alert { padding: 10px; border-radius: 4px; margin-bottom: 12px; }
.alert-success { background: #d4edda; color: #155724; }
.alert-danger { background: #f8d7da; color: #721c24; }
.tech-table { width: 100%; border-collapse: collapse; margin-top: 12px; background: #fff; }
.tech-table th { background: #f5f5f5; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; color: #333; font-weight: 600; }
.tech-table td { padding: 10px; border-top: 1px solid #ddd; vertical-align: top; color: #333; }
.tech-table tr:hover { background: #f9f9f9; }
.action-btn { display: inline-block; padding: 6px 12px; margin: 2px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; font-weight: 500; }
.btn-primary { background: #800000; color: white; }
.btn-warning { background: #ffc107; color: #333; }
.btn-danger { background: #dc3545; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.action-form { display: inline-block; margin: 2px; }
.password-form { background: #f9f9f9; padding: 8px; border-radius: 4px; margin-top: 6px; }
.password-form input { padding: 4px 8px; margin-right: 6px; border: 1px solid #ccc; border-radius: 3px; }
.password-form label { font-size: 12px; margin-left: 6px; color: #333; }
.status-badge { padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block; }
.badge-active { background: #d4edda; color: #155724; }
.badge-inactive { background: #f8d7da; color: #721c24; }
label { color: #333; }
input[type="text"], input[type="password"] { color: #333; }
</style>
</head><body>
<div class="admin-container">
<h2>Technician Account Management</h2>
<p><small>Logged in as admin: <strong>jmilonas</strong> — <a href="manage_tickets.php">Back to Tickets</a> | <a href="logout.php">Log out</a></small></p>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<table class="tech-table">
<thead>
<tr>
<th>Username</th>
<th>Display Name</th>
<th>Status</th>
<th>Must Change PW</th>
<th>Created</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><strong><?php echo htmlspecialchars($r['username']); ?></strong></td>
<td><?php echo htmlspecialchars($r['display_name'] ?? ''); ?></td>
<td>
    <span class="status-badge <?php echo $r['active'] ? 'badge-active' : 'badge-inactive'; ?>">
        <?php echo $r['active'] ? 'Active' : 'Inactive'; ?>
    </span>
</td>
<td><?php echo $r['must_change_password'] ? 'Yes' : 'No'; ?></td>
<td><?php echo date('M j, Y', strtotime($r['created_at'])); ?></td>
<td>
    <form class="action-form" method="POST">
        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
        <button name="toggle_active" class="action-btn btn-warning" type="submit" title="Enable/Disable account">
            <?php echo $r['active'] ? 'Disable' : 'Enable'; ?>
        </button>
    </form>
    
    <form class="action-form" method="POST">
        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
        <button name="reset_password" class="action-btn btn-primary" type="submit" title="Reset to 'changeme'">Reset PW</button>
    </form>
    
    <form class="action-form" method="POST">
        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
        <button name="toggle_must_change" class="action-btn btn-secondary" type="submit" title="Toggle force password change">
            <?php echo $r['must_change_password'] ? 'Clear Must-Change' : 'Force Change'; ?>
        </button>
    </form>
    
    <?php if ($r['username'] !== 'jmilonas'): ?>
    <form class="action-form" method="POST" onsubmit="return confirm('Delete user <?php echo htmlspecialchars($r['username']); ?>? This cannot be undone.');">
        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
        <button name="delete_user" class="action-btn btn-danger" type="submit" title="Delete user">Delete</button>
    </form>
    <?php endif; ?>
    
    <div class="password-form">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
            <input type="password" name="new_password" placeholder="New password..." required style="width:150px;">
            <label><input type="checkbox" name="force_change" value="1"> Force change</label>
            <button name="set_custom_password" class="action-btn btn-primary" type="submit">Set</button>
        </form>
    </div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h3 style="margin-top: 30px;">Create New Technician</h3>
<form method="POST" style="max-width: 500px;">
<div style="margin-bottom: 10px;">
    <label for="display_name" style="display: block; margin-bottom: 4px; font-weight: 600;">Full Display Name</label>
    <input type="text" id="display_name" name="display_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    <small style="color: #666;">Username will be auto-generated (first initial + last name)</small>
</div>
<button name="create_user" class="btn" type="submit">Create Technician</button>
</form>

</div>
</body></html>
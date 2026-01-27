<?php
session_start();
if (empty($_SESSION['tech_user'])) {
    header('Location: login.php');
    exit();
}

$current_user = $_SESSION['tech_user'];
$is_admin = ($current_user === 'jmilonas');

require_once __DIR__ . '/includes/db_config.php';

// Connection is already established in db_config.php as $conn

$bucit_point_defaults = [
    'screen' => 10,
    'battery' => 5,
    'keyboard' => 7,
    'wifi' => 2,
    'login' => 3,
    'other' => 0
];

function bucit_default_points_for_category_lb($category, $defaults) {
    $key = strtolower(trim($category ?? ''));
    return $defaults[$key] ?? 0;
}

function bucit_backfill_ticket_points($conn, $defaults) {
    $check = $conn->query("SHOW TABLES LIKE 'ticket_points'");
    if (!$check || $check->num_rows === 0) {
        return;
    }

    $missing_sql = "SELECT t.id, t.tech, t.problem_category FROM tickets t
                    LEFT JOIN ticket_points tp ON tp.ticket_id = t.id
                    WHERE tp.ticket_id IS NULL AND t.status = 'Closed'
                      AND t.tech IS NOT NULL AND t.tech <> '' AND t.tech <> 'jmilonas'";
    $missing_res = $conn->query($missing_sql);
    if (!$missing_res || $missing_res->num_rows === 0) {
        return;
    }
    while ($row = $missing_res->fetch_assoc()) {
        $tech = trim($row['tech']);
        if ($tech === '') continue;
        $base = bucit_default_points_for_category_lb($row['problem_category'] ?? '', $defaults);
        $insert = $conn->prepare('INSERT INTO ticket_points (ticket_id, tech, problem_category, base_points, awarded_points, manual_override, notes, updated_by) VALUES (?, ?, ?, ?, ?, 0, NULL, ?)');
        if ($insert) {
            $updated_by = 'backfill';
            $award = $base;
            $insert->bind_param('ississ', $row['id'], $tech, $row['problem_category'], $base, $award, $updated_by);
            $insert->execute();
            $insert->close();
        }
    }
}

$flash_message = $_SESSION['leaderboard_flash'] ?? null;
$flash_message = $_SESSION['leaderboard_flash'] ?? null;
$flash_error = $_SESSION['leaderboard_error'] ?? null;
unset($_SESSION['leaderboard_flash'], $_SESSION['leaderboard_error']);

$redirect_after_post = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin) {
    $selected_tech_post = $_POST['selected_tech'] ?? '';
    $redirect_after_post = $selected_tech_post ? ('leaderboard.php?tech=' . urlencode($selected_tech_post)) : 'leaderboard.php';

    $points_id = intval($_POST['points_id'] ?? 0);
    if ($points_id > 0 && isset($_POST['update_points'])) {
        $new_points = max(0, intval($_POST['awarded_points'] ?? 0));
        $notes = trim($_POST['notes'] ?? '');
        $new_category_input = trim($_POST['problem_category'] ?? '');
        if ($new_category_input === '') {
            $new_category_input = trim($_POST['current_category'] ?? 'other');
        }
        $base_points = bucit_default_points_for_category_lb($new_category_input, $bucit_point_defaults);

        $stmt = $conn->prepare('UPDATE ticket_points SET problem_category = ?, base_points = ?, awarded_points = ?, manual_override = 1, notes = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('siissi', $new_category_input, $base_points, $new_points, $notes, $current_user, $points_id);
            if ($stmt->execute()) {
                $_SESSION['leaderboard_flash'] = 'Updated ticket entry.';
            } else {
                $_SESSION['leaderboard_error'] = 'Could not update entry.';
            }
            $stmt->close();
        } else {
            $_SESSION['leaderboard_error'] = 'Unable to prepare update statement.';
        }
    } elseif ($points_id > 0 && isset($_POST['reset_points'])) {
        $stmt = $conn->prepare('UPDATE ticket_points SET awarded_points = base_points, manual_override = 0, notes = NULL, updated_by = ?, updated_at = NOW() WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $current_user, $points_id);
            if ($stmt->execute()) {
                $_SESSION['leaderboard_flash'] = 'Reset points to default value.';
            } else {
                $_SESSION['leaderboard_error'] = 'Could not reset points.';
            }
            $stmt->close();
        } else {
            $_SESSION['leaderboard_error'] = 'Unable to prepare reset statement.';
        }
    }

    header('Location: ' . $redirect_after_post);
    exit();
}

$check_points_exists = $conn->query("SHOW TABLES LIKE 'ticket_points'");
if ($check_points_exists && $check_points_exists->num_rows > 0) {
    bucit_backfill_ticket_points($conn, $bucit_point_defaults);
}

$selected_tech = $_GET['tech'] ?? '';
$selected_tech = trim($selected_tech);

$leaderboard_rows = [];
$leaderboard_sql = "SELECT tp.tech, COALESCE(t.display_name, tp.tech) AS display_name, SUM(tp.awarded_points) AS total_points, COUNT(*) AS tickets_closed
                    FROM ticket_points tp
                    LEFT JOIN technicians t ON t.username = tp.tech
                    WHERE tp.tech <> 'jmilonas'
                    GROUP BY tp.tech, display_name
                    ORDER BY total_points DESC, display_name ASC";
$lb_res = $conn->query($leaderboard_sql);
if ($lb_res) {
    while ($row = $lb_res->fetch_assoc()) {
        $leaderboard_rows[] = $row;
    }
}

$detail_rows = [];
$detail_display_name = '';
if ($selected_tech !== '') {
    $detail_stmt = $conn->prepare("SELECT tp.*, tickets.problem_category AS live_category, tickets.status AS ticket_status
                                   FROM ticket_points tp
                                   LEFT JOIN tickets ON tickets.id = tp.ticket_id
                                   WHERE tp.tech = ?
                                   ORDER BY tp.updated_at DESC");
    if ($detail_stmt) {
        $detail_stmt->bind_param('s', $selected_tech);
        $detail_stmt->execute();
        $detail_res = $detail_stmt->get_result();
        while ($row = $detail_res->fetch_assoc()) {
            $detail_rows[] = $row;
        }
        $detail_stmt->close();
    }

    // Attempt to fetch display name
    $name_stmt = $conn->prepare('SELECT display_name FROM technicians WHERE username = ? LIMIT 1');
    if ($name_stmt) {
        $name_stmt->bind_param('s', $selected_tech);
        $name_stmt->execute();
        $name_stmt->bind_result($dn);
        if ($name_stmt->fetch() && $dn) {
            $detail_display_name = $dn;
        }
        $name_stmt->close();
    }
    if ($detail_display_name === '') {
        $detail_display_name = ucfirst($selected_tech);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bucit Ranked</title>
    <link rel="icon" type="image/svg+xml" href="img/buc.svg">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root { --brand: #800000; --brand-dark: #5a0000; }
        body {
            background: linear-gradient(135deg, #800000, #3d0000);
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .leaderboard-container {
            max-width: 1200px;
            margin: 30px auto;
            background: rgba(255,255,255,0.98);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            align-items: baseline;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            color: var(--brand);
        }
        .header-actions a {
            background: var(--brand);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .leader-row {
            display: flex;
            align-items: center;
            gap: 16px;
            background: #fff7f2;
            border: 1px solid #f3d6c8;
            border-radius: 10px;
            padding: 16px 20px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s, border-color 0.2s;
        }
        .leader-row:hover { transform: translateY(-3px); border-color: var(--brand); }
        .leader-row.active { border-color: var(--brand); box-shadow: 0 6px 18px rgba(0,0,0,0.15); }
        .leader-rank {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--brand);
            min-width: 48px;
            text-align: center;
        }
        .leader-body {
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .leader-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .leader-meta {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            color: #555;
            font-size: 0.95rem;
        }
        .leader-meta span { display: flex; align-items: center; gap: 6px; }
        .leader-meta .trophy-count { font-size: 1.05rem; font-weight: 600; color: #f5a623; }
        @media (max-width: 600px) {
            .leader-row { flex-direction: column; align-items: flex-start; }
            .leader-rank { text-align: left; }
            .leader-body { flex-direction: column; align-items: flex-start; }
            .leader-meta { flex-direction: column; align-items: flex-start; gap: 4px; }
        }
        .detail-section { margin-top: 40px; }
        .detail-header { display: flex; justify-content: space-between; align-items: center; }
        .detail-header h2 { margin: 0; color: var(--brand); }
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .detail-table th, .detail-table td {
            border-bottom: 1px solid #eee;
            padding: 10px;
            text-align: left;
        }
        .detail-table th { background: #fafafa; font-weight: 600; }
        .ticket-link { text-decoration: none; color: var(--brand); font-weight: 600; }
        .adjust-form { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 6px; }
        .adjust-form input[type="number"] {
            width: 80px;
            padding: 4px 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .adjust-form textarea {
            flex: 1;
            min-width: 220px;
            min-height: 40px;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .adjust-form button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-primary { background: var(--brand); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .manual-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #ffeaa7;
            color: #8a6d3b;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 6px;
        }
        @media (max-width: 600px) {
            .adjust-form { flex-direction: column; align-items: stretch; }
            .adjust-form textarea { min-width: 100%; }
        }
    </style>
</head>
<body>
<div class="leaderboard-container">
    <div class="header">
        <div>
            <h1>Bucit Ranked</h1>
            <p style="margin:4px 0 0; color:#555;">Climb the trophy ladder by closing tickets. Higher difficulty = more trophies!</p>
        </div>
        <div class="header-actions">
            <a href="manage_tickets.php">← Back to tickets</a>
        </div>
    </div>

    <?php if ($flash_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash_message); ?></div><?php endif; ?>
    <?php if ($flash_error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>

    <?php if (empty($leaderboard_rows)): ?>
        <p>No trophies have been awarded yet. Close tickets to start the competition!</p>
    <?php else: ?>
    <div class="leaderboard-list">
        <?php foreach ($leaderboard_rows as $idx => $row): 
            $rank = $idx + 1;
            $is_active = ($selected_tech !== '' && $selected_tech === $row['tech']);
        ?>
            <a class="leader-row<?php echo $is_active ? ' active' : ''; ?>" href="leaderboard.php?tech=<?php echo urlencode($row['tech']); ?>#detail">
                <div class="leader-rank">#<?php echo $rank; ?></div>
                <div class="leader-body">
                    <div class="leader-name"><?php echo htmlspecialchars($row['display_name']); ?></div>
                    <div class="leader-meta">
                        <span><strong><?php echo intval($row['tickets_closed']); ?></strong> tickets closed</span>
                        <span class="trophy-count">🏆 <?php echo intval($row['total_points']); ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($selected_tech !== ''): ?>
    <div class="detail-section" id="detail">
        <div class="detail-header">
            <h2><?php echo htmlspecialchars($detail_display_name); ?> — Ticket trophies</h2>
            <div style="color:#666; font-size:0.9rem;">Username: <?php echo htmlspecialchars($selected_tech); ?></div>
        </div>
        <?php if (empty($detail_rows)): ?>
            <p style="margin-top:12px;">No awarded tickets yet.</p>
        <?php else: ?>
        <table class="detail-table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Category</th>
                    <th>Base</th>
                    <th>Awarded</th>
                    <th>Notes / Override</th>
                    <?php if ($is_admin): ?><th>Adjust</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detail_rows as $entry): ?>
                <tr>
                    <td>
                        <a class="ticket-link" href="edit_ticket.php?id=<?php echo intval($entry['ticket_id']); ?>" target="_blank">Ticket #<?php echo intval($entry['ticket_id']); ?></a>
                        <div style="font-size:0.85rem; color:#777;">Status: <?php echo htmlspecialchars($entry['ticket_status'] ?? ''); ?></div>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($entry['problem_category'] ?? ($entry['live_category'] ?? '')); ?>
                    </td>
                    <td><?php echo intval($entry['base_points']); ?></td>
                    <td>
                        🏆 <?php echo intval($entry['awarded_points']); ?>
                        <?php if (!empty($entry['manual_override'])): ?><span class="manual-badge">Manual</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($entry['notes'])): ?>
                            <div><?php echo nl2br(htmlspecialchars($entry['notes'])); ?></div>
                        <?php else: ?>
                            <span style="color:#aaa;">No notes</span>
                        <?php endif; ?>
                        <div style="font-size:0.75rem; color:#999; margin-top:4px;">
                            Last updated by <?php echo htmlspecialchars($entry['updated_by'] ?? 'system'); ?> on <?php echo htmlspecialchars(date('M j, Y g:i a', strtotime($entry['updated_at']))); ?>
                        </div>
                    </td>
                    <?php if ($is_admin): ?>
                    <td>
                        <form method="POST" class="adjust-form">
                            <input type="hidden" name="points_id" value="<?php echo intval($entry['id']); ?>">
                            <input type="hidden" name="selected_tech" value="<?php echo htmlspecialchars($selected_tech); ?>">
                            <?php
                                $current_category_value = $entry['problem_category'] ?? ($entry['live_category'] ?? '');
                                $normalized_category = strtolower(trim($current_category_value));
                                if (!array_key_exists($normalized_category, $bucit_point_defaults)) {
                                    $normalized_category = 'other';
                                }
                            ?>
                            <input type="hidden" name="current_category" value="<?php echo htmlspecialchars($current_category_value); ?>">
                            <select name="problem_category" style="min-width:140px;">
                                <?php foreach ($bucit_point_defaults as $catKey => $val): ?>
                                    <option value="<?php echo htmlspecialchars($catKey); ?>" <?php echo ($normalized_category === $catKey) ? 'selected' : ''; ?>><?php echo ucfirst($catKey); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="awarded_points" min="0" value="<?php echo intval($entry['awarded_points']); ?>" required>
                            <textarea name="notes" placeholder="Add admin note..."><?php echo htmlspecialchars($entry['notes']); ?></textarea>
                            <button type="submit" name="update_points" class="btn-primary">Save</button>
                            <button type="submit" name="reset_points" class="btn-secondary" onclick="return confirm('Reset to default points for this ticket?');">Reset</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>

<?php
// status.php - simple public page for students to check ticket status by ticket ID
require_once __DIR__ . '/includes/db_config.php';

$conn = getDbConnection();

$ticketId = trim($_GET['id'] ?? '');
$ticket = null;
$notFound = false;
if ($ticketId !== '') {
    // Use prepared statement for safety
    $priorityCheck = $conn->query("SHOW COLUMNS FROM tickets LIKE 'priority'");
    $has_priority = $priorityCheck && $priorityCheck->num_rows > 0;

    $select_cols = "id, first_name, last_name, status, notes, date_reported, problem_category, custom_detail";
    if ($has_priority) { $select_cols .= ", priority"; }

    $stmt = $conn->prepare("SELECT $select_cols FROM tickets WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $ticket = $res->fetch_assoc();
    } else {
        $notFound = true;
    }
    $stmt->close();
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Check Ticket Status</title>
<link rel="stylesheet" href="styles.css">
<style>
:root{ --brand: #800000; }
body{ background: var(--brand); color:#fff; }
nav{ background: #5a0000; padding: 12px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
nav div{ max-width: 1200px; margin: 0 auto; display: flex; gap: 20px; align-items: center; padding: 0 20px; }
nav a{ color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 4px; transition: background 0.2s; }
nav a:hover{ background: rgba(255,255,255,0.1); }
nav a.active{ background: rgba(255,255,255,0.15); }
.container{ max-width: 720px; margin: 48px auto; background: rgba(255,255,255,0.98); padding: 24px; border-radius: 8px; color:#222; }
.ticket-box{ padding: 12px; border-radius: 8px; border: 1px solid #ddd; background:#fff; }
.container-header{ display:flex; justify-content:space-between; align-items:center; gap:12px; }
.new-ticket-btn{ background:var(--brand); color:#fff; text-decoration:none; padding:10px 16px; border-radius:6px; font-weight:600; border:1px solid #5a0000; }
.new-ticket-btn:hover{ background:#5a0000; }
</style>
</head>
<body><nav>
  <div>
    <a href="index.html" style="display: flex; align-items: center; gap: 8px; font-weight: bold; font-size: 1.2em; margin-right: 10px;">
      <img src="img/buc.svg" alt="BucIT Logo" style="height: 32px; width: 32px;">
      <span>BucIT</span>
    </a>
    <a href="index.html">Home</a>
    <a href="form.html">Intake Form</a>
    <a href="status.php" class="active">Ticket Status</a>
    <a href="chatbot.html">Chatbot</a>
    <a href="articles/index.html">Articles</a>
  </div>
</nav>    <div class="container">
        <div class="container-header">
            <h1 style="margin:0;">Check your ticket</h1>
            <a class="new-ticket-btn" href="form.html">+ New Ticket</a>
        </div>
        <p>Enter your ticket ID to check the status and updates.</p>
        <form method="GET">
            <label for="id">Ticket ID</label>
            <input type="number" id="id" name="id" value="<?php echo htmlspecialchars($ticketId); ?>" required>
            <button type="submit">Check</button>
        </form>

        <?php if ($ticket): ?>
        <div style="margin-top: 20px;" class="ticket-box">
            <h2>Ticket #<?php echo htmlspecialchars($ticket['id']); ?> — <?php echo htmlspecialchars($ticket['status']); ?></h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></p>
            <p><strong>Date reported:</strong> <?php echo date('M d, Y', strtotime($ticket['date_reported'])); ?></p>
            <p><strong>Problem:</strong> <?php echo htmlspecialchars($ticket['problem_category']); ?></p>
            <?php if (!empty($ticket['custom_detail'])): ?><p><strong>Details:</strong> <?php echo htmlspecialchars($ticket['custom_detail']); ?></p><?php endif; ?>
            <?php if (!empty($ticket['notes'])): ?><p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($ticket['notes'])); ?></p><?php endif; ?>
            <?php if (!empty($ticket['priority'])): ?><p><strong>Priority:</strong> <?php echo htmlspecialchars($ticket['priority']); ?></p><?php endif; ?>
            <p><a href="form.html">Submit a new ticket</a></p>
        </div>
        <?php elseif ($notFound): ?>
        <div style="margin-top: 20px;" class="ticket-box">No ticket found with ID <?php echo htmlspecialchars($ticketId); ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
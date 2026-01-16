<?php
// submit_success.php - Landing page shown to users after submitting a ticket
$ticket_id = $_GET['ticket_id'] ?? null;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Ticket Submitted</title>
<link rel="icon" type="image/svg+xml" href="img/buc.svg">
<link rel="stylesheet" href="styles.css">
<style>
:root{ --brand: #800000; --card-bg: rgba(255,255,255,0.98); }
body{ background: var(--brand); color:#fff; min-height:100vh; }
.container{ max-width: 720px; margin: 48px auto; background: var(--card-bg); padding: 28px; border-radius: 8px; color:#222; }
.success-badge{ display:inline-block; padding: 8px 12px; border-radius: 6px; background: #d4edda; color:#155724; font-weight:700; }
.primary-action{ background: var(--brand); color:#fff; padding:10px 14px; border-radius:6px; text-decoration:none; display:inline-block; }
.secondary{ margin-left: 10px; color:#555; text-decoration:none; }
</style>
</head>
<body>
<div class="container">
  <h1 style="color:var(--brand);">Thank you — your ticket has been submitted</h1>

  <?php if ($ticket_id): ?>
    <p>Your ticket ID is <strong>#<?php echo htmlspecialchars($ticket_id); ?></strong>. Please keep this ID to check status or for reference when contacting tech support.</p>
  <?php else: ?>
    <p>Your ticket has been submitted successfully.</p>
  <?php endif; ?>

  <p><a class="primary-action" href="status.php">Check Ticket Status</a>
  <a class="secondary" href="form.html">Submit Another Ticket</a></p>

  <hr>
  <p style="font-size:0.95rem; color:#555;">This is the customer-facing confirmation. If you are a technician, <a href="manage_tickets.php">open the management dashboard</a> to view and manage tickets.</p>
</div>
</body>
</html>
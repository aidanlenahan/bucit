<?php
// edit_ticket.php - View and edit individual support tickets

// Database connection parameters
$servername = "localhost";
$username = "bucit";
$password = "m0Mih-Vdm!].Km8F";
$dbname = "bucit";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id || !is_numeric($ticket_id)) {
    header("Location: manage_tickets.php");
    exit();
}

// Start session to detect logged-in technician
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current_tech = $_SESSION['tech_user'] ?? null;

// Check whether priority column exists so UI can adapt
$priorityCheck = $conn->query("SHOW COLUMNS FROM tickets LIKE 'priority'");
$has_priority = ($priorityCheck && $priorityCheck->num_rows > 0);
// Check whether restarted column exists so UI can adapt
$restartedCheck = $conn->query("SHOW COLUMNS FROM tickets LIKE 'restarted'");
$has_restarted = ($restartedCheck && $restartedCheck->num_rows > 0);
// Check whether tech column exists so UI can adapt
$techCheck = $conn->query("SHOW COLUMNS FROM tickets LIKE 'tech'");
$has_tech = ($techCheck && $techCheck->num_rows > 0);

// Default point values for Bucit Ranked (per category)
$bucit_point_defaults = [
    'screen' => 10,
    'battery' => 5,
    'keyboard' => 7,
    'wifi' => 2,
    'login' => 3,
    'other' => 0
];

function bucit_points_table_exists($conn) {
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    $res = $conn->query("SHOW TABLES LIKE 'ticket_points'");
    $exists = ($res && $res->num_rows > 0);
    return $exists;
}

function bucit_default_points_for_category($category, $defaults) {
    $key = strtolower(trim($category ?? ''));
    return $defaults[$key] ?? 0;
}

function bucit_award_points_if_needed($conn, $ticket_id, $previous_status, $ticket, $defaults) {
    if (!bucit_points_table_exists($conn) || !$ticket) {
        return;
    }

    $current_status = $ticket['status'] ?? '';
    if (strcasecmp($current_status, 'Closed') !== 0) {
        return;
    }

    $tech = trim($ticket['tech'] ?? '');
    if ($tech === '' || strtolower($tech) === 'jmilonas') {
        return;
    }

    $problem_category = $ticket['problem_category'] ?? '';
    $base_points = bucit_default_points_for_category($problem_category, $defaults);

    $existing = null;
    $stmt = $conn->prepare('SELECT id, manual_override FROM ticket_points WHERE ticket_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $existing = $res->fetch_assoc();
        }
        $stmt->close();
    }

    $updated_by = 'system';

    if ($existing) {
        $manual_override = intval($existing['manual_override'] ?? 0);
        if ($manual_override) {
            $update = $conn->prepare('UPDATE ticket_points SET tech = ?, problem_category = ?, base_points = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
            if ($update) {
                $update->bind_param('ssisi', $tech, $problem_category, $base_points, $updated_by, $existing['id']);
                $update->execute();
                $update->close();
            }
        } else {
            $awarded = $base_points;
            $update = $conn->prepare('UPDATE ticket_points SET tech = ?, problem_category = ?, base_points = ?, awarded_points = ?, manual_override = 0, updated_by = ?, updated_at = NOW() WHERE id = ?');
            if ($update) {
                $update->bind_param('ssissi', $tech, $problem_category, $base_points, $awarded, $updated_by, $existing['id']);
                $update->execute();
                $update->close();
            }
        }
        return;
    }

    $awarded = $base_points;
    $insert = $conn->prepare('INSERT INTO ticket_points (ticket_id, tech, problem_category, base_points, awarded_points, manual_override, notes, updated_by) VALUES (?, ?, ?, ?, ?, 0, NULL, ?)');
    if ($insert) {
        $insert->bind_param('ississ', $ticket_id, $tech, $problem_category, $base_points, $awarded, $updated_by);
        $insert->execute();
        $insert->close();
    }
}

// Get ticket details (used for defaults on the form)
$sql = "SELECT * FROM tickets WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage_tickets.php");
    exit();
}

$ticket = $result->fetch_assoc();
$stmt->close();

$current_ticket_tech = $ticket['tech'] ?? '';
if (!empty($current_ticket_tech) && !isset($tech_display_lookup[$current_ticket_tech])) {
    $tech_options[] = [
        'username' => $current_ticket_tech,
        'display_name' => $current_ticket_tech,
        'active' => 0
    ];
    $tech_display_lookup[$current_ticket_tech] = $current_ticket_tech;
}

$previous_status = $ticket['status'] ?? null;
$assigned_tech_display = '';
if (!empty($ticket['tech']) && isset($tech_display_lookup[$ticket['tech']])) {
    $assigned_tech_display = $tech_display_lookup[$ticket['tech']];
} elseif (!empty($ticket['tech'])) {
    $assigned_tech_display = $ticket['tech'];
}

// Load technicians from DB for dropdown/display (fallback to current assignment if needed)
$tech_options = [];
$tech_display_lookup = [];

$tech_query = $conn->query("SELECT username, COALESCE(display_name, username) AS display_name, active FROM technicians ORDER BY display_name ASC");
if ($tech_query && $tech_query->num_rows > 0) {
    while ($row = $tech_query->fetch_assoc()) {
        $tech_options[] = $row;
        $tech_display_lookup[$row['username']] = $row['display_name'];
    }
}

// Load available inventory parts
$inventory_parts = [];
$inventory_query = $conn->query("SELECT id, part_name, part_number, quantity FROM inventory WHERE quantity > 0 ORDER BY part_name ASC");
if ($inventory_query && $inventory_query->num_rows > 0) {
    while ($row = $inventory_query->fetch_assoc()) {
        $inventory_parts[] = $row;
    }
}

// Load parts already attached to this ticket
$attached_parts = [];
$attached_query = $conn->prepare("SELECT tp.id, tp.part_id, tp.quantity_used, i.part_name, i.part_number FROM ticket_parts tp JOIN inventory i ON tp.part_id = i.id WHERE tp.ticket_id = ?");
if ($attached_query) {
    $attached_query->bind_param('i', $ticket_id);
    $attached_query->execute();
    $result = $attached_query->get_result();
    while ($row = $result->fetch_assoc()) {
        $attached_parts[] = $row;
    }
    $attached_query->close();
}


// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle parts attachment/removal
    if (isset($_POST['attach_parts'])) {
        $selected_parts = $_POST['parts'] ?? [];
        $part_quantities = $_POST['part_quantities'] ?? [];
        
        if (!empty($selected_parts)) {
            // Begin transaction for inventory safety
            $conn->begin_transaction();
            
            try {
                foreach ($selected_parts as $part_id) {
                    $part_id = intval($part_id);
                    $quantity = isset($part_quantities[$part_id]) ? intval($part_quantities[$part_id]) : 1;
                    
                    if ($quantity < 1) $quantity = 1;
                    
                    // Check if sufficient inventory exists
                    $check = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
                    $check->bind_param('i', $part_id);
                    $check->execute();
                    $result = $check->get_result();
                    $check->close();
                    
                    if ($result->num_rows == 0) {
                        throw new Exception("Part not found");
                    }
                    
                    $part = $result->fetch_assoc();
                    if ($part['quantity'] < $quantity) {
                        throw new Exception("Insufficient inventory for selected part");
                    }
                    
                    // Check if part already attached to avoid duplicates
                    $existing = $conn->prepare("SELECT id FROM ticket_parts WHERE ticket_id = ? AND part_id = ?");
                    $existing->bind_param('ii', $ticket_id, $part_id);
                    $existing->execute();
                    $existing_result = $existing->get_result();
                    $existing->close();
                    
                    if ($existing_result->num_rows > 0) {
                        continue; // Skip if already attached
                    }
                    
                    // Attach part to ticket
                    $attach = $conn->prepare("INSERT INTO ticket_parts (ticket_id, part_id, quantity_used, added_by) VALUES (?, ?, ?, ?)");
                    $attach->bind_param('iiis', $ticket_id, $part_id, $quantity, $current_tech);
                    $attach->execute();
                    $attach->close();
                    
                    // Deduct from inventory
                    $deduct = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
                    $deduct->bind_param('ii', $quantity, $part_id);
                    $deduct->execute();
                    $deduct->close();
                }
                
                $conn->commit();
                $success_message = "Parts attached successfully and inventory updated!";
                
                // Reload attached parts
                $attached_parts = [];
                $attached_query = $conn->prepare("SELECT tp.id, tp.part_id, tp.quantity_used, i.part_name, i.part_number FROM ticket_parts tp JOIN inventory i ON tp.part_id = i.id WHERE tp.ticket_id = ?");
                if ($attached_query) {
                    $attached_query->bind_param('i', $ticket_id);
                    $attached_query->execute();
                    $result = $attached_query->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $attached_parts[] = $row;
                    }
                    $attached_query->close();
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Error attaching parts: " . $e->getMessage();
            }
        }
    }
    // If this is a pickup action, only update status and tech (preserve all other fields)
    elseif (isset($_POST['pickup'])) {
        if (empty($current_tech)) {
            $error_message = 'You must be logged in to pick up a ticket.';
        } else {
            // Pickup: only update status and tech, leave all other fields as-is
            $fields = ['status = ?'];
            $params = ['In Progress'];
            $types = 's';
            
            if ($has_tech) {
                $fields[] = 'tech = ?';
                $params[] = $current_tech;
                $types .= 's';
            }
            
            $update_sql = 'UPDATE tickets SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $params[] = $ticket_id;
            $types .= 'i';
            
            $stmt = $conn->prepare($update_sql);
            if ($stmt) {
                $a_params = array_merge([$types], $params);
                $refs = [];
                foreach ($a_params as $key => $value) {
                    $refs[$key] = &$a_params[$key];
                }
                call_user_func_array([$stmt, 'bind_param'], $refs);
                
                if ($stmt->execute()) {
                    $success_message = "Ticket picked up successfully!";
                    // Refresh ticket data
                    $stmt->close();
                    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
                    $stmt->bind_param('i', $ticket_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows) {
                        $ticket = $res->fetch_assoc();
                        bucit_award_points_if_needed($conn, $ticket_id, $previous_status, $ticket, $bucit_point_defaults);
                        $previous_status = $ticket['status'] ?? $previous_status;
                    }
                    $stmt->close();
                } else {
                    $error_message = "Error picking up ticket: " . $conn->error;
                    $stmt->close();
                }
            } else {
                $error_message = "Prepare failed: " . $conn->error;
            }
        }
    } else {
        // Normal edit: Collect editable fields (basic sanitization)
        $status = $_POST['status'] ?? 'Open';
        $notes = trim($_POST['notes'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $schoolId = trim($_POST['school_id'] ?? '');
        $classOf = trim($_POST['class_of'] ?? '');
        $schoolEmail = trim($_POST['school_email'] ?? '');
        $additionalInfo = trim($_POST['additional_info'] ?? '');
        $dateReported = $_POST['date_reported'] ?? '';
        $problemCategory = $_POST['problem_category'] ?? '';
        $problemDetail = trim($_POST['problem_detail'] ?? '');
        $customDetail = trim($_POST['custom_detail'] ?? '');
        $priority = intval($_POST['priority'] ?? 3);
        $restarted = null;
        if ($has_restarted) {
            // Expect numeric 1/0 from the edit form
            $restarted = isset($_POST['restarted']) ? intval($_POST['restarted']) : (isset($ticket['restarted']) ? intval($ticket['restarted']) : 0);
        }
        $tech = null;
        if ($has_tech) {
            $tech = trim($_POST['tech'] ?? ($ticket['tech'] ?? ''));
        }

        // If ticket is being Closed, force priority to 5 so closed tickets don't interfere with priority sorting
        if ($has_priority && strcasecmp(trim($status), 'Closed') === 0) {
            $priority = 5;
        }

        // If date not provided, preserve current value
        if (empty($dateReported)) {
            $dateReported = $ticket['date_reported'];
        }

        // Build dynamic UPDATE depending on which optional columns exist
        $fields = [
            'status = ?', 'notes = ?', 'first_name = ?', 'last_name = ?',
            'school_id = ?', 'class_of = ?', 'school_email = ?', 'additional_info = ?', 'date_reported = ?',
            'problem_category = ?', 'problem_detail = ?', 'custom_detail = ?'
        ];
        $params = [
            $status, $notes, $firstName, $lastName, $schoolId, $classOf, $schoolEmail, $additionalInfo, $dateReported, $problemCategory, $problemDetail, $customDetail
        ];
        $types = str_repeat('s', count($params));

        if ($has_priority) {
            $fields[] = 'priority = ?';
            $params[] = $priority;
            $types .= 'i';
        }

        if ($has_restarted) {
            $fields[] = 'restarted = ?';
            $params[] = $restarted;
            $types .= 'i';
        }

        if ($has_tech) {
            $fields[] = 'tech = ?';
            $params[] = $tech;
            $types .= 's';
        }

        $update_sql = 'UPDATE tickets SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $params[] = $ticket_id;
        $types .= 'i';

        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            // bind params dynamically (mysqli requires references)
            $a_params = array_merge([$types], $params);
            $refs = [];
            foreach ($a_params as $key => $value) {
                $refs[$key] = &$a_params[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);

            if ($stmt->execute()) {
                $success_message = "Ticket updated successfully!";
                // Refresh ticket data from DB
                $stmt->close();
                $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
                $stmt->bind_param('i', $ticket_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows) {
                    $ticket = $res->fetch_assoc();
                        bucit_award_points_if_needed($conn, $ticket_id, $previous_status, $ticket, $bucit_point_defaults);
                        $previous_status = $ticket['status'] ?? $previous_status;
                }
                $stmt->close();
            } else {
                $error_message = "Error updating ticket: " . $conn->error;
                $stmt->close();
            }
        } else {
            $error_message = "Prepare failed: " . $conn->error;
        }
    }
}

// Get ticket details
$sql = "SELECT * FROM tickets WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage_tickets.php");
    exit();
}

$ticket = $result->fetch_assoc();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ticket #<?php echo htmlspecialchars($ticket['id']); ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Theme variables */
        :root { --brand: #800000; --brand-dark: #5a0000; --card-bg: rgba(255,255,255,0.98); }

        body {
            background: var(--brand);
            color: #fff;
            min-height: 100vh;
        }
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 8px;
            color: #222;
        }

        .ticket-header h1 {
            color: var(--brand);
            margin: 0;
        }
        
        .ticket-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .ticket-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .ticket-management {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn {
            background-color: var(--brand);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn:hover {
            background-color: var(--brand-dark);
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #545b62;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group select,
        
        
        
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-open {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-in-progress {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-resolved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-delayed {
            background-color: #ffeaa7;
            color: #856404;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #333;
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="ticket-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Ticket #<?php echo htmlspecialchars($ticket['id']); ?></h1>
                <div>
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $ticket['status'] ?? 'open')); ?>">
                        <?php echo htmlspecialchars($ticket['status'] ?? 'Open'); ?>
                    </span>
                </div>
            </div>
            <div style="margin-top:8px; display:flex; gap:10px; align-items:center; justify-content:space-between;">
                <div>
                    <?php if (!empty($current_tech)): ?>
                        <small>Logged in as <strong><?php echo htmlspecialchars($current_tech); ?></strong> — <a href="change_password.php">Change password</a> | <a href="logout.php">Log out</a></small>
                    <?php else: ?>
                        <small><a href="login.php">Technician log in</a></small>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top: 10px;">
                <a href="manage_tickets.php" class="btn btn-secondary">← Back to Tickets</a>
            </div>
        </div>
        
        <div class="ticket-details">
            <h2>Ticket Details</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Student Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">School ID</div>
                    <div class="detail-value"><?php echo htmlspecialchars($ticket['school_id']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Class of</div>
                    <div class="detail-value"><?php echo htmlspecialchars($ticket['class_of']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">School Email</div>
                    <div class="detail-value"><?php echo htmlspecialchars($ticket['school_email'] ?? ''); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Date Reported</div>
                    <div class="detail-value"><?php echo date('F d, Y', strtotime($ticket['date_reported'])); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Date Created</div>
                    <div class="detail-value"><?php echo date('F d, Y g:i A', strtotime($ticket['created_at'])); ?></div>
                </div>
                <?php if ($has_restarted): ?>
                <div class="detail-item">
                    <div class="detail-label">Restarted?</div>
                    <div class="detail-value"><?php echo (!empty($ticket['restarted']) && $ticket['restarted']) ? 'Yes' : 'No'; ?></div>
                </div>
                <?php endif; ?>
                <?php if ($has_tech): ?>
                <div class="detail-item">
                    <div class="detail-label">Tech</div>
                    <div class="detail-value"><?php echo htmlspecialchars($assigned_tech_display ?: ($ticket['tech'] ?? '')); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Problem Category</div>
                    <div class="detail-value"><?php echo htmlspecialchars($ticket['problem_category']); ?></div>
                </div>
                
                <?php if ($ticket['problem_detail']): ?>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Problem Detail</div>
                    <div class="detail-value"><?php echo htmlspecialchars($ticket['problem_detail']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($ticket['custom_detail']): ?>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Custom Detail</div>
                    <div class="detail-value"><?php echo htmlspecialchars($ticket['custom_detail']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($ticket['additional_info']): ?>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Additional Information</div>
                    <div class="detail-value"><?php echo htmlspecialchars($ticket['additional_info']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($ticket['notes']): ?>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Admin Notes</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($ticket['notes'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="ticket-management">
            <h2>Update Ticket</h2>
            <?php if (!empty($current_tech) && (empty($ticket['tech']) || $ticket['tech'] !== $current_tech)): ?>
                <form method="POST" style="display:inline-block; margin-bottom:10px;">
                    <input type="hidden" name="pickup" value="1">
                    <button type="submit" class="btn">Pick up ticket</button>
                </form>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input id="first_name" name="first_name" value="<?php echo htmlspecialchars($ticket['first_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input id="last_name" name="last_name" value="<?php echo htmlspecialchars($ticket['last_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="school_id">School ID</label>
                    <input id="school_id" name="school_id" value="<?php echo htmlspecialchars($ticket['school_id'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="class_of">Class of</label>
                    <input id="class_of" name="class_of" value="<?php echo htmlspecialchars($ticket['class_of'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="school_email">School Email</label>
                    <input type="email" id="school_email" name="school_email" value="<?php echo htmlspecialchars($ticket['school_email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="additional_info">Additional Information</label>
                    <textarea id="additional_info" name="additional_info" maxlength="120" rows="3"><?php echo htmlspecialchars($ticket['additional_info'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="date_reported">Date Reported</label>
                    <input type="date" id="date_reported" name="date_reported" value="<?php echo htmlspecialchars($ticket['date_reported'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="problem_category">Problem Category</label>
                    <select id="problem_category" name="problem_category">
                        <option value="screen" <?php echo ($ticket['problem_category'] ?? '') == 'screen' ? 'selected' : ''; ?>>Screen not working</option>
                        <option value="battery" <?php echo ($ticket['problem_category'] ?? '') == 'battery' ? 'selected' : ''; ?>>Battery issue</option>
                        <option value="keyboard" <?php echo ($ticket['problem_category'] ?? '') == 'keyboard' ? 'selected' : ''; ?>>Keyboard not working</option>
                        <option value="wifi" <?php echo ($ticket['problem_category'] ?? '') == 'wifi' ? 'selected' : ''; ?>>WiFi connectivity</option>
                        <option value="login" <?php echo ($ticket['problem_category'] ?? '') == 'login' ? 'selected' : ''; ?>>Can’t log in</option>
                        <option value="other" <?php echo ($ticket['problem_category'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="problem_detail">Problem Detail</label>
                    <select id="problem_detail" name="problem_detail">
                        <option value="">— None —</option>
                        <optgroup label="Battery">
                            <option value="dies_quickly" <?php echo ($ticket['problem_detail'] ?? '') == 'dies_quickly' ? 'selected' : ''; ?>>Dies quickly</option>
                            <option value="does_not_charge" <?php echo ($ticket['problem_detail'] ?? '') == 'does_not_charge' ? 'selected' : ''; ?>>Does not charge</option>
                        </optgroup>
                        <optgroup label="Screen">
                            <option value="cracked" <?php echo ($ticket['problem_detail'] ?? '') == 'cracked' ? 'selected' : ''; ?>>Cracked</option>
                            <option value="black_screen" <?php echo ($ticket['problem_detail'] ?? '') == 'black_screen' ? 'selected' : ''; ?>>Black screen</option>
                            <option value="flickering_display" <?php echo ($ticket['problem_detail'] ?? '') == 'flickering_display' ? 'selected' : ''; ?>>Flickering display</option>
                        </optgroup>
                        <optgroup label="Keyboard">
                            <option value="key(s)_stuck" <?php echo ($ticket['problem_detail'] ?? '') == 'key(s)_stuck' ? 'selected' : ''; ?>>Key(s) stuck</option>
                            <option value="not_typing" <?php echo ($ticket['problem_detail'] ?? '') == 'not_typing' ? 'selected' : ''; ?>>Not typing</option>
                            <option value="keys_missing" <?php echo ($ticket['problem_detail'] ?? '') == 'keys_missing' ? 'selected' : ''; ?>>Keys missing</option>
                        </optgroup>
                        <optgroup label="WiFi">
                            <option value="can't_connect" <?php echo ($ticket['problem_detail'] ?? '') == 'can\'t_connect' ? 'selected' : ''; ?>>Can't connect</option>
                            <option value="drops_connection" <?php echo ($ticket['problem_detail'] ?? '') == 'drops_connection' ? 'selected' : ''; ?>>Drops connection</option>
                            <option value="slow_speeds" <?php echo ($ticket['problem_detail'] ?? '') == 'slow_speeds' ? 'selected' : ''; ?>>Slow speeds</option>
                        </optgroup>
                        <optgroup label="Login">
                            <option value="forgot_password" <?php echo ($ticket['problem_detail'] ?? '') == 'forgot_password' ? 'selected' : ''; ?>>Forgot password</option>
                            <option value="account_locked" <?php echo ($ticket['problem_detail'] ?? '') == 'account_locked' ? 'selected' : ''; ?>>Account locked</option>
                        </optgroup>
                        <optgroup label="Other">
                            <option value="something_else" <?php echo ($ticket['problem_detail'] ?? '') == 'something_else' ? 'selected' : ''; ?>>Something else</option>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label for="custom_detail">Custom Detail</label>
                    <textarea id="custom_detail" name="custom_detail"><?php echo htmlspecialchars($ticket['custom_detail'] ?? ''); ?></textarea>
                </div>

                <?php if ($has_priority): ?>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="1" <?php echo ($ticket['priority'] ?? 3) == 1 ? 'selected' : ''; ?>>1 — Urgent</option>
                        <option value="2" <?php echo ($ticket['priority'] ?? 3) == 2 ? 'selected' : ''; ?>>2 — High</option>
                        <option value="3" <?php echo ($ticket['priority'] ?? 3) == 3 ? 'selected' : ''; ?>>3 — Normal</option>
                        <option value="4" <?php echo ($ticket['priority'] ?? 3) == 4 ? 'selected' : ''; ?>>4 — Low</option>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($has_restarted): ?>
                <div class="form-group">
                    <label for="restarted">Restarted?</label>
                    <select id="restarted" name="restarted">
                        <option value="1" <?php echo (!empty($ticket['restarted']) && $ticket['restarted']) ? 'selected' : ''; ?>>Yes</option>
                        <option value="0" <?php echo (empty($ticket['restarted']) || !$ticket['restarted']) ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($has_tech): ?>
                <div class="form-group">
                    <label for="tech">Tech</label>
                    <select id="tech" name="tech">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($tech_options as $tech_row): ?>
                            <?php $value = $tech_row['username']; $label = $tech_row['display_name']; ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo (!empty($ticket['tech']) && $ticket['tech'] === $value) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?><?php echo empty($tech_row['active']) ? ' (inactive)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="Open" <?php echo ($ticket['status'] ?? 'Open') == 'Open' ? 'selected' : ''; ?>>Open</option>
                        <option value="In Progress" <?php echo ($ticket['status'] ?? '') == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Closed" <?php echo ($ticket['status'] ?? '') == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                        <option value="Delayed" <?php echo ($ticket['status'] ?? '') == 'Delayed' ? 'selected' : ''; ?>>Delayed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Admin Notes</label>
                    <textarea id="notes" name="notes" placeholder="Add notes about this ticket..."><?php echo htmlspecialchars($ticket['notes'] ?? ''); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="submit" class="btn btn-success">Update Ticket</button>
                    <?php if (($ticket['status'] ?? '') !== 'Closed'): ?>
                        <a href="close_ticket.php?ticket_id=<?php echo $ticket_id; ?>" class="btn" style="background: #28a745; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px;">✓ Close Ticket & Send Email</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Parts Management Section -->
            <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd;">
                <h3 style="color: var(--brand);">📦 Replacement Parts</h3>
                
                <?php if (!empty($attached_parts)): ?>
                <div style="margin-bottom: 20px;">
                    <h4>Parts Used on This Ticket</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: var(--brand); color: white;">
                                <th style="padding: 10px; text-align: left;">Part Name</th>
                                <th style="padding: 10px; text-align: left;">Part Number</th>
                                <th style="padding: 10px; text-align: center;">Quantity Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attached_parts as $part): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($part['part_name']); ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($part['part_number'] ?? '-'); ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo intval($part['quantity_used']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($inventory_parts)): ?>
                <form method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="attach_parts" value="1">
                    <h4>Attach Parts to This Ticket</h4>
                    <p style="color: #666; margin-bottom: 15px;">Select all parts used for this repair. Inventory will be automatically deducted.</p>
                    
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                        <?php foreach ($inventory_parts as $part): ?>
                            <?php 
                            // Check if already attached
                            $already_attached = false;
                            foreach ($attached_parts as $attached) {
                                if ($attached['part_id'] == $part['id']) {
                                    $already_attached = true;
                                    break;
                                }
                            }
                            ?>
                            <div style="padding: 10px; margin-bottom: 5px; background: white; border-radius: 4px; display: flex; align-items: center; gap: 10px; <?php echo $already_attached ? 'opacity: 0.5;' : ''; ?>">
                                <input type="checkbox" 
                                       name="parts[]" 
                                       value="<?php echo $part['id']; ?>" 
                                       id="part_<?php echo $part['id']; ?>"
                                       <?php echo $already_attached ? 'disabled' : ''; ?>>
                                <label for="part_<?php echo $part['id']; ?>" style="flex: 1; margin: 0; cursor: pointer;">
                                    <strong><?php echo htmlspecialchars($part['part_name']); ?></strong>
                                    <?php if ($part['part_number']): ?>
                                        <span style="color: #666;">(<?php echo htmlspecialchars($part['part_number']); ?>)</span>
                                    <?php endif; ?>
                                    <span style="color: <?php echo $part['quantity'] <= 5 ? '#856404' : '#28a745'; ?>; font-weight: bold;"> — <?php echo $part['quantity']; ?> available</span>
                                    <?php if ($already_attached): ?>
                                        <span style="color: #999; font-style: italic;"> (Already attached)</span>
                                    <?php endif; ?>
                                </label>
                                <input type="number" 
                                       name="part_quantities[<?php echo $part['id']; ?>]" 
                                       min="1" 
                                       max="<?php echo $part['quantity']; ?>" 
                                       value="1" 
                                       style="width: 60px; padding: 5px;"
                                       <?php echo $already_attached ? 'disabled' : ''; ?>>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <button type="submit" class="btn btn-success">Attach Selected Parts</button>
                        <a href="inventory.php" class="btn" style="background-color: #6c757d;">Manage Inventory</a>
                    </div>
                </form>
                <?php else: ?>
                <p style="color: #666;">No parts currently available in inventory. <a href="inventory.php">Add parts to inventory</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
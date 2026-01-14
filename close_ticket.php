<?php
/**
 * Close Ticket Script
 * Handles closing tickets and sending email notifications
 */

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

// Include email functions
require_once 'includes/functions.php';

// Start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current_tech = $_SESSION['tech_user'] ?? null;

// Check if technician is logged in
if (empty($current_tech)) {
    header('Location: tech_login.php');
    exit();
}

$ticket_id = $_POST['ticket_id'] ?? $_GET['ticket_id'] ?? null;
$resolution = $_POST['resolution'] ?? '';
$send_email = isset($_POST['send_email']) ? intval($_POST['send_email']) : 1;

// If this is a GET request, show the form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $ticket_id) {
    // Fetch ticket details
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->bind_param('i', $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    $stmt->close();
    
    if (!$ticket) {
        echo "Ticket not found.";
        exit();
    }
    
    // Display form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Close Ticket #<?php echo htmlspecialchars($ticket['id']); ?></title>
        <link rel="stylesheet" href="styles.css">
        <style>
            .close-ticket-container {
                max-width: 800px;
                margin: 40px auto;
                padding: 30px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .ticket-info {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 25px;
                border-left: 4px solid #667eea;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
            }
            .form-group textarea {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-family: inherit;
                font-size: 14px;
                resize: vertical;
                min-height: 150px;
            }
            .form-group input[type="checkbox"] {
                margin-right: 8px;
            }
            .button-group {
                display: flex;
                gap: 10px;
                margin-top: 25px;
            }
            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                transition: all 0.2s;
            }
            .btn-primary {
                background: #667eea;
                color: white;
            }
            .btn-primary:hover {
                background: #5568d3;
            }
            .btn-secondary {
                background: #e0e0e0;
                color: #333;
            }
            .btn-secondary:hover {
                background: #d0d0d0;
            }
        </style>
    </head>
    <body>
        <div class="close-ticket-container">
            <h1>Close Ticket #<?php echo htmlspecialchars($ticket['id']); ?></h1>
            
            <div class="ticket-info">
                <p><strong>Student:</strong> <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></p>
                <p><strong>School ID:</strong> <?php echo htmlspecialchars($ticket['school_id']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['school_email'] ?? 'N/A'); ?></p>
                <p><strong>Problem:</strong> <?php echo htmlspecialchars($ticket['problem_category'] ?? 'N/A'); ?></p>
                <p><strong>Details:</strong> <?php echo htmlspecialchars($ticket['problem_detail'] ?? 'N/A'); ?></p>
                <?php if (!empty($ticket['custom_detail'])): ?>
                    <p><strong>Additional Info:</strong> <?php echo htmlspecialchars($ticket['custom_detail']); ?></p>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="close_ticket.php">
                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['id']); ?>">
                
                <div class="form-group">
                    <label for="resolution">Resolution Notes: *</label>
                    <textarea name="resolution" id="resolution" required placeholder="Describe how the issue was resolved..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="send_email" value="1" checked>
                        Send email notification to student
                    </label>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Close Ticket & Send Email</button>
                    <a href="edit_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Handle POST request - close the ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ticket_id) {
    // Validate ticket exists
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->bind_param('i', $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    $stmt->close();
    
    if (!$ticket) {
        die("Ticket not found.");
    }
    
    // Check if ticket has required columns
    $has_tech = false;
    $techCheck = $conn->query("SHOW COLUMNS FROM tickets LIKE 'tech'");
    if ($techCheck && $techCheck->num_rows > 0) {
        $has_tech = true;
    }
    
    $has_priority = false;
    $priorityCheck = $conn->query("SHOW COLUMNS FROM tickets LIKE 'priority'");
    if ($priorityCheck && $priorityCheck->num_rows > 0) {
        $has_priority = true;
    }
    
    // Prepare update query
    $fields = ['status = ?', 'notes = ?'];
    $params = ['Closed', $resolution];
    $types = 'ss';
    
    // Set priority to 5 for closed tickets (if column exists)
    if ($has_priority) {
        $fields[] = 'priority = ?';
        $params[] = 5;
        $types .= 'i';
    }
    
    // Set tech to current technician (if column exists and not already set)
    if ($has_tech && empty($ticket['tech'])) {
        $fields[] = 'tech = ?';
        $params[] = $current_tech;
        $types .= 's';
    }
    
    $update_sql = 'UPDATE tickets SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $params[] = $ticket_id;
    $types .= 'i';
    
    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        // Bind parameters dynamically
        $a_params = array_merge([$types], $params);
        $refs = [];
        foreach ($a_params as $key => $value) {
            $refs[$key] = &$a_params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Send email if requested and email is available
            $email_sent = false;
            if ($send_email && !empty($ticket['school_email'])) {
                $ticketData = [
                    'ticketNumber' => $ticket['id'],
                    'status' => 'Closed',
                    'resolution' => $resolution,
                    'closedDate' => date('Y-m-d H:i:s'),
                    'problemCategory' => $ticket['problem_category'] ?? 'N/A',
                    'problemDetail' => $ticket['problem_detail'] ?? 'N/A'
                ];
                
                $email_sent = sendTicketClosedEmail(
                    $ticket['school_email'],
                    $ticket['first_name'],
                    $ticket['last_name'],
                    $ticketData
                );
            }
            
            // Redirect back to ticket with success message
            $message = "Ticket closed successfully!";
            if ($send_email && !empty($ticket['school_email'])) {
                $message .= $email_sent ? " Email notification sent." : " (Email failed to send)";
            }
            
            header("Location: edit_ticket.php?id=$ticket_id&success=" . urlencode($message));
            exit();
        } else {
            $error = "Error closing ticket: " . $conn->error;
            $stmt->close();
        }
    } else {
        $error = "Prepare failed: " . $conn->error;
    }
    
    // If we get here, there was an error
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    echo "<h2>Error Closing Ticket</h2>";
    echo "<p>" . htmlspecialchars($error) . "</p>";
    echo "<a href='edit_ticket.php?id=$ticket_id'>Go Back</a>";
    echo "</body></html>";
    exit();
}

// If no ticket_id provided
echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
echo "<h2>No Ticket ID Provided</h2>";
echo "<a href='manage_tickets.php'>Return to Tickets</a>";
echo "</body></html>";
?>

<?php
// submit_ticket.php
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/functions.php';

// Connection is already established in db_config.php as $conn

// Collect form data safely
$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$schoolId = $_POST['schoolId'] ?? '';
$classOf = $_POST['classOf'] ?? '';
$schoolEmail = $_POST['schoolEmail'] ?? '';
$additionalInfo = $_POST['additionalInfo'] ?? '';
$date = date('Y-m-d'); // set report date to server submission date
$problem = $_POST['problem'] ?? '';
$subproblem = $_POST['subproblem'] ?? null;
$customDetail = $_POST['custom-detail'] ?? null;
// restarted: expect 'yes' or 'no' from the form; default to 0 (no)
$restarted = (isset($_POST['restarted']) && $_POST['restarted'] === 'yes') ? 1 : 0;

// Prepare SQL statement
$stmt = $conn->prepare(
  "INSERT INTO tickets (
    first_name, last_name, school_id, class_of, school_email, additional_info, date_reported,
    problem_category, problem_detail, custom_detail, restarted
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
  echo "Prepare failed: " . $conn->error;
  $conn->close();
  exit;
}

$bind = $stmt->bind_param(
  "ssssssssssi",
  $firstName,
  $lastName,
  $schoolId,
  $classOf,
  $schoolEmail,
  $additionalInfo,
  $date,
  $problem,
  $subproblem,
  $customDetail,
  $restarted
);

if ($bind === false) {
  echo "Bind failed: " . $stmt->error;
  $stmt->close();
  $conn->close();
  exit;
}

if ($stmt->execute()) {
  $ticket_id = $conn->insert_id;
  
  // Send confirmation email
  $ticketData = [
    'ticketNumber' => $ticket_id,
    'problem' => $problem,
    'problemDetail' => $subproblem ?: $customDetail,
    'dateReported' => $date
  ];
  sendTicketCreatedEmail($schoolEmail, $firstName, $lastName, $ticketData);
  
  $stmt->close();
  $conn->close();
  
  // Redirect to user-facing success page
  header("Location: submit_success.php?ticket_id=" . $ticket_id);
  exit();
} else {
  echo "<p style=\"color:red\">Execute failed: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();

<?php
// submit_ticket.php

// Database connection parameters
$servername = "localhost";
$username = "bucit";       // or your DB username
$password = "m0Mih-Vdm!].Km8F";           // your DB password
$dbname = "bucit";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Collect form data safely
$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$schoolId = $_POST['schoolId'] ?? '';
$classOf = $_POST['classOf'] ?? '';
$phone = $_POST['phone'] ?? '';
$date = date('Y-m-d'); // set report date to server submission date
$problem = $_POST['problem'] ?? '';
$subproblem = $_POST['subproblem'] ?? null;
$customDetail = $_POST['custom-detail'] ?? null;
// restarted: expect 'yes' or 'no' from the form; default to 0 (no)
$restarted = (isset($_POST['restarted']) && $_POST['restarted'] === 'yes') ? 1 : 0;

// Prepare SQL statement
$stmt = $conn->prepare(
  "INSERT INTO tickets (
    first_name, last_name, school_id, class_of, phone, date_reported,
    problem_category, problem_detail, custom_detail, restarted
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
  echo "Prepare failed: " . $conn->error;
  $conn->close();
  exit;
}

$bind = $stmt->bind_param(
  "sssssssssi",
  $firstName,
  $lastName,
  $schoolId,
  $classOf,
  $phone,
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

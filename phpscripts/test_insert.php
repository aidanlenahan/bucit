<?php
// test_insert.php - quick DB test
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/db_config.php';

$conn = getDbConnection();

$firstName = 'TestFirst';
$lastName = 'TestLast';
$schoolId = '123456';
$classOf = '2026';
$phone = '5551234567';
$date = date('Y-m-d');
$problem = 'test_problem';
$subproblem = 'test_detail';
$customDetail = 'none';

$stmt = $conn->prepare("INSERT INTO tickets (first_name, last_name, school_id, class_of, phone, date_reported, problem_category, problem_detail, custom_detail) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
  die('Prepare failed: ' . $conn->error);
}
$stmt->bind_param('sssssssss', $firstName, $lastName, $schoolId, $classOf, $phone, $date, $problem, $subproblem, $customDetail);
if (!$stmt->execute()) {
  die('Execute failed: ' . $stmt->error);
}
$insert_id = $conn->insert_id;

echo "Inserted row ID: $insert_id\n";

// Show the inserted row
$res = $conn->query("SELECT * FROM tickets WHERE id = " . intval($insert_id));
if ($res) {
  $row = $res->fetch_assoc();
  echo "\nRow data:\n";
  print_r($row);
} else {
  echo "Query failed: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
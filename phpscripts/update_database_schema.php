<?php
/**
 * Database Schema Update Script
 * 
 * This script adds new fields to the tickets table:
 * - chromebook_sn: Chromebook Serial Number (displayed in status.php)
 * - private_notes: Private technician notes (NOT displayed in status.php)
 * - picked_up: Yes/No field for whether ticket has been picked up
 * - pickup_date: Date when ticket was picked up
 * 
 * Run this file once by visiting it in your browser or running:
 * php update_database_schema.php
 */

require_once __DIR__ . '/includes/db_config.php';

// Track changes made
$changes = [];
$errors = [];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Schema Update</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #800000; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-left: 4px solid red; margin: 10px 0; }
        .info { color: #666; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196F3; margin: 10px 0; }
        .warning { color: #856404; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; margin: 10px 0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Database Schema Update</h1>
    <p>Updating the <code>tickets</code> table with new fields...</p>
";

// Function to check if column exists
function column_exists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// 1. Add chromebook_sn field
if (!column_exists($conn, 'tickets', 'chromebook_sn')) {
    $sql = "ALTER TABLE tickets ADD COLUMN chromebook_sn VARCHAR(50) DEFAULT NULL COMMENT 'Chromebook Serial Number' AFTER custom_detail";
    if ($conn->query($sql)) {
        $changes[] = "Added column 'chromebook_sn' to tickets table";
    } else {
        $errors[] = "Failed to add 'chromebook_sn': " . $conn->error;
    }
} else {
    $changes[] = "Column 'chromebook_sn' already exists - skipped";
}

// 2. Add private_notes field (different from public notes)
if (!column_exists($conn, 'tickets', 'private_notes')) {
    $sql = "ALTER TABLE tickets ADD COLUMN private_notes TEXT DEFAULT NULL COMMENT 'Private technician notes (not shown to students)' AFTER notes";
    if ($conn->query($sql)) {
        $changes[] = "Added column 'private_notes' to tickets table";
    } else {
        $errors[] = "Failed to add 'private_notes': " . $conn->error;
    }
} else {
    $changes[] = "Column 'private_notes' already exists - skipped";
}

// 3. Add picked_up field
if (!column_exists($conn, 'tickets', 'picked_up')) {
    $sql = "ALTER TABLE tickets ADD COLUMN picked_up ENUM('Yes', 'No') DEFAULT 'No' COMMENT 'Whether device has been picked up' AFTER status";
    if ($conn->query($sql)) {
        $changes[] = "Added column 'picked_up' to tickets table";
    } else {
        $errors[] = "Failed to add 'picked_up': " . $conn->error;
    }
} else {
    $changes[] = "Column 'picked_up' already exists - skipped";
}

// 4. Add pickup_date field
if (!column_exists($conn, 'tickets', 'pickup_date')) {
    $sql = "ALTER TABLE tickets ADD COLUMN pickup_date DATE DEFAULT NULL COMMENT 'Date device was picked up' AFTER picked_up";
    if ($conn->query($sql)) {
        $changes[] = "Added column 'pickup_date' to tickets table";
    } else {
        $errors[] = "Failed to add 'pickup_date': " . $conn->error;
    }
} else {
    $changes[] = "Column 'pickup_date' already exists - skipped";
}

// Display results
echo "<h2>Results:</h2>";

if (!empty($changes)) {
    echo "<div class='success'><strong>Changes:</strong><ul>";
    foreach ($changes as $change) {
        echo "<li>$change</li>";
    }
    echo "</ul></div>";
}

if (!empty($errors)) {
    echo "<div class='error'><strong>Errors:</strong><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul></div>";
}

if (empty($errors)) {
    echo "<div class='info'><strong>Database update completed successfully!</strong></div>";
    echo "<div class='warning'><strong>Important Notes:</strong>
        <ul>
            <li><code>chromebook_sn</code> will be displayed in status.php for students</li>
            <li><code>private_notes</code> will NOT be displayed in status.php (tech-only)</li>
            <li><code>priority</code> will be removed from status.php display but kept in database</li>
            <li><code>picked_up</code> defaults to 'No', will be shown in status.php</li>
            <li><code>pickup_date</code> will be shown in status.php only when picked_up = 'Yes'</li>
        </ul>
    </div>";
} else {
    echo "<div class='error'><strong>Some errors occurred. Please review and fix them.</strong></div>";
}

echo "<p><a href='../tech/manage_tickets.php' style='display: inline-block; padding: 10px 20px; background: #800000; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px;'>Go to Ticket Management</a></p>";
echo "</body></html>";

$conn->close();
?>

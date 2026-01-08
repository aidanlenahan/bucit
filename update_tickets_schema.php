<?php
// update_tickets_schema.php - Update tickets table with new columns

$servername = "localhost";
$username = "bucit";
$password = "m0Mih-Vdm!].Km8F";
$dbname = "bucit";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Updating Tickets Table Schema</h1>";

$errors = [];
$success = [];

// Check if school_email column exists
$check = $conn->query("SHOW COLUMNS FROM tickets LIKE 'school_email'");
if ($check && $check->num_rows == 0) {
    // Add school_email column
    if ($conn->query("ALTER TABLE tickets ADD COLUMN school_email VARCHAR(255) AFTER class_of")) {
        $success[] = "✓ Added 'school_email' column";
    } else {
        $errors[] = "✗ Error adding 'school_email': " . $conn->error;
    }
} else {
    $success[] = "~ 'school_email' column already exists";
}

// Check if additional_info column exists
$check = $conn->query("SHOW COLUMNS FROM tickets LIKE 'additional_info'");
if ($check && $check->num_rows == 0) {
    // Add additional_info column
    if ($conn->query("ALTER TABLE tickets ADD COLUMN additional_info VARCHAR(120) AFTER school_email")) {
        $success[] = "✓ Added 'additional_info' column";
    } else {
        $errors[] = "✗ Error adding 'additional_info': " . $conn->error;
    }
} else {
    $success[] = "~ 'additional_info' column already exists";
}

// Check if phone column exists
$check = $conn->query("SHOW COLUMNS FROM tickets LIKE 'phone'");
if ($check && $check->num_rows > 0) {
    // Remove phone column
    if ($conn->query("ALTER TABLE tickets DROP COLUMN phone")) {
        $success[] = "✓ Removed 'phone' column";
    } else {
        $errors[] = "✗ Error removing 'phone': " . $conn->error;
    }
} else {
    $success[] = "~ 'phone' column already removed";
}

$conn->close();

// Display results
echo "<h2>Update Results</h2>";

if (!empty($success)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Success:</h3><ul>";
    foreach ($success as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul></div>";
}

if (!empty($errors)) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Errors:</h3><ul>";
    foreach ($errors as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul></div>";
} else {
    echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ Schema Update Complete!</h3>";
    echo "<p>The tickets table has been updated successfully.</p>";
    echo "<p><a href='form.html'>Go to Intake Form</a> | <a href='manage_tickets.php'>Go to Ticket Management</a></p>";
    echo "</div>";
}

echo "<hr><p><small>You can delete this file after running it once.</small></p>";
?>

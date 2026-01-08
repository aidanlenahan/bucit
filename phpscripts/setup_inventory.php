<?php
// setup_inventory.php - One-click database setup for inventory feature
// Run this file once to create the required database tables

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

echo "<h1>Inventory Feature Setup</h1>";
echo "<p>Setting up database tables...</p>";

$errors = [];
$success = [];

// Create inventory table
$sql_inventory = "CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_name VARCHAR(255) NOT NULL,
    part_number VARCHAR(100),
    quantity INT NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_inventory) === TRUE) {
    $success[] = "✓ Table 'inventory' created successfully (or already exists)";
} else {
    $errors[] = "✗ Error creating table 'inventory': " . $conn->error;
}

// Create ticket_parts table
$sql_ticket_parts = "CREATE TABLE IF NOT EXISTS ticket_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    part_id INT NOT NULL,
    quantity_used INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    added_by VARCHAR(100),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES inventory(id) ON DELETE CASCADE
)";

if ($conn->query($sql_ticket_parts) === TRUE) {
    $success[] = "✓ Table 'ticket_parts' created successfully (or already exists)";
} else {
    $errors[] = "✗ Error creating table 'ticket_parts': " . $conn->error;
}

// Create indexes (suppress errors if they already exist)
$sql_index_1 = "CREATE INDEX idx_ticket_parts_ticket ON ticket_parts(ticket_id)";
$sql_index_2 = "CREATE INDEX idx_ticket_parts_part ON ticket_parts(part_id)";

// Try to create first index
$index_result = @$conn->query($sql_index_1);
if ($index_result === TRUE) {
    $success[] = "✓ Index on ticket_id created successfully";
} else {
    $success[] = "~ Index on ticket_id already exists (this is fine)";
}

// Try to create second index
$index_result = @$conn->query($sql_index_2);
if ($index_result === TRUE) {
    $success[] = "✓ Index on part_id created successfully";
} else {
    $success[] = "~ Index on part_id already exists (this is fine)";
}

$conn->close();

// Display results
echo "<h2>Setup Results</h2>";

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
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>The inventory feature is now ready to use.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li><a href='inventory.php'>Go to Inventory Management</a> to add parts</li>";
    echo "<li><a href='manage_tickets.php'>Go to Ticket Management</a> to view tickets</li>";
    echo "<li>Open any ticket and scroll down to attach parts</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Once setup is complete, you can delete this file (setup_inventory.php) for security.</small></p>";
?>

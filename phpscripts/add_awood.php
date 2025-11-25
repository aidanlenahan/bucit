<?php
// add_awood.php - Add Alex Wood technician account

$servername = "localhost";
$username = "bucit";
$password = "m0Mih-Vdm!].Km8F";
$dbname = "bucit";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if awood already exists
$check = $conn->prepare("SELECT id FROM technicians WHERE username = ? LIMIT 1");
$check->bind_param('s', $user);
$user = 'awood';
$check->execute();
$res = $check->get_result();

if ($res && $res->num_rows > 0) {
    echo "<p style='color:orange;'>User 'awood' already exists. Skipping creation.</p>";
    $check->close();
} else {
    $check->close();
    
    // Insert Alex Wood
    $insert = $conn->prepare("INSERT INTO technicians (username, display_name, password_hash, active, must_change_password) VALUES (?, ?, ?, 1, 1)");
    if ($insert) {
        $user = 'awood';
        $display = 'Alex Wood';
        $pw_hash = password_hash('changeme', PASSWORD_DEFAULT);
        
        $insert->bind_param('sss', $user, $display, $pw_hash);
        
        if ($insert->execute()) {
            echo "<p style='color:green;'>✓ Added technician 'awood' (Alex Wood) with default password 'changeme' and must_change_password=1</p>";
        } else {
            echo "<p style='color:red;'>✗ Error adding technician: " . $insert->error . "</p>";
        }
        $insert->close();
    } else {
        echo "<p style='color:red;'>✗ Could not prepare insert: " . $conn->error . "</p>";
    }
}

$conn->close();

echo "<br><p><a href='login.php'>Go to Login</a> | <a href='manage_tickets.php'>Go to Tickets</a></p>";
?>

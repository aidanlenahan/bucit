<?php
// update_database.php - Add missing columns to tickets table

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

echo "<h2>Updating Database Schema</h2>";

// Check if status column exists
$check_status = "SHOW COLUMNS FROM tickets LIKE 'status'";
$result = $conn->query($check_status);

if ($result->num_rows == 0) {
    // Add status column
    $add_status = "ALTER TABLE tickets ADD COLUMN status VARCHAR(50) DEFAULT 'Open' AFTER custom_detail";
    if ($conn->query($add_status)) {
        echo "<p style='color: green;'>✓ Added 'status' column successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'status' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ 'status' column already exists</p>";
}

// Check if notes column exists
$check_notes = "SHOW COLUMNS FROM tickets LIKE 'notes'";
$result = $conn->query($check_notes);

if ($result->num_rows == 0) {
    // Add notes column
    $add_notes = "ALTER TABLE tickets ADD COLUMN notes TEXT AFTER status";
    if ($conn->query($add_notes)) {
        echo "<p style='color: green;'>✓ Added 'notes' column successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'notes' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ 'notes' column already exists</p>";
}

// Check if created_at column exists
$check_created = "SHOW COLUMNS FROM tickets LIKE 'created_at'";
$result = $conn->query($check_created);

if ($result->num_rows == 0) {
    // Add created_at column
    $add_created = "ALTER TABLE tickets ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER notes";
    if ($conn->query($add_created)) {
        echo "<p style='color: green;'>✓ Added 'created_at' column successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'created_at' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ 'created_at' column already exists</p>";
}

// Check if priority column exists
$check_priority = "SHOW COLUMNS FROM tickets LIKE 'priority'";
$result = $conn->query($check_priority);

if ($result->num_rows == 0) {
    // Add priority column (1-4 with default 3)
    $add_priority = "ALTER TABLE tickets ADD COLUMN priority TINYINT(1) DEFAULT 3 AFTER custom_detail";
    if ($conn->query($add_priority)) {
        echo "<p style='color: green;'>✓ Added 'priority' column successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'priority' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ 'priority' column already exists</p>";
}

// Check if restarted column exists
$check_restarted = "SHOW COLUMNS FROM tickets LIKE 'restarted'";
$result = $conn->query($check_restarted);

if ($result->num_rows == 0) {
    // Add restarted column
    $add_restarted = "ALTER TABLE tickets ADD COLUMN restarted TINYINT(1) DEFAULT 0";
    if ($conn->query($add_restarted)) {
        echo "<p style='color: green;'>✓ Added 'restarted' column successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'restarted' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ 'restarted' column already exists</p>";
}

// Check if tech column exists
$check_tech = "SHOW COLUMNS FROM tickets LIKE 'tech'";
$result = $conn->query($check_tech);

if ($result->num_rows == 0) {
    // Add tech column
    $add_tech = "ALTER TABLE tickets ADD COLUMN tech VARCHAR(255) DEFAULT NULL AFTER restarted";
    if ($conn->query($add_tech)) {
        echo "<p style='color: green;'>✓ Added 'tech' column successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'tech' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ 'tech' column already exists</p>";
}

// Create technicians table if missing (store display_name and username)
$check_tech_table = "SHOW TABLES LIKE 'technicians'";
$res = $conn->query($check_tech_table);
if (!$res || $res->num_rows == 0) {
    $create_table = "CREATE TABLE technicians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(150) NOT NULL UNIQUE,
        display_name VARCHAR(255) DEFAULT NULL,
        password_hash VARCHAR(255) NOT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        must_change_password TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($create_table)) {
        echo "<p style='color: green;'>✓ Created 'technicians' table</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating 'technicians' table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ 'technicians' table already exists</p>";
}

// Ensure technicians table has expected columns (backfill if older schema exists)
$check_col = function($col) use ($conn) {
    $q = "SHOW COLUMNS FROM technicians LIKE '" . $conn->real_escape_string($col) . "'";
    $r = $conn->query($q);
    return ($r && $r->num_rows > 0);
};

if ($check_col('display_name') === false) {
    $alter = "ALTER TABLE technicians ADD COLUMN display_name VARCHAR(255) DEFAULT NULL AFTER username";
    if ($conn->query($alter)) {
        echo "<p style='color: green;'>✓ Added 'display_name' column to technicians</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'display_name' column: " . $conn->error . "</p>";
    }
}

if ($check_col('must_change_password') === false) {
    $alter = "ALTER TABLE technicians ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 1 AFTER active";
    if ($conn->query($alter)) {
        echo "<p style='color: green;'>✓ Added 'must_change_password' column to technicians</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding 'must_change_password' column: " . $conn->error . "</p>";
    }
}

// Create ticket_points table for Bucit Ranked leaderboard
$check_points_table = "SHOW TABLES LIKE 'ticket_points'";
$points_res = $conn->query($check_points_table);
if (!$points_res || $points_res->num_rows == 0) {
    $tickets_engine = null;
    $engine_res = $conn->query("SHOW TABLE STATUS LIKE 'tickets'");
    if ($engine_res && $engine_res->num_rows > 0) {
        $engine_row = $engine_res->fetch_assoc();
        $tickets_engine = $engine_row['Engine'] ?? null;
    }
    $engine_res && $engine_res->close();

    $can_fk = (strcasecmp((string)$tickets_engine, 'InnoDB') === 0);

    $create_points = "CREATE TABLE ticket_points (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL UNIQUE,
        tech VARCHAR(150) NOT NULL,
        problem_category VARCHAR(50) DEFAULT NULL,
        base_points INT NOT NULL DEFAULT 0,
        awarded_points INT NOT NULL DEFAULT 0,
        manual_override TINYINT(1) NOT NULL DEFAULT 0,
        notes TEXT DEFAULT NULL,
        updated_by VARCHAR(150) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    if ($can_fk) {
        $create_points .= ",
        CONSTRAINT fk_ticket_points_ticket FOREIGN KEY (ticket_id)
            REFERENCES tickets(id) ON DELETE CASCADE";
    }

    $create_points .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($create_points)) {
        echo "<p style='color: green;'>✓ Created 'ticket_points' table" . ($can_fk ? '' : " (without FK; 'tickets' table is not InnoDB)") . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating 'ticket_points' table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ 'ticket_points' table already exists</p>";
}

// helper: generate username: first initial + last name, lowercase and alphanumeric
$normalize_username = function($full){
    $parts = preg_split('/\s+/', trim($full));
    if (count($parts) === 0) return '';
    $first = $parts[0];
    $last = $parts[count($parts)-1];
    $u = strtolower(substr($first,0,1) . preg_replace('/[^a-z0-9]/i','', $last));
    return $u;
};

// Seed technicians from Names.txt if table is empty
$count_check = $conn->query("SELECT COUNT(*) as c FROM technicians");
$count_row = $count_check ? $count_check->fetch_assoc() : null;
$tech_count = $count_row ? intval($count_row['c']) : 0;
if ($tech_count === 0) {
    $names_file = __DIR__ . DIRECTORY_SEPARATOR . 'Names.txt';
    if (file_exists($names_file)) {
        $lines = file($names_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false && count($lines) > 0) {
            $insert_stmt = $conn->prepare("INSERT INTO technicians (username, display_name, password_hash, active, must_change_password) VALUES (?, ?, ?, 1, 1)");
            if ($insert_stmt) {
                foreach ($lines as $ln) {
                    $display = trim($ln);
                    if ($display === '') continue;
                    // compute formatted username: first initial + last name, lowercase
                    $formatted = $normalize_username($display);
                    if ($formatted === '') continue;
                    // ensure uniqueness: append number if needed
                    $base = $formatted;
                    $i = 1;
                    while (true) {
                        $chk = $conn->prepare("SELECT id FROM technicians WHERE username = ?");
                        $chk->bind_param('s', $formatted);
                        $chk->execute();
                        $rr = $chk->get_result();
                        $exists = ($rr && $rr->num_rows > 0);
                        $chk->close();
                        if (!$exists) break;
                        $i++;
                        $formatted = $base . $i;
                    }
                    $pw_hash = password_hash('changeme', PASSWORD_DEFAULT);
                    $insert_stmt->bind_param('sss', $formatted, $display, $pw_hash);
                    $insert_stmt->execute();
                }
                $insert_stmt->close();
                echo "<p style='color: green;'>✓ Seeded technicians from Names.txt (default password 'changeme')</p>";
            } else {
                echo "<p style='color: red;'>✗ Could not prepare insert for technicians: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ Names.txt empty or not readable; no technicians seeded</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Names.txt not found; no technicians seeded</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ technicians table already has $tech_count entries; skipped seeding</p>";
}

// Show final table structure
echo "<h3>Current Table Structure:</h3>";
$show_structure = "DESCRIBE tickets";
$result = $conn->query($show_structure);

if ($result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Type'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Null'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Key'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Ensure all technicians have the default initial password 'changeme' and must change it on first login
$default_hash = password_hash('changeme', PASSWORD_DEFAULT);
$upd = $conn->prepare("UPDATE technicians SET password_hash = ?, must_change_password = 1");
if ($upd) {
    $upd->bind_param('s', $default_hash);
    if ($upd->execute()) {
        echo "<p style='color: green;'>✓ Set default password for all technicians and required change on first login</p>";
    } else {
        echo "<p style='color: red;'>✗ Could not set default password for technicians: " . $conn->error . "</p>";
    }
    $upd->close();
} else {
    echo "<p style='color: red;'>✗ Could not prepare update for technicians: " . $conn->error . "</p>";
}

echo "<br><p><a href='manage_tickets.php'>Go to Ticket Management</a></p>";

$conn->close();
?>
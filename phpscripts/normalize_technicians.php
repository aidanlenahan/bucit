<?php
// normalize_technicians.php
// Convert technician usernames to first-initial + last name (lowercase, no hyphens),
// backfill missing display_name from existing username, ensure uniqueness,
// and update `tickets.tech` references.

// WARNING: Run this once from a safe environment (backup your DB first).

require_once __DIR__ . '/../includes/db_config.php';

$conn = getDbConnection();

echo "<h2>Normalizing technician usernames and filling display names</h2>";

// Fetch all technicians
$res = $conn->query("SELECT id, username, display_name FROM technicians ORDER BY id ASC");
if (!$res) {
    die("Could not fetch technicians: " . $conn->error);
}

$mapping = [];

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['id'];
    $old_username = trim($row['username']);
    $display = $row['display_name'];

    // If display_name empty, try to derive from the existing username
    if (empty($display)) {
        // replace dots/underscores with spaces, collapse multiple spaces
        $d = preg_replace('/[._]+/', ' ', $old_username);
        $d = preg_replace('/\s+/', ' ', trim($d));
        $display = ucwords(strtolower($d));
    }

    // Build base username: first initial + last name, strip non-alphanum
    $parts = preg_split('/\s+/', trim($display));
    if (count($parts) === 0) {
        // fallback: use old username cleaned
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $old_username));
        if ($base === '') $base = 'tech' . $id;
    } else {
        $first = $parts[0];
        $last = $parts[count($parts)-1];
        $base = strtolower(substr($first,0,1) . preg_replace('/[^a-z0-9]/i','', $last));
        if ($base === '') $base = 'tech' . $id;
    }

    // Ensure uniqueness by appending numbers if needed
    $new_username = $base;
    $suffix = 1;
    while (true) {
        $chk = $conn->prepare("SELECT id FROM technicians WHERE username = ? LIMIT 1");
        $chk->bind_param('s', $new_username);
        $chk->execute();
        $rr = $chk->get_result();
        $exists = ($rr && $rr->num_rows > 0 && $rr->fetch_assoc()['id'] != $id);
        $chk->close();
        if (!$exists) break;
        $suffix++;
        $new_username = $base . $suffix;
    }

    // If nothing changed except display, still update display_name
    $need_update = false;
    if ($old_username !== $new_username) $need_update = true;
    if ($row['display_name'] !== $display) $need_update = true;

    if ($need_update) {
        $upd = $conn->prepare("UPDATE technicians SET username = ?, display_name = ? WHERE id = ?");
        if (!$upd) {
            echo "<p style='color:red;'>Could not prepare UPDATE for id={$id}: " . htmlspecialchars($conn->error) . "</p>";
            continue;
        }
        $upd->bind_param('ssi', $new_username, $display, $id);
        if ($upd->execute()) {
            echo "<p style='color:green;'>Updated id={$id}: '{$old_username}' → '{$new_username}', display='{$display}'</p>";
            $mapping[$old_username] = $new_username;
        } else {
            echo "<p style='color:red;'>Failed updating id={$id}: " . htmlspecialchars($upd->error) . "</p>";
        }
        $upd->close();
    } else {
        echo "<p>Skipped id={$id}: username already '{$old_username}', display='{$display}'</p>";
    }
}

// Update tickets.tech references
if (!empty($mapping)) {
    echo "<h3>Updating tickets.tech references</h3>";
    foreach ($mapping as $old => $new) {
        $stmt = $conn->prepare("UPDATE tickets SET tech = ? WHERE tech = ?");
        if (!$stmt) {
            echo "<p style='color:red;'>Could not prepare tickets UPDATE for '{$old}': " . htmlspecialchars($conn->error) . "</p>";
            continue;
        }
        $stmt->bind_param('ss', $new, $old);
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            echo "<p>Replaced tech '{$old}' → '{$new}' in {$affected} ticket(s)</p>";
        } else {
            echo "<p style='color:red;'>Failed updating tickets for '{$old}': " . htmlspecialchars($stmt->error) . "</p>";
        }
        $stmt->close();
    }
} else {
    echo "<p>No username changes detected; no ticket updates required.</p>";
}

echo "<p>Done. Please verify technician accounts and ticket assignments.</p>";

$conn->close();

?>

<?php
// inventory.php - Manage replacement parts inventory
require_once __DIR__ . '/includes/db_config.php';

// Connect to MySQL
$conn = getDbConnection();

// Start session and require technician login
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current_tech = $_SESSION['tech_user'] ?? null;
if (empty($current_tech)) {
    header('Location: tech_login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $part_id = intval($_POST['part_id'] ?? 0);
                $part_name = trim($_POST['part_name'] ?? '');
                $part_number = trim($_POST['part_number'] ?? '');
                $quantity = intval($_POST['quantity'] ?? 0);
                $notes = trim($_POST['notes'] ?? '');
                
                if ($part_id > 0 && !empty($part_name)) {
                    $stmt = $conn->prepare("UPDATE inventory SET part_name = ?, part_number = ?, quantity = ?, notes = ? WHERE id = ?");
                    $stmt->bind_param("ssisi", $part_name, $part_number, $quantity, $notes, $part_id);
                    if ($stmt->execute()) {
                        $success_message = "Part updated successfully!";
                    } else {
                        $error_message = "Error updating part: " . $conn->error;
                    }
                    $stmt->close();
                }
                break;
                
            case 'adjust_quantity':
                $part_id = intval($_POST['part_id'] ?? 0);
                $adjustment = intval($_POST['adjustment'] ?? 0);
                
                if ($part_id > 0) {
                    $stmt = $conn->prepare("UPDATE inventory SET quantity = GREATEST(0, quantity + ?) WHERE id = ?");
                    $stmt->bind_param("ii", $adjustment, $part_id);
                    if ($stmt->execute()) {
                        $success_message = "Quantity adjusted successfully!";
                    } else {
                        $error_message = "Error adjusting quantity: " . $conn->error;
                    }
                    $stmt->close();
                }
                break;
                
            case 'delete':
                $part_id = intval($_POST['part_id'] ?? 0);
                
                if ($part_id > 0) {
                    // Check if part is used in any tickets
                    $check = $conn->prepare("SELECT COUNT(*) as count FROM ticket_parts WHERE part_id = ?");
                    $check->bind_param("i", $part_id);
                    $check->execute();
                    $result = $check->get_result();
                    $row = $result->fetch_assoc();
                    $check->close();
                    
                    if ($row['count'] > 0) {
                        $error_message = "Cannot delete part that has been used in tickets. Consider setting quantity to 0 instead.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
                        $stmt->bind_param("i", $part_id);
                        if ($stmt->execute()) {
                            $success_message = "Part deleted successfully!";
                        } else {
                            $error_message = "Error deleting part: " . $conn->error;
                        }
                        $stmt->close();
                    }
                }
                break;
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Get all inventory items
$inventory_query = $conn->query("SELECT * FROM inventory ORDER BY part_name ASC");
$inventory_items = [];
if ($inventory_query) {
    while ($row = $inventory_query->fetch_assoc()) {
        $inventory_items[] = $row;
    }
}

// Get edit mode part if specified
$edit_part = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_part = $result->fetch_assoc();
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - BucIT Support</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --brand: #800000;
            --brand-dark: #5a0000;
            --card-bg: rgba(255,255,255,0.98);
        }

        body {
            background: var(--brand);
            color: #fff;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #222;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--brand);
            margin: 0;
        }

        .card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #222;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: var(--brand);
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .btn {
            background-color: var(--brand);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background-color: var(--brand-dark);
        }

        .btn-secondary {
            background-color: #666;
        }

        .btn-secondary:hover {
            background-color: #444;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            margin: 0 2px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--brand);
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .quantity-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }

        .quantity-good {
            background-color: #d4edda;
            color: #155724;
        }

        .quantity-low {
            background-color: #fff3cd;
            color: #856404;
        }

        .quantity-out {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            position: relative;
            padding-right: 45px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .alert-close {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: opacity 0.2s, background 0.2s;
        }

        .alert-close:hover {
            opacity: 1;
            background: rgba(0,0,0,0.1);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .quick-adjust {
            display: inline-flex;
            gap: 5px;
            align-items: center;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-success:hover {
            background-color: #1e7e34;
        }

        .back-link {
            color: var(--brand);
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }

        .modal.active {
            display: block;
        }

        .modal-content {
            background-color: var(--card-bg);
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            position: relative;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px;
            background-color: var(--brand);
            color: white;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            color: white;
        }

        .modal-close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            width: 30px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .modal-close:hover {
            background: rgba(255,255,255,0.2);
        }

        .modal-body {
            padding: 20px;
            color: #222;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Inventory Management</h1>
                <p style="margin: 5px 0 0 0;">Manage replacement parts and supplies</p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="new_inventory.php" class="btn btn-success" title="Add new part" style="padding:8px 12px; font-size:18px; line-height:1;">+</a>
                <a href="manage_tickets.php" class="back-link">← Back to Tickets</a>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <span><?php echo htmlspecialchars($success_message); ?></span>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <span><?php echo htmlspecialchars($error_message); ?></span>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Inventory List -->
        <div class="card">
            <h2>Current Inventory</h2>
            <?php if (empty($inventory_items)): ?>
                <p>No parts in inventory yet. <a href="new_inventory.php">Add your first part</a>!</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Part Name</th>
                                <th>Part Number</th>
                                <th>Quantity</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory_items as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['part_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['part_number'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $qty = intval($item['quantity']);
                                        $class = 'quantity-good';
                                        if ($qty == 0) $class = 'quantity-out';
                                        elseif ($qty <= 5) $class = 'quantity-low';
                                        ?>
                                        <span class="quantity-badge <?php echo $class; ?>"><?php echo $qty; ?></span>
                                    </td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($item['notes'] ?? '-'); ?>
                                    </td>
                                    <td class="action-buttons">
                                        <div class="quick-adjust">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="adjust_quantity">
                                                <input type="hidden" name="part_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="adjustment" value="1">
                                                <button type="submit" class="btn btn-success btn-small">+1</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="adjust_quantity">
                                                <input type="hidden" name="part_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="adjustment" value="-1">
                                                <button type="submit" class="btn btn-secondary btn-small">-1</button>
                                            </form>
                                            <button type="button" class="btn btn-small" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">Edit</button>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this part?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="part_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Part Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Part</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" action="inventory.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="edit_part_id" name="part_id">
                    
                    <div class="form-group">
                        <label for="edit_part_name">Part Name *</label>
                        <input type="text" id="edit_part_name" name="part_name" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_part_number">Part Number / SKU</label>
                        <input type="text" id="edit_part_number" name="part_number">
                    </div>

                    <div class="form-group">
                        <label for="edit_quantity">Quantity</label>
                        <input type="number" id="edit_quantity" name="quantity" min="0">
                    </div>

                    <div class="form-group">
                        <label for="edit_notes">Notes</label>
                        <textarea id="edit_notes" name="notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn">Update Part</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });

        function openEditModal(part) {
            document.getElementById('edit_part_id').value = part.id;
            document.getElementById('edit_part_name').value = part.part_name;
            document.getElementById('edit_part_number').value = part.part_number || '';
            document.getElementById('edit_quantity').value = part.quantity;
            document.getElementById('edit_notes').value = part.notes || '';
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });

        <?php if ($edit_part): ?>
        // Auto-open modal if edit parameter is present
        openEditModal(<?php echo json_encode($edit_part); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
<?php
$conn->close();
?>

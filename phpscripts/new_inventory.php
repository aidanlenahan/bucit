<?php
// new_inventory.php - Add new parts to inventory
require_once __DIR__ . '/../includes/db_config.php';

// Connection is already established in db_config.php as $conn

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $part_name = trim($_POST['part_name'] ?? '');
    $part_number = trim($_POST['part_number'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($part_name)) {
        $error_message = "Part name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory (part_name, part_number, quantity, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $part_name, $part_number, $quantity, $notes);
        if ($stmt->execute()) {
            $success_message = "Part added successfully!";
            // Redirect to inventory page after 2 seconds
            header("refresh:2;url=inventory.php");
        } else {
            $error_message = "Error adding part: " . $conn->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Part - Inventory</title>
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
            max-width: 800px;
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
            background-color: var(--brand) !important;
            color: white !important;
            padding: 0 20px !important;
            border: none !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            font-size: 14px !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
            height: 40px !important;
            line-height: 40px !important;
            vertical-align: middle !important;
            margin-right: 10px !important;
            font-family: Arial, sans-serif !important;
        }

        button.btn {
            font-weight: normal !important;
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

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
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

        .back-link {
            color: var(--brand);
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Add New Part</h1>
                <p style="margin: 5px 0 0 0;">Add a new part to inventory</p>
            </div>
            <div>
                <a href="inventory.php" class="back-link">← Back to Inventory</a>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
                <br><small>Redirecting to inventory...</small>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Part Details</h2>
            <form method="POST" action="new_inventory.php">
                <div class="form-group">
                    <label for="part_name">Part Name *</label>
                    <input type="text" id="part_name" name="part_name" required 
                           placeholder="e.g., LCD Screen 15.6 inch">
                </div>

                <div class="form-group">
                    <label for="part_number">Part Number / SKU</label>
                    <input type="text" id="part_number" name="part_number" 
                           placeholder="e.g., LCD-156-001">
                </div>

                <div class="form-group">
                    <label for="quantity">Initial Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="0" value="0">
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" placeholder="Any additional information about this part..."></textarea>
                </div>

                <button type="submit" class="btn">Add Part</button>
                <a href="inventory.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>

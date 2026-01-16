<?php
/**
 * new_inventory.php - Add new replacement parts to inventory
 */
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $part_name = trim($_POST['part_name'] ?? '');
    $part_number = trim($_POST['part_number'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate required fields
    if (empty($part_name)) {
        $error_message = "Part name is required.";
    } elseif ($quantity < 0) {
        $error_message = "Quantity cannot be negative.";
    } else {
        // Insert new part into inventory
        $stmt = $conn->prepare("INSERT INTO inventory (part_name, part_number, quantity, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $part_name, $part_number, $quantity, $notes);
        
        if ($stmt->execute()) {
            $new_part_id = $conn->insert_id;
            $stmt->close();
            
            // Redirect to inventory page with success message
            header("Location: inventory.php?success=" . urlencode("Part added successfully! (ID: $new_part_id)"));
            exit();
        } else {
            $error_message = "Error adding part: " . $conn->error;
            $stmt->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Part - BucIT Support</title>
    <link rel="icon" type="image/svg+xml" href="img/buc.svg">
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
        }

        .header h1 {
            color: var(--brand);
            margin: 0;
        }

        .back-link {
            display: inline-block;
            color: var(--brand);
            text-decoration: none;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #222;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: var(--brand);
            margin-top: 0;
            margin-bottom: 20px;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-group label .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(128, 0, 0, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            align-items: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            height: 44px;
            line-height: 1;
            box-sizing: border-box;
            vertical-align: top;
        }

        .btn-primary {
            background-color: var(--brand);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--brand-dark);
        }

        .btn-secondary {
            background-color: #666;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #444;
        }

        .form-hint {
            background: #f8f9fa;
            border-left: 4px solid var(--brand);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .form-hint h4 {
            margin-top: 0;
            color: var(--brand);
        }

        .form-hint ul {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .form-hint li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="inventory.php" class="back-link">← Back to Inventory</a>
            <h1>Add New Replacement Part</h1>
        </div>

        <div class="card">
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="form-hint">
                <h4>📦 Adding a New Part</h4>
                <ul>
                    <li><strong>Part Name:</strong> Descriptive name (e.g., "MacBook Pro 13\" Screen", "USB-C Charger 65W")</li>
                    <li><strong>Part Number:</strong> Manufacturer or internal SKU for tracking</li>
                    <li><strong>Quantity:</strong> Current stock level</li>
                    <li><strong>Notes:</strong> Compatible models, installation notes, or ordering details</li>
                </ul>
            </div>

            <form method="POST" action="new_inventory.php">
                <div class="form-group">
                    <label for="part_name">Part Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="part_name" 
                        name="part_name" 
                        required 
                        placeholder="e.g., MacBook Pro 13&quot; Screen Assembly"
                        value="<?php echo htmlspecialchars($_POST['part_name'] ?? ''); ?>"
                        autofocus
                    >
                    <small>Enter a descriptive name for the replacement part</small>
                </div>

                <div class="form-group">
                    <label for="part_number">Part Number / SKU</label>
                    <input 
                        type="text" 
                        id="part_number" 
                        name="part_number" 
                        placeholder="e.g., A1502-LCD-2015 or SKU12345"
                        value="<?php echo htmlspecialchars($_POST['part_number'] ?? ''); ?>"
                    >
                    <small>Manufacturer part number or internal SKU (optional)</small>
                </div>

                <div class="form-group">
                    <label for="quantity">Initial Quantity <span class="required">*</span></label>
                    <input 
                        type="number" 
                        id="quantity" 
                        name="quantity" 
                        min="0" 
                        required 
                        value="<?php echo htmlspecialchars($_POST['quantity'] ?? '0'); ?>"
                    >
                    <small>How many units are currently in stock?</small>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        placeholder="Compatible models, installation instructions, supplier info, etc."
                    ><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    <small>Additional information about this part (optional)</small>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Add Part to Inventory</button>
                    <a href="inventory.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>

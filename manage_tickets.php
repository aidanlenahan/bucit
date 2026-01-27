<?php
// manage_tickets.php - Dashboard for viewing and managing support tickets
require_once __DIR__ . '/includes/db_config.php';

// Connection is already established in db_config.php as $conn

// Start session and require technician login
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current_tech = $_SESSION['tech_user'] ?? null;
if (empty($current_tech)) {
    header('Location: login.php');
    exit();
}

// Get sorting parameters
$sort_by = $_GET['sort'] ?? 'date_reported';
$order = $_GET['order'] ?? 'DESC';

// Check whether priority column exists so we do not throw SQL errors; add to valid columns if present.
$has_priority = false;
$priorityCheck = $conn->query("SHOW COLUMNS FROM tickets LIKE 'priority'");
if ($priorityCheck && $priorityCheck->num_rows > 0) {
    $has_priority = true;
}
// Check whether tech column exists so UI can adapt
$has_tech = false;
$techCheck = $conn->query("SHOW COLUMNS FROM tickets LIKE 'tech'");
if ($techCheck && $techCheck->num_rows > 0) {
    $has_tech = true;
}

// Validate sorting parameters
    $valid_columns = ['id', 'first_name', 'last_name', 'school_id', 'date_reported', 'problem_category', 'status'];
$valid_orders = ['ASC', 'DESC'];

if ($has_priority) {
    $valid_columns[] = 'priority';
}
if ($has_tech) {
    $valid_columns[] = 'tech';
}

if (!in_array($sort_by, $valid_columns)) {
    $sort_by = 'date_reported';
}
if (!in_array($order, $valid_orders)) {
    $order = 'DESC';
}

// Toggle order for next click
$next_order = ($order == 'ASC') ? 'DESC' : 'ASC';

// Filters and pagination
$search = trim($_GET['q'] ?? '');
$priority_filter = $_GET['priority'] ?? '';
$per_page = $_GET['per_page'] ?? '25';
$page = max(1, intval($_GET['page'] ?? 1));

// Validate per_page
$allowed_per_page = ['10','15','25','50','100','all'];
if (!in_array((string)$per_page, $allowed_per_page)) {
    $per_page = '25';
}

// Build WHERE clause from filters
$where_clauses = [];
if ($priority_filter !== '' && is_numeric($priority_filter)) {
    if ($has_priority) {
        $where_clauses[] = "priority = " . intval($priority_filter);
    }
}
if ($search !== '') {
    $escaped = $conn->real_escape_string($search);
    $where_clauses[] = "(id = '" . $escaped . "' OR first_name LIKE '%" . $escaped . "%' OR last_name LIKE '%" . $escaped . "%' OR school_id LIKE '%" . $escaped . "%' OR problem_category LIKE '%" . $escaped . "%' OR problem_detail LIKE '%" . $escaped . "%' OR custom_detail LIKE '%" . $escaped . "%' OR status LIKE '%" . $escaped . "%')";
}
$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Count total for pagination
$count_sql = "SELECT COUNT(*) as total_count FROM tickets $where_sql";
$count_result = $conn->query($count_sql);
$total_count = $count_result ? intval($count_result->fetch_assoc()['total_count']) : 0;

// Prepare LIMIT clause if not 'all'
$limit_sql = '';
if ($per_page !== 'all') {
    $per_page_int = intval($per_page);
    $offset = ($page - 1) * $per_page_int;
    $limit_sql = "LIMIT $offset, $per_page_int";
}

// Get current page tickets with sorting (include priority in the SELECT only when available)
$select_columns = "id, first_name, last_name, school_id, class_of, school_email, additional_info, date_reported, problem_category, problem_detail, custom_detail, status, created_at";
if ($has_priority) {
    $select_columns .= ", priority";
}
if ($has_tech) {
    $select_columns .= ", tech";
}

$order_by_sql = "$sort_by $order";
// If sorting by priority, ensure closed tickets are always last so they don't get in the way
if ($has_priority && $sort_by === 'priority') {
    // Order first by whether status is Closed (non-closed first), then by priority in the requested direction
    $order_by_sql = "(status = 'Closed') ASC, priority $order";
}

$sql = "SELECT $select_columns FROM tickets $where_sql ORDER BY $order_by_sql $limit_sql";
$result = $conn->query($sql);

// Check for success message
$success = $_GET['success'] ?? null;
$ticket_id = $_GET['ticket_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Ticket Management</title>
    <link rel="icon" type="image/svg+xml" href="img/buc.svg">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Theme variables */
        :root { --brand: #800000; --brand-dark: #5a0000; --card-bg: rgba(255,255,255,0.98); }

        body {
            background: var(--brand);
            color: #fff;
            min-height: 100vh;
        }
        .management-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 8px;
            color: #222;
        }
        
        .management-container h1 {
            color: var(--brand);
            margin: 0;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .header-actions h1 {
            margin: 0;
            font-size: 28px;
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-dropdown-toggle {
            color: #666;
            font-size: 13px;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .user-dropdown-toggle:hover {
            background-color: #f0f0f0;
        }

        .user-dropdown-toggle strong {
            color: var(--brand);
        }

        .user-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 180px;
            z-index: 1000;
            margin-top: 4px;
        }

        .user-dropdown:hover .user-dropdown-menu {
            display: block;
        }

        .user-dropdown-menu a {
            display: block;
            padding: 10px 16px;
            color: #333;
            text-decoration: none;
            font-size: 13px;
            transition: background-color 0.2s;
        }

        .user-dropdown-menu a:hover {
            background-color: #f8f9fa;
        }

        .user-dropdown-menu a:first-child {
            border-radius: 4px 4px 0 0;
        }

        .user-dropdown-menu a:last-child {
            border-radius: 0 0 4px 4px;
        }

        .filter-bar {
            margin: 25px 0 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .filter-field {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #555;
        }

        .filter-field label {
            margin: 0;
        }

        .filter-bar select,
        .filter-bar input[type="text"] {
            padding: 6px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            height: 36px;
        }

        .filter-bar select {
            background: white;
            color: #333;
        }

        .filter-bar .search-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 1 1 320px;
            min-width: 260px;
        }

        .filter-bar .search-group input[type="text"] {
            flex: 1 1 auto;
            min-width: 200px;
            height: 36px;
        }

        .search-btn {
            background: #6c757d;
            color: white;
            padding: 0 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            transition: background 0.2s;
            width: 44px;
            min-width: 44px;
            text-align: center;
            flex: 0 0 44px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-btn:hover {
            background: #5a6268;
        }
        
        .action-btn-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .btn {
            background-color: var(--brand);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        
        .btn:hover {
            background-color: var(--brand-dark);
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
        
        .tickets-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .tickets-table th,
        .tickets-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .tickets-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }
        
        .tickets-table th:hover {
            background-color: #e9ecef;
        }
        
        .tickets-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .ticket-row {
            cursor: pointer;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-open {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-in-progress {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-resolved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-delayed {
            background-color: #ffeaa7;
            color: #856404;
        }
        
        .sort-indicator {
            margin-left: 5px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card[data-status],
        .stat-card[data-reset] {
            cursor: pointer;
            border: 1px solid transparent;
            transition: border-color 0.2s, box-shadow 0.2s;
            user-select: none;
        }

        .stat-card[data-status]:hover,
        .stat-card[data-reset]:hover {
            border-color: var(--brand);
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--brand);
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="management-container">
        <?php if ($success && $ticket_id): ?>
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong>Success!</strong> Ticket #<?php echo htmlspecialchars($ticket_id); ?> has been submitted successfully!
        </div>
        <?php endif; ?>
        
        <div class="header-actions">
            <h1>Support Ticket Management</h1>
            <div class="action-btn-group">
                <a href="form.html" class="btn btn-success" title="Add ticket" style="padding:8px 12px; font-size:18px; line-height:1;">+</a>
                <?php if ($current_tech === 'jmilonas'): ?>
                <a href="admin_tech.php" class="btn" style="padding:8px 12px; font-size:13px;">Admin</a>
                <?php endif; ?>
                <a href="index.html" class="btn" style="padding:8px 12px; font-size:13px;">Home</a>
                <a href="inventory.php" class="btn" style="padding:8px 12px; font-size:13px;">Inventory</a>
                <a href="leaderboard.php" class="btn" style="padding:8px 12px; font-size:13px;">Bucit Ranked</a>
                <div class="user-dropdown">
                    <div class="user-dropdown-toggle">
                        <strong><?php echo htmlspecialchars($current_tech); ?></strong> ▾
                    </div>
                    <div class="user-dropdown-menu">
                        <a href="change_password.php">Change Password</a>
                        <a href="logout.php">Log Out</a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        // Get statistics
        $stats_sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_tickets,
                        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed,
                        SUM(CASE WHEN status = 'Delayed' THEN 1 ELSE 0 END) as delayed_tickets
                      FROM tickets";
        $stats_result = $conn->query($stats_sql);
        $stats = $stats_result->fetch_assoc();
        ?>
        
        <div class="stats-container">
            <div class="stat-card" data-reset="true">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card" data-status="Open">
                <div class="stat-number"><?php echo $stats['open_tickets']; ?></div>
                <div class="stat-label">Open</div>
            </div>
            <div class="stat-card" data-status="In Progress">
                <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card" data-status="Closed">
                <div class="stat-number"><?php echo $stats['closed']; ?></div>
                <div class="stat-label">Closed</div>
            </div>
            <div class="stat-card" data-status="Delayed">
                <div class="stat-number"><?php echo $stats['delayed_tickets']; ?></div>
                <div class="stat-label">Delayed</div>
            </div>
        </div>

        <form method="GET" id="ticket-filter-form" class="filter-bar">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
            <input type="hidden" name="order" value="<?php echo htmlspecialchars($order); ?>">

            <div class="filter-field">
                <label for="per-page-select">Rows per page</label>
                <select id="per-page-select" name="per_page" onchange="this.form.submit()" style="width:80px;">
                    <?php foreach(['10','15','25','50','100','all'] as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo ($per_page == $opt) ? 'selected': ''; ?>><?php echo ($opt=='all')? 'All' : $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($has_priority): ?>
            <div class="filter-field">
                <label for="priority-select">Priority</label>
                <select id="priority-select" name="priority" onchange="this.form.submit()" style="width:140px;">
                    <option value="">All</option>
                    <option value="1" <?php echo ($priority_filter === '1')?'selected':''; ?>>1 — Urgent</option>
                    <option value="2" <?php echo ($priority_filter === '2')?'selected':''; ?>>2 — High</option>
                    <option value="3" <?php echo ($priority_filter === '3')?'selected':''; ?>>3 — Normal</option>
                    <option value="4" <?php echo ($priority_filter === '4')?'selected':''; ?>>4 — Low</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="search-group">
                <input id="search-input" type="text" name="q" placeholder="Search name, ID, problem..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn" title="Search">🔍</button>
            </div>
        </form>
        
        <?php if ($result && $result->num_rows > 0): ?>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th onclick="sortTable('id')">
                        Ticket ID
                        <?php if ($sort_by == 'id'): ?>
                            <span class="sort-indicator"><?php echo $order == 'ASC' ? '▲' : '▼'; ?></span>
                        <?php endif; ?>
                    </th>
                    <th onclick="sortTable('first_name')">
                        Student Name
                        <?php if ($sort_by == 'first_name'): ?>
                            <span class="sort-indicator"><?php echo $order == 'ASC' ? '▲' : '▼'; ?></span>
                        <?php endif; ?>
                    </th>
                    <th onclick="sortTable('school_id')">
                        School ID
                        <?php if ($sort_by == 'school_id'): ?>
                            <span class="sort-indicator"><?php echo $order == 'ASC' ? '▲' : '▼'; ?></span>
                        <?php endif; ?>
                    </th>
                    <th onclick="sortTable('problem_category')">
                        Problem
                        <?php if ($sort_by == 'problem_category'): ?>
                            <span class="sort-indicator"><?php echo $order == 'ASC' ? '▲' : '▼'; ?></span>
                        <?php endif; ?>
                    </th>
                    <th onclick="sortTable('status')">
                        Status
                        <?php if ($sort_by == 'status'): ?>
                            <span class="sort-indicator"><?php echo $order == 'ASC' ? '▲' : '▼'; ?></span>
                        <?php endif; ?>
                    </th>
                    <?php if ($has_priority): ?>
                    <th onclick="sortTable('priority')">
                        Priority
                        <?php if ($sort_by == 'priority'): ?>
                            <span class="sort-indicator"><?php echo $order == 'ASC' ? '▲' : '▼'; ?></span>
                        <?php endif; ?>
                    </th>
                    <?php endif; ?>
                    <?php if ($has_tech): ?>
                    <th onclick="sortTable('tech')">
                        Tech
                        <?php if ($sort_by == 'tech'): ?>
                            <span class="sort-indicator"><?php echo $order == 'ASC' ? '▲' : '▼'; ?></span>
                        <?php endif; ?>
                    </th>
                    <?php endif; ?>
                    <th onclick="sortTable('date_reported')">
                        Date Reported
                        <?php if ($sort_by == 'date_reported'): ?>
                            <span class="sort-indicator"><?php echo $order == 'ASC' ? '▲' : '▼'; ?></span>
                        <?php endif; ?>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="ticket-row" onclick="viewTicket(<?php echo $row['id']; ?>)">
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['school_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['problem_category']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status'] ?? 'open')); ?>">
                            <?php echo htmlspecialchars($row['status'] ?? 'Open'); ?>
                        </span>
                    </td>
                    <?php if ($has_priority): ?>
                    <td><?php echo htmlspecialchars($row['priority'] ?? '3'); ?></td>
                    <?php endif; ?>
                    <?php if ($has_tech): ?>
                    <td><?php echo htmlspecialchars($row['tech'] ?? ''); ?></td>
                    <?php endif; ?>
                    <td><?php echo date('M d, Y', strtotime($row['date_reported'])); ?></td>
                    <td onclick="event.stopPropagation();">
                        <a href="edit_ticket.php?id=<?php echo $row['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 12px;" title="Edit Ticket">
                            <img src="img/edit.png" alt="Edit" style="width: 16px; height: 16px; vertical-align: middle; filter: brightness(0) invert(1);">
                        </a>
                        <button onclick="printTicket(<?php echo $row['id']; ?>); event.stopPropagation();" class="btn" style="padding: 5px 10px; font-size: 12px; margin-left: 5px;" title="Print Ticket">
                            <img src="img/print.png" alt="Print" style="width: 16px; height: 16px; vertical-align: middle; filter: brightness(0) invert(1);">
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php if ($per_page !== 'all' && $total_count > 0): 
            $per_page_int = intval($per_page);
            $total_pages = max(1, (int)ceil($total_count / $per_page_int));
        ?>
        <div style="margin-top: 12px; display:flex; justify-content:space-between; align-items:center;">
            <div>Showing <?php echo ($per_page === 'all') ? $total_count : (min($per_page_int, $total_count - ($page-1)*$per_page_int)); ?> of <?php echo $total_count; ?> tickets</div>
            <div>
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$page-1])); ?>" class="btn">Prev</a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$p])); ?>" class="btn" style="background: <?php echo ($p==$page) ? '#5a0000' : 'var(--brand)'; ?>; margin: 0 3px; padding: 5px 10px;"><?php echo $p; ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$page+1])); ?>" class="btn">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div style="text-align: center; padding: 40px;">
            <h3>No tickets found</h3>
            <p>No support tickets have been submitted yet.</p>
            <a href="form.html" class="btn">Create First Ticket</a>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function sortTable(column) {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSort = urlParams.get('sort');
            const currentOrder = urlParams.get('order') || 'DESC';

            let newOrder = 'ASC';
            if (currentSort === column && currentOrder === 'ASC') {
                newOrder = 'DESC';
            }

            urlParams.set('sort', column);
            urlParams.set('order', newOrder);
            // reset to first page when sorting
            urlParams.set('page', 1);
            window.location.href = '?' + urlParams.toString();
        }
        
        function viewTicket(ticketId) {
            window.location.href = `edit_ticket.php?id=${ticketId}`;
        }
        
        function printTicket(ticketId) {
            // Open ticket in new window and trigger print
            const printWindow = window.open(`edit_ticket.php?id=${ticketId}`, '_blank');
            if (printWindow) {
                printWindow.addEventListener('load', function() {
                    printWindow.print();
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card[data-status], .stat-card[data-reset]');
            const searchInput = document.getElementById('search-input');
            const filterForm = document.getElementById('ticket-filter-form');

            if (!searchInput || !filterForm) {
                return;
            }

            statCards.forEach(function(card) {
                card.addEventListener('click', function() {
                    if (card.dataset.reset !== undefined) {
                        searchInput.value = '';
                    } else if (card.dataset.status) {
                        searchInput.value = card.dataset.status.toUpperCase();
                    } else {
                        return;
                    }
                    filterForm.submit();
                });
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
<?php
include 'config.php';
require_admin_login();

$admin_id = $_SESSION['admin_id'];
$admin = get_admin($conn, $admin_id);
$error = '';
$success = '';

// Handle withdrawal request status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);
    $withdrawal_id = isset($_POST['withdrawal_id']) ? intval($_POST['withdrawal_id']) : 0;
    $admin_notes = isset($_POST['admin_notes']) ? sanitize($_POST['admin_notes']) : '';
    
    if ($withdrawal_id > 0) {
        // Get the withdrawal request
        $sql = "SELECT * FROM withdrawal_requests WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $withdrawal_id);
        $stmt->execute();
        $withdrawal = $stmt->get_result()->fetch_assoc();
        
        if (!$withdrawal) {
            $error = '⚠️ Withdrawal request not found.';
        } else {
            $new_status = '';
            
            if ($action === 'accept') {
                $new_status = 'accepted';
            } elseif ($action === 'decline') {
                $new_status = 'declined';
                
                // If declining, refund the points
                $conn->query("START TRANSACTION");
                try {
                    $sql = "UPDATE users SET reward_points = reward_points + ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $withdrawal['amount'], $withdrawal['user_id']);
                    $stmt->execute();
                    
                    // Record refund transaction
                    $sql = "INSERT INTO transactions (user_id, points_change, transaction_type, description) VALUES (?, ?, 'bonus', ?)";
                    $stmt = $conn->prepare($sql);
                    $description = "Withdrawal declined - " . $admin_notes;
                    $stmt->bind_param("iss", $withdrawal['user_id'], $withdrawal['amount'], $description);
                    $stmt->execute();
                    
                    $conn->query("COMMIT");
                } catch (Exception $e) {
                    $conn->query("ROLLBACK");
                    $error = '⚠️ Error declining withdrawal: ' . htmlspecialchars($e->getMessage());
                    $new_status = '';
                }
            }
            
            if (!empty($new_status)) {
                $sql = "UPDATE withdrawal_requests SET status = ?, admin_notes = ?, processed_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $new_status, $admin_notes, $withdrawal_id);
                
                if ($stmt->execute()) {
                    $success = '✅ Withdrawal request ' . ucfirst($new_status) . '.';
                } else {
                    $error = '⚠️ Failed to update withdrawal request.';
                }
            }
        }
    }
}

// Get filter parameter
$filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending';
$valid_filters = ['pending', 'accepted', 'declined', 'all'];
$filter = in_array($filter, $valid_filters) ? $filter : 'pending';

// Fetch withdrawal requests
if ($filter === 'all') {
    $sql = "SELECT wr.*, u.username, u.email FROM withdrawal_requests wr 
            JOIN users u ON wr.user_id = u.id 
            ORDER BY wr.requested_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT wr.*, u.username, u.email FROM withdrawal_requests wr 
            JOIN users u ON wr.user_id = u.id 
            WHERE wr.status = ? 
            ORDER BY wr.requested_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $filter);
}

$stmt->execute();
$withdrawals = $stmt->get_result();

// Get statistics
$sql = "SELECT COUNT(*) as pending FROM withdrawal_requests WHERE status = 'pending'";
$pending_count = $conn->query($sql)->fetch_assoc()['pending'];

$sql = "SELECT COUNT(*) as accepted FROM withdrawal_requests WHERE status = 'accepted'";
$accepted_count = $conn->query($sql)->fetch_assoc()['accepted'];

$sql = "SELECT COUNT(*) as declined FROM withdrawal_requests WHERE status = 'declined'";
$declined_count = $conn->query($sql)->fetch_assoc()['declined'];

$sql = "SELECT SUM(amount) as total FROM withdrawal_requests WHERE status = 'accepted'";
$total_accepted = $conn->query($sql)->fetch_assoc()['total'];
$total_accepted = $total_accepted ? $total_accepted : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Requests - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-box-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-box-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-buttons a {
            padding: 0.75rem 1.5rem;
            background: var(--card-bg);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 4px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-buttons a:hover,
        .filter-buttons a.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .withdrawals-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .withdrawals-table thead {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .withdrawals-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }

        .withdrawals-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .withdrawals-table tbody tr:last-child td {
            border-bottom: none;
        }

        .withdrawal-row {
            transition: background 0.2s ease;
        }

        .withdrawal-row:hover {
            background: rgba(0, 0, 0, 0.02);
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-declined {
            background: #f8d7da;
            color: #842029;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-accept {
            background: #d1e7dd;
            color: #0f5132;
        }

        .btn-accept:hover {
            background: #0f5132;
            color: white;
        }

        .btn-decline {
            background: #f8d7da;
            color: #842029;
        }

        .btn-decline:hover {
            background: #842029;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            resize: vertical;
            min-height: 80px;
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            border-left: 4px solid;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left-color: #c62828;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left-color: #2e7d32;
        }

        .no-records {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
            background: var(--card-bg);
            border-radius: 8px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .withdrawals-table {
                font-size: 0.9rem;
            }

            .withdrawals-table th,
            .withdrawals-table td {
                padding: 0.75rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-small {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="admin.php">Dashboard</a></li>
                <li><a href="admin-withdrawals.php" style="color: var(--primary-color); font-weight: bold;">Withdrawals</a></li>
                <li><a href="api/admin.php">API</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1>💸 Withdrawal Requests</h1>
            <p>Manage user withdrawal requests and process payments</p>
        </div>

        <!-- Statistics -->
        <div class="admin-stats">
            <div class="stat-box">
                <div class="stat-box-label">⏳ Pending</div>
                <div class="stat-box-value"><?php echo $pending_count; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-label">✓ Accepted</div>
                <div class="stat-box-value"><?php echo $accepted_count; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-label">✗ Declined</div>
                <div class="stat-box-value"><?php echo $declined_count; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-label">💰 Total Accepted</div>
                <div class="stat-box-value"><?php echo intval($total_accepted); ?></div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Filter Buttons -->
        <div class="filter-buttons">
            <a href="?status=pending" class="<?php echo $filter === 'pending' ? 'active' : ''; ?>">⏳ Pending</a>
            <a href="?status=accepted" class="<?php echo $filter === 'accepted' ? 'active' : ''; ?>">✓ Accepted</a>
            <a href="?status=declined" class="<?php echo $filter === 'declined' ? 'active' : ''; ?>">✗ Declined</a>
            <a href="?status=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">📊 All</a>
        </div>

        <!-- Withdrawals Table -->
        <?php if ($withdrawals->num_rows > 0): ?>
            <table class="withdrawals-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Bank Details</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($withdrawal = $withdrawals->fetch_assoc()): ?>
                        <tr class="withdrawal-row">
                            <td>
                                <strong><?php echo htmlspecialchars($withdrawal['username']); ?></strong><br>
                                <span style="color: var(--text-secondary); font-size: 0.85rem;"><?php echo htmlspecialchars($withdrawal['email']); ?></span>
                            </td>
                            <td><strong><?php echo intval($withdrawal['amount']); ?> points</strong></td>
                            <td>
                                <div style="font-size: 0.9rem;">
                                    <strong>Bank:</strong> <?php echo htmlspecialchars($withdrawal['bank_name']); ?><br>
                                    <strong>Account:</strong> <?php echo htmlspecialchars($withdrawal['account_name']); ?><br>
                                    <strong>Number:</strong> <?php echo htmlspecialchars($withdrawal['account_number']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $withdrawal['status']; ?>">
                                    <?php echo htmlspecialchars($withdrawal['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($withdrawal['requested_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($withdrawal['status'] === 'pending'): ?>
                                        <button class="btn-small btn-accept" onclick="openModal(<?php echo $withdrawal['id']; ?>, 'accept')">✓ Accept</button>
                                        <button class="btn-small btn-decline" onclick="openModal(<?php echo $withdrawal['id']; ?>, 'decline')">✗ Decline</button>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-size: 0.9rem;">No actions</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-records">
                <p>📭 No withdrawal requests found with status "<?php echo htmlspecialchars($filter); ?>"</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal for actions -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">Action Required</div>
            <form method="POST" action="admin-withdrawals.php">
                <div class="modal-body">
                    <input type="hidden" id="withdrawalId" name="withdrawal_id">
                    <input type="hidden" id="actionType" name="action">
                    
                    <div class="form-group">
                        <label for="adminNotes">Admin Notes:</label>
                        <textarea id="adminNotes" name="admin_notes" placeholder="Enter any notes about this withdrawal..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-small" onclick="closeModal()" style="background: #ddd; color: #333;">Cancel</button>
                    <button type="submit" id="confirmBtn" class="btn-small" style="background: var(--primary-color); color: white;">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(withdrawalId, action) {
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const confirmBtn = document.getElementById('confirmBtn');
            const withdrawalInput = document.getElementById('withdrawalId');
            const actionInput = document.getElementById('actionType');
            
            withdrawalInput.value = withdrawalId;
            actionInput.value = action;
            
            if (action === 'accept') {
                title.textContent = '✓ Accept Withdrawal';
                confirmBtn.textContent = 'Accept Payment';
                confirmBtn.style.background = '#0f5132';
            } else if (action === 'decline') {
                title.textContent = '✗ Decline Withdrawal';
                confirmBtn.textContent = 'Decline & Refund';
                confirmBtn.style.background = '#842029';
            }
            
            modal.classList.add('active');
        }
        
        function closeModal() {
            const modal = document.getElementById('actionModal');
            modal.classList.remove('active');
            document.getElementById('adminNotes').value = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('actionModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

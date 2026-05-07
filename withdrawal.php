<?php
include 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user($conn, $user_id);
$error = '';
$success = '';
$withdrawal_amount = '';
$bank_name = '';
$account_name = '';
$account_number = '';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        $error = '⚠️ Security validation failed. Please try again.';
    } else {
        $withdrawal_amount = isset($_POST['withdrawal_amount']) ? $_POST['withdrawal_amount'] : '';
        $bank_name = isset($_POST['bank_name']) ? sanitize($_POST['bank_name']) : '';
        $account_name = isset($_POST['account_name']) ? sanitize($_POST['account_name']) : '';
        $account_number = isset($_POST['account_number']) ? sanitize($_POST['account_number']) : '';
        
        // Input validation
        if (empty($withdrawal_amount)) {
            $error = '⚠️ Please enter a withdrawal amount.';
        } elseif (empty($bank_name)) {
            $error = '⚠️ Please enter your bank name.';
        } elseif (empty($account_name)) {
            $error = '⚠️ Please enter your account name.';
        } elseif (empty($account_number)) {
            $error = '⚠️ Please enter your account number.';
        } else {
            // Ensure it's a valid number
            $withdrawal_amount = floatval($withdrawal_amount);
            
            // Validation checks
            if ($withdrawal_amount <= 0) {
                $error = '⚠️ Withdrawal amount must be greater than zero.';
            } elseif ($withdrawal_amount > $user['reward_points']) {
                $error = '⚠️ Insufficient balance. You cannot withdraw more than your available points.';
            } elseif (!is_numeric($withdrawal_amount) || $withdrawal_amount !== floor($withdrawal_amount)) {
                $error = '⚠️ Withdrawal amount must be a whole number.';
            } elseif (strlen($bank_name) < 2) {
                $error = '⚠️ Please enter a valid bank name.';
            } elseif (strlen($account_name) < 2) {
                $error = '⚠️ Please enter a valid account name.';
            } elseif (strlen($account_number) < 5) {
                $error = '⚠️ Please enter a valid account number.';
            } else {
                // Additional security check - re-fetch user balance to prevent race conditions
                $sql = "SELECT reward_points FROM users WHERE id = ? FOR UPDATE";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    $error = '⚠️ Database error. Please try again.';
                } else {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $latest_user = $stmt->get_result()->fetch_assoc();
                    
                    // Final balance check with latest data
                    if ($withdrawal_amount > $latest_user['reward_points']) {
                        $error = '⚠️ Insufficient balance. Your balance may have changed. Please try again.';
                    } else {
                        // Start transaction
                        $conn->query("START TRANSACTION");
                        
                        try {
                            // Deduct points from user
                            $sql = "UPDATE users SET reward_points = reward_points - ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            if (!$stmt) {
                                throw new Exception('Prepare failed: ' . $conn->error);
                            }
                            
                            $negative_amount = -$withdrawal_amount;
                            $stmt->bind_param("ii", $withdrawal_amount, $user_id);
                            
                            if (!$stmt->execute()) {
                                throw new Exception('Execute failed: ' . $stmt->error);
                            }
                            
                            // Record transaction
                            $sql = "INSERT INTO transactions (user_id, points_change, transaction_type, description) VALUES (?, ?, 'redemption', ?)";
                            $stmt = $conn->prepare($sql);
                            if (!$stmt) {
                                throw new Exception('Prepare failed: ' . $conn->error);
                            }
                            
                            $description = "Withdrawal request for " . intval($withdrawal_amount) . " points";
                            $negative_amount = -$withdrawal_amount;
                            $stmt->bind_param("iss", $user_id, $negative_amount, $description);
                            
                            if (!$stmt->execute()) {
                                throw new Exception('Execute failed: ' . $stmt->error);
                            }
                            
                            // Store withdrawal request for admin
                            $sql = "INSERT INTO withdrawal_requests (user_id, amount, bank_name, account_name, account_number, status) VALUES (?, ?, ?, ?, ?, 'pending')";
                            $stmt = $conn->prepare($sql);
                            if (!$stmt) {
                                throw new Exception('Prepare failed: ' . $conn->error);
                            }
                            
                            $stmt->bind_param("iisss", $user_id, $withdrawal_amount, $bank_name, $account_name, $account_number);
                            
                            if (!$stmt->execute()) {
                                throw new Exception('Execute failed: ' . $stmt->error);
                            }
                            
                            // Commit transaction
                            $conn->query("COMMIT");
                            
                            // Update session and local user data
                            $user = get_user($conn, $user_id);
                            $success = '✅ Withdrawal successful! ' . intval($withdrawal_amount) . ' points have been deducted from your account. 💳 You will receive your payment within the next 24 hours.';
                            $withdrawal_amount = '';
                            $bank_name = '';
                            $account_name = '';
                            $account_number = '';
                            
                            // Regenerate CSRF token after successful submission
                            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
                            
                        } catch (Exception $e) {
                            // Rollback transaction on error
                            $conn->query("ROLLBACK");
                            $error = '⚠️ Withdrawal failed: ' . htmlspecialchars($e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .withdrawal-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .withdrawal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .withdrawal-header h1 {
            margin: 0;
            font-size: 2rem;
        }

        .withdrawal-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.95;
            font-size: 1rem;
        }

        .withdrawal-form {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .balance-info {
            background: #f0f8ff;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }

        .balance-info p {
            margin: 0.25rem 0;
            color: var(--text-primary);
        }

        .balance-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0.5rem 0 0 0;
        }

        .form-group button {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .form-group button:hover {
            background: var(--secondary-color);
        }

        .form-group button:active {
            transform: scale(0.98);
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

        .transaction-history {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }

        .transaction-history h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-date {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .transaction-amount {
            font-weight: 600;
            color: var(--primary-color);
        }

        .transaction-amount.negative {
            color: #d32f2f;
        }

        .no-transactions {
            text-align: center;
            color: var(--text-secondary);
            padding: 2rem 1rem;
        }

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .warning-section {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin-top: 1.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #856404;
        }

        .warning-section h4 {
            margin: 0 0 0.5rem 0;
            color: #856404;
        }

        .warning-section ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.25rem;
        }

        .warning-section li {
            margin: 0.25rem 0;
        }
    </style>
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="withdrawal.php" style="color: var(--primary-color); font-weight: bold;">Withdrawal</a></li>
                <li><a href="leaderboards.php">Leaderboards</a></li>
                <li><a href="badges.php">Badges</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="page-container">
        <div class="withdrawal-container">
            <!-- Header -->
            <div class="withdrawal-header">
                <h1>💸 Request Withdrawal</h1>
                <p>Convert your reward points into real value</p>
            </div>

            <!-- Current Balance -->
            <div class="balance-info">
                <p>💎 Current Balance:</p>
                <div class="balance-value"><?php echo intval($user['reward_points']); ?> Points</div>
            </div>

            <!-- Error/Success Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Withdrawal Form -->
            <div class="withdrawal-form">
                <form method="POST" action="withdrawal.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="form-group">
                        <label for="withdrawal_amount">Withdrawal Amount (Points)</label>
                        <input 
                            type="number" 
                            id="withdrawal_amount" 
                            name="withdrawal_amount" 
                            min="1" 
                            max="<?php echo intval($user['reward_points']); ?>"
                            step="1"
                            value="<?php echo htmlspecialchars($withdrawal_amount); ?>"
                            placeholder="Enter amount (minimum 1 point)"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="bank_name">Bank Name</label>
                        <input 
                            type="text" 
                            id="bank_name" 
                            name="bank_name" 
                            value="<?php echo htmlspecialchars($bank_name); ?>"
                            placeholder="Enter your bank name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="account_name">Account Name</label>
                        <input 
                            type="text" 
                            id="account_name" 
                            name="account_name" 
                            value="<?php echo htmlspecialchars($account_name); ?>"
                            placeholder="Enter account holder name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="account_number">Account Number</label>
                        <input 
                            type="text" 
                            id="account_number" 
                            name="account_number" 
                            value="<?php echo htmlspecialchars($account_number); ?>"
                            placeholder="Enter your account number"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-primary">Request Withdrawal</button>
                    </div>
                </form>
            </div>

            <!-- Security Notice -->
            <div class="warning-section">
                <h4>🔒 Security Notice</h4>
                <ul>
                    <li>Withdrawal amounts must be whole numbers greater than zero</li>
                    <li>You cannot withdraw more than your available balance</li>
                    <li>All withdrawals are logged for security purposes</li>
                    <li>Each request generates a unique transaction record</li>
                </ul>
            </div>

            <!-- Recent Transactions -->
            <div class="transaction-history">
                <h2>📊 Recent Transactions</h2>
                <?php
                $sql = "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $transactions = $stmt->get_result();

                if ($transactions->num_rows > 0):
                    while ($transaction = $transactions->fetch_assoc()):
                        $is_negative = $transaction['points_change'] < 0;
                        $amount_class = $is_negative ? 'negative' : '';
                        $amount_display = ($is_negative ? '-' : '+') . abs($transaction['points_change']) . ' points';
                ?>
                    <div class="transaction-item">
                        <div>
                            <div><?php echo htmlspecialchars($transaction['description']); ?></div>
                            <div class="transaction-date"><?php echo date('M d, Y at H:i', strtotime($transaction['created_at'])); ?></div>
                        </div>
                        <div class="transaction-amount <?php echo $amount_class; ?>">
                            <?php echo $amount_display; ?>
                        </div>
                    </div>
                <?php
                    endwhile;
                else:
                ?>
                    <div class="no-transactions">
                        <p>No transactions yet. Start earning and making withdrawals!</p>
                    </div>
                <?php endif; ?>
            </div>

            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

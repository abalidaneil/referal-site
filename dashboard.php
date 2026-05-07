<?php
include 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user($conn, $user_id);

// Get referral stats
$sql = "SELECT COUNT(*) as total_referrals, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_referrals FROM referrals WHERE referrer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get referrals list
$sql = "SELECT r.*, u.username, u.email FROM referrals r JOIN users u ON r.referee_id = u.id WHERE r.referrer_id = ? ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$referrals_result = $stmt->get_result();

// Get recent transactions
$sql = "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions_result = $stmt->get_result();

// Get referral link
$referral_link = SITE_URL . "/register.php?ref=" . $user['referral_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="withdrawal.php">Withdrawal</a></li>
                <li><a href="leaderboards.php">Leaderboards</a></li>
                <li><a href="badges.php">Badges</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="page-container">
        <div class="container" style="padding: 2rem 2rem; flex: 1; margin-top: 1rem;">
            <!-- Welcome Header -->
            <div class="profile-header">
                <h1 style="margin-bottom: 0.5rem; font-size: 2.25rem;">Welcome back, <?php echo htmlspecialchars($user['username']); ?>! 👋</h1>
                <p style="font-size: 1.1rem; opacity: 0.95;">Your referral dashboard is ready. Keep growing your network and earning rewards!</p>
            </div>

            <!-- Stats Section -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $user['reward_points']; ?></div>
                    <div class="stat-label">💎 Total Points</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo isset($stats['total_referrals']) ? $stats['total_referrals'] : 0; ?></div>
                    <div class="stat-label">👥 Total Referrals</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo isset($stats['completed_referrals']) ? $stats['completed_referrals'] : 0; ?></div>
                    <div class="stat-label">✓ Completed</div>
                </div>
            </div>

            <!-- Referral Code Section -->
            <div class="referral-code-section">
                <h2>🔗 Your Referral Code</h2>
                <p style="font-size: 1.05rem; margin-bottom: 1.5rem;">Share this code with friends to earn rewards! Each successful sign-up earns you points.</p>
                <div class="referral-code-box" id="referral-code">
                    <?php echo htmlspecialchars($user['referral_code']); ?>
                </div>
                <button class="copy-btn" onclick="copyToClipboard()">📋 Copy Code</button>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1rem; font-size: 1.15rem;">Or Share This Link:</h3>
                <div class="referral-code-box" id="referral-link">
                    <?php echo htmlspecialchars($referral_link); ?>
                </div>
                <button class="copy-btn" onclick="copyLinkToClipboard()">📋 Copy Link</button>
            </div>

            <!-- Referrals Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">👥 Your Referrals</h3>
                    <p class="card-subtitle">Track all your successful and pending referrals</p>
                </div>

                <?php if ($referrals_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Points</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($referral = $referrals_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($referral['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($referral['email']); ?></td>
                                        <td>
                                            <?php if ($referral['status'] === 'completed'): ?>
                                                <span class="badge badge-completed">✓ Completed</span>
                                            <?php elseif ($referral['status'] === 'pending'): ?>
                                                <span class="badge badge-pending">⏳ Pending</span>
                                            <?php else: ?>
                                                <span class="badge badge-cancelled">✗ Cancelled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo REFERRAL_REWARD_POINTS; ?></strong> pts</td>
                                        <td><?php echo date('M d, Y', strtotime($referral['created_at'])); ?></td>
                                        <td><a href="public-profile.php?user_id=<?php echo $referral['referee_id']; ?>" style="color: var(--secondary-color); text-decoration: none; font-weight: 600;">View →</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding: 3rem;">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-title">No Referrals Yet</div>
                        <div class="empty-state-text">Start sharing your referral code above to earn your first rewards!</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Transactions -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3 class="card-title">💰 Recent Transactions</h3>
                    <p class="card-subtitle">Your recent activity and points history</p>
                </div>

                <?php if ($transactions_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Points</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($transaction = $transactions_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $type_labels = [
                                                'referral_reward' => 'Referral Reward',
                                                'redemption' => 'Redemption',
                                                'bonus' => 'Bonus',
                                                'admin' => 'Admin'
                                            ];
                                            echo isset($type_labels[$transaction['transaction_type']]) ? $type_labels[$transaction['transaction_type']] : $transaction['transaction_type'];
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(isset($transaction['description']) ? $transaction['description'] : ''); ?></td>
                                        <td style="font-weight: bold; color: <?php echo $transaction['points_change'] > 0 ? '#10b981' : '#ef4444'; ?>">
                                            <?php echo ($transaction['points_change'] > 0 ? '+' : '') . $transaction['points_change']; ?>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: var(--text-secondary);">No transactions yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <footer>
            <p>&copy; 2026 Referral Hub. All rights reserved.</p>
        </footer>
    </div>

    <script>
        function copyToClipboard() {
            const code = document.getElementById('referral-code').textContent;
            navigator.clipboard.writeText(code).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.classList.add('copied');
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('copied');
                }, 2000);
            });
        }

        function copyLinkToClipboard() {
            const link = document.getElementById('referral-link').textContent;
            navigator.clipboard.writeText(link).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.classList.add('copied');
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('copied');
                }, 2000);
            });
        }
    </script>
</body>
</html>

<?php
include 'config.php';
require_login();

// For production, add proper admin authentication here
// This is a basic example - implement proper authorization
$is_admin = false; // Set to true for testing or add proper admin check

// Get statistics
$sql = "SELECT COUNT(*) as count FROM users";
$total_users = $conn->query($sql)->fetch_assoc()['count'];

$sql = "SELECT COUNT(*) as count FROM referrals";
$total_referrals = $conn->query($sql)->fetch_assoc()['count'];

$sql = "SELECT COUNT(*) as count FROM referrals WHERE status = 'completed'";
$completed_referrals = $conn->query($sql)->fetch_assoc()['count'];

$sql = "SELECT SUM(reward_points) as total FROM users";
$total_temp = $conn->query($sql)->fetch_assoc();
$total_points = isset($total_temp['total']) ? $total_temp['total'] : 0;

// Get recent signups
$sql = "SELECT id, username, email, reward_points, created_at FROM users ORDER BY created_at DESC LIMIT 10";
$recent_users = $conn->query($sql);

// Get recent referrals
$sql = "SELECT r.*, u1.username as referrer, u2.username as referee FROM referrals r JOIN users u1 ON r.referrer_id = u1.id JOIN users u2 ON r.referee_id = u2.id ORDER BY r.created_at DESC LIMIT 10";
$recent_referrals = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="admin.php" style="color: var(--primary-color); font-weight: bold;">Dashboard</a></li>
                <li><a href="admin-withdrawals.php">Withdrawals</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="page-container">
        <div class="container" style="padding: 2rem; flex: 1;">
            <h1 style="margin-bottom: 0.5rem;">📊 Admin Dashboard</h1>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">Platform statistics and management</p>

            <!-- Platform Stats -->
            <div class="dashboard-grid">
                <div class="stat-card" style="border-left: 4px solid var(--primary-color);">
                    <div class="stat-value"><?php echo number_format($total_users); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--secondary-color);">
                    <div class="stat-value"><?php echo number_format($total_referrals); ?></div>
                    <div class="stat-label">Total Referrals</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="stat-value"><?php echo number_format($completed_referrals); ?></div>
                    <div class="stat-label">Completed Referrals</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                    <div class="stat-value"><?php echo number_format($total_points); ?></div>
                    <div class="stat-label">Total Points Distributed</div>
                </div>
            </div>

            <!-- Additional Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Conversion Rate</h3>
                    </div>
                    <div style="text-align: center; padding: 1rem;">
                        <div style="font-size: 2.5rem; color: var(--secondary-color); margin-bottom: 0.5rem;">
                            <?php 
                            $conversion = $total_referrals > 0 ? round(($completed_referrals / $total_referrals) * 100, 2) : 0;
                            echo $conversion . '%';
                            ?>
                        </div>
                        <small style="color: var(--text-secondary);"><?php echo $completed_referrals; ?> of <?php echo $total_referrals; ?> referrals completed</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Average Points per User</h3>
                    </div>
                    <div style="text-align: center; padding: 1rem;">
                        <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                            <?php 
                            $avg_points = $total_users > 0 ? round($total_points / $total_users, 2) : 0;
                            echo number_format($avg_points, 2);
                            ?>
                        </div>
                        <small style="color: var(--text-secondary);">Total points ÷ Users</small>
                    </div>
                </div>
            </div>

            <!-- Recent Signups -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Signups</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Points</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['reward_points']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Referrals -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3 class="card-title">Recent Referrals</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Referrer</th>
                                <th>Referee</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($referral = $recent_referrals->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($referral['referrer']); ?></td>
                                    <td><?php echo htmlspecialchars($referral['referee']); ?></td>
                                    <td>
                                        <?php if ($referral['status'] === 'completed'): ?>
                                            <span class="badge badge-success">Completed</span>
                                        <?php elseif ($referral['status'] === 'pending'): ?>
                                            <span class="badge badge-pending">Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-cancelled">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($referral['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Admin Notes -->
            <div class="card" style="margin-top: 2rem; background-color: #fef3c7; border-left: 4px solid #f59e0b;">
                <h3 style="color: #92400e; margin-bottom: 1rem;">⚠️ Admin Notes</h3>
                <ul style="color: #92400e; margin-left: 1.5rem;">
                    <li>API endpoints are available at <code style="background-color: rgba(0,0,0,0.1); padding: 0.25rem 0.5rem; border-radius: 0.25rem;">/api/admin.php</code></li>
                    <li>Use <code style="background-color: rgba(0,0,0,0.1); padding: 0.25rem 0.5rem; border-radius: 0.25rem;">?action=stats</code> to get platform statistics</li>
                    <li>Use <code style="background-color: rgba(0,0,0,0.1); padding: 0.25rem 0.5rem; border-radius: 0.25rem;">?action=add_bonus</code> to add bonus points to users</li>
                    <li>Implement proper admin authentication before production deployment</li>
                </ul>
            </div>
        </div>

        <footer>
            <p>&copy; 2026 Referral Hub. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

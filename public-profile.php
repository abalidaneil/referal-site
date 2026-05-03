<?php
include 'config.php';

// Get user ID from URL parameter
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$view_user_id = intval($_GET['user_id']);
$view_user = get_user($conn, $view_user_id);

if (!$view_user) {
    die('User not found.');
}

// Get referral stats for this user
$sql = "SELECT COUNT(*) as total_referrals, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_referrals FROM referrals WHERE referrer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get user's rank
$sql = "SELECT COUNT(*) + 1 as rank FROM users u 
        LEFT JOIN referrals r ON u.id = r.referrer_id
        GROUP BY u.id
        HAVING SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) > 
        (SELECT SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) FROM referrals WHERE referrer_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$rank_result = $stmt->get_result();
$rank = ($rank_result->num_rows > 0) ? $rank_result->fetch_assoc()['rank'] : 1;

// Get referrals for display
$sql = "SELECT r.*, u.username, u.email FROM referrals r 
        JOIN users u ON r.referee_id = u.id WHERE r.referrer_id = ? 
        ORDER BY r.created_at DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$referrals_result = $stmt->get_result();

$referral_link = SITE_URL . "/register.php?ref=" . $view_user['referral_code'];
$is_own_profile = is_logged_in() && $_SESSION['user_id'] == $view_user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($view_user['username']); ?>'s Profile - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-stat {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .profile-stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .referrals-section {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .referrals-section h3 {
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .referral-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .referral-item:last-child {
            margin-bottom: 0;
        }

        .referral-info {
            flex: 1;
        }

        .referral-username {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .referral-email {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .referral-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .view-profile-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }

        .view-profile-btn:hover {
            background-color: #4338ca;
        }

        .no-referrals {
            text-align: center;
            color: var(--text-secondary);
            padding: 2rem;
        }

        .profile-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid var(--primary-color);
            border-radius: 6px;
            background: white;
            color: var(--primary-color);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .action-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }

            .profile-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <?php if (is_logged_in()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="leaderboards.php">Leaderboards</a></li>
                    <li><a href="badges.php">Badges</a></li>
                    <li><a href="logout.php" class="logout-btn">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn btn-primary">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <h1><?php echo htmlspecialchars($view_user['username']); ?>'s Profile</h1>
            <p><?php echo htmlspecialchars($view_user['email']); ?></p>
            <p style="margin-top: 0.5rem; opacity: 0.9;">Rank: #<?php echo $rank; ?></p>
            <?php if (!$is_own_profile): ?>
                <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">This is a public profile for someone you referred.</p>
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <div class="profile-stat">
                <div class="profile-stat-label">Completed Referrals</div>
                <div class="profile-stat-value"><?php echo isset($stats['completed_referrals']) ? $stats['completed_referrals'] : 0; ?></div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-label">Total Referrals</div>
                <div class="profile-stat-value"><?php echo isset($stats['total_referrals']) ? $stats['total_referrals'] : 0; ?></div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-label">Reward Points</div>
                <div class="profile-stat-value"><?php echo $view_user['reward_points']; ?></div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-label">Member Since</div>
                <div class="profile-stat-value"><?php echo date('M Y', strtotime($view_user['created_at'])); ?></div>
            </div>
        </div>

        <div class="profile-actions">
            <a href="public-badges.php?user_id=<?php echo $view_user_id; ?>" class="action-btn">View Badges</a>
        </div>

        <div class="referrals-section">
            <h3>Recent Referrals (Showing <?php echo $referrals_result->num_rows; ?>)</h3>
            <?php if ($referrals_result->num_rows > 0): ?>
                <?php while ($referral = $referrals_result->fetch_assoc()): ?>
                    <div class="referral-item">
                        <div class="referral-info">
                            <div class="referral-username"><?php echo htmlspecialchars($referral['username']); ?></div>
                            <div class="referral-email"><?php echo htmlspecialchars($referral['email']); ?></div>
                            <div>
                                <span class="referral-status status-<?php echo $referral['status']; ?>">
                                    <?php echo ucfirst($referral['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-referrals">
                    <p>No referrals yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <footer>
            <p>&copy; 2026 Referral Hub. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

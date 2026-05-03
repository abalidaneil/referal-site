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

// Get user's rank
$sql = "SELECT COUNT(*) + 1 as rank FROM users u 
        LEFT JOIN referrals r ON u.id = r.referrer_id
        GROUP BY u.id
        HAVING SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) > 
        (SELECT SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) FROM referrals WHERE referrer_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rank_result = $stmt->get_result();
$rank = ($rank_result->num_rows > 0) ? $rank_result->fetch_assoc()['rank'] : 1;

// Get referrals
$sql = "SELECT r.*, u.username, u.email, u.created_at as user_created_at FROM referrals r 
        JOIN users u ON r.referee_id = u.id WHERE r.referrer_id = ? 
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$referrals_result = $stmt->get_result();

$referral_link = SITE_URL . "/register.php?ref=" . $user['referral_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Referral Hub</title>
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

        .referral-link-section {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .referral-link-section h3 {
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .referral-code {
            background: var(--light-bg);
            padding: 1rem;
            border-radius: 6px;
            font-family: monospace;
            word-break: break-all;
            margin-bottom: 1rem;
            border: 2px dashed var(--primary-color);
        }

        .copy-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .copy-btn:hover {
            background-color: #4338ca;
        }

        .referrals-section {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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

        .view-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .view-btn:hover {
            background-color: #4338ca;
        }

        .no-referrals {
            text-align: center;
            color: var(--text-secondary);
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="leaderboards.php">Leaderboards</a></li>
                <li><a href="badges.php">Badges</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <h1><?php echo htmlspecialchars($user['username']); ?>'s Profile</h1>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
            <p style="margin-top: 0.5rem; opacity: 0.9;">Rank: #<?php echo $rank; ?></p>
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
                <div class="profile-stat-value"><?php echo $user['reward_points']; ?></div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-label">Member Since</div>
                <div class="profile-stat-value"><?php echo date('M Y', strtotime($user['created_at'])); ?></div>
            </div>
        </div>

        <div class="referral-link-section">
            <h3>Your Referral Code</h3>
            <div class="referral-code"><?php echo htmlspecialchars($user['referral_code']); ?></div>
            <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($user['referral_code']); ?>')">Copy Referral Code</button>
        </div>

        <div class="referrals-section">
            <h3>Your Referrals</h3>
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
                        <a href="public-profile.php?user_id=<?php echo $referral['referee_id']; ?>" class="view-btn">View Profile</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-referrals">
                    <p>You haven't referred anyone yet. Share your referral code to get started!</p>
                </div>
            <?php endif; ?>
        </div>

        <footer>
            <p>&copy; 2026 Referral Hub. All rights reserved.</p>
        </footer>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Referral code copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }
    </script>
</body>
</html>

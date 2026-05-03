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

// Get referral stats
$sql = "SELECT COUNT(*) as total_referrals, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_referrals FROM referrals WHERE referrer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$completed_referrals = isset($stats['completed_referrals']) ? $stats['completed_referrals'] : 0;
$total_referrals = isset($stats['total_referrals']) ? $stats['total_referrals'] : 0;

// Check if first referral was made
$sql = "SELECT MIN(created_at) as first_referral_date FROM referrals WHERE referrer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$first_ref = $stmt->get_result()->fetch_assoc();
$has_first_referral = !empty($first_ref['first_referral_date']);

// Check if fast starter
$is_fast_starter = false;
if ($has_first_referral) {
    $user_created = strtotime($view_user['created_at']);
    $first_ref_created = strtotime($first_ref['first_referral_date']);
    $is_fast_starter = ($first_ref_created - $user_created) <= 86400;
}

// Check if OG
$sql = "SELECT COUNT(*) as user_count FROM users WHERE id <= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$og_check = $stmt->get_result()->fetch_assoc();
$is_og = $og_check['user_count'] <= 100;

// Badge definitions
$badges = [
    // Onboarding
    [
        'id' => 'first_recruit',
        'name' => 'First Recruit',
        'description' => 'Made your first referral',
        'icon' => '🎯',
        'earned' => $has_first_referral,
        'progress' => $has_first_referral ? 1 : 0,
        'max' => 1
    ],
    [
        'id' => 'welcome_wagon',
        'name' => 'Welcome Wagon',
        'description' => 'Referred someone who completed a signup',
        'icon' => '🚚',
        'earned' => $completed_referrals >= 1,
        'progress' => min($completed_referrals, 1),
        'max' => 1
    ],
    
    // Quantity-Based
    [
        'id' => 'social_butterfly',
        'name' => 'Social Butterfly',
        'description' => '5 referrals',
        'icon' => '🦋',
        'earned' => $completed_referrals >= 5,
        'progress' => min($completed_referrals, 5),
        'max' => 5
    ],
    [
        'id' => 'connector',
        'name' => 'Connector',
        'description' => '10 referrals',
        'icon' => '🔗',
        'earned' => $completed_referrals >= 10,
        'progress' => min($completed_referrals, 10),
        'max' => 10
    ],
    [
        'id' => 'magnet',
        'name' => 'Magnet',
        'description' => '25 referrals',
        'icon' => '🧲',
        'earned' => $completed_referrals >= 25,
        'progress' => min($completed_referrals, 25),
        'max' => 25
    ],
    [
        'id' => 'super_connector',
        'name' => 'Super Connector',
        'description' => '50 referrals',
        'icon' => '⚡',
        'earned' => $completed_referrals >= 50,
        'progress' => min($completed_referrals, 50),
        'max' => 50
    ],
    [
        'id' => 'legend',
        'name' => 'Legend',
        'description' => '100 referrals',
        'icon' => '👑',
        'earned' => $completed_referrals >= 100,
        'progress' => min($completed_referrals, 100),
        'max' => 100
    ],
    [
        'id' => 'walking_billboard',
        'name' => 'Walking Billboard',
        'description' => '250+ referrals',
        'icon' => '📣',
        'earned' => $completed_referrals >= 250,
        'progress' => min($completed_referrals, 250),
        'max' => 250
    ],
    
    // Milestones
    [
        'id' => 'bronze_booster',
        'name' => 'Bronze Booster',
        'description' => '5 total referred friends',
        'icon' => '🥉',
        'earned' => $total_referrals >= 5,
        'progress' => min($total_referrals, 5),
        'max' => 5
    ],
    [
        'id' => 'silver_supporter',
        'name' => 'Silver Supporter',
        'description' => '25 total referred friends',
        'icon' => '🥈',
        'earned' => $total_referrals >= 25,
        'progress' => min($total_referrals, 25),
        'max' => 25
    ],
    [
        'id' => 'gold_greeter',
        'name' => 'Gold Greeter',
        'description' => '50 total referred friends',
        'icon' => '🥇',
        'earned' => $total_referrals >= 50,
        'progress' => min($total_referrals, 50),
        'max' => 50
    ],
    [
        'id' => 'platinum_partner',
        'name' => 'Platinum Partner',
        'description' => '100 total referred friends',
        'icon' => '💎',
        'earned' => $total_referrals >= 100,
        'progress' => min($total_referrals, 100),
        'max' => 100
    ],
    [
        'id' => 'diamond_diplomat',
        'name' => 'Diamond Diplomat',
        'description' => '250 total referred friends',
        'icon' => '✨',
        'earned' => $total_referrals >= 250,
        'progress' => min($total_referrals, 250),
        'max' => 250
    ],
    
    // Speed & Special
    [
        'id' => 'fast_starter',
        'name' => 'Fast Starter',
        'description' => 'First referral within 24 hours of joining',
        'icon' => '⚡',
        'earned' => $is_fast_starter,
        'progress' => $is_fast_starter ? 1 : 0,
        'max' => 1
    ],
    
    // OG
    [
        'id' => 'og',
        'name' => 'OG (Original Gangster)',
        'description' => 'Among first 100 users to join',
        'icon' => '🏁',
        'earned' => $is_og,
        'progress' => $is_og ? 1 : 0,
        'max' => 1
    ],
];

$earned_badges = array_filter($badges, function($b) { return $b['earned']; });
$locked_badges = array_filter($badges, function($b) { return !$b['earned']; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($view_user['username']); ?>'s Badges - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .badges-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem;
        }

        .badges-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .badges-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .badges-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .badge-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .badge-stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .badge-stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .badge-stat-label {
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        .badges-section {
            margin-bottom: 3rem;
        }

        .badges-section h2 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--primary-color);
        }

        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .badge-card {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .badge-card.earned {
            border-color: var(--secondary-color);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .badge-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .badge-card.earned:hover {
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }

        .badge-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .badge-card.earned .badge-icon {
            opacity: 1;
            filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.5));
        }

        .badge-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .badge-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .badge-progress-bar {
            width: 100%;
            height: 8px;
            background-color: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }

        .badge-progress-fill {
            height: 100%;
            background-color: var(--secondary-color);
            transition: width 0.3s;
        }

        .badge-progress-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        .locked-badge {
            opacity: 0.6;
        }

        .earned-badge {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(79, 70, 229, 0.1));
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .badge-stats {
                grid-template-columns: 1fr;
            }

            .badges-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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

    <div class="badges-container">
        <a href="public-profile.php?user_id=<?php echo $view_user_id; ?>" class="back-link">← Back to Profile</a>

        <div class="badges-header">
            <h1><?php echo htmlspecialchars($view_user['username']); ?>'s Badges</h1>
            <p>🏅 Unlocked Achievement Collection</p>
        </div>

        <div class="badge-stats">
            <div class="badge-stat-card">
                <div class="badge-stat-value"><?php echo count($earned_badges); ?></div>
                <div class="badge-stat-label">Badges Earned</div>
            </div>
            <div class="badge-stat-card">
                <div class="badge-stat-value"><?php echo count($badges) - count($earned_badges); ?></div>
                <div class="badge-stat-label">Badges Locked</div>
            </div>
        </div>

        <!-- Earned Badges Section -->
        <?php if (count($earned_badges) > 0): ?>
            <div class="badges-section">
                <h2>🎉 Earned Badges (<?php echo count($earned_badges); ?>)</h2>
                <div class="badges-grid">
                    <?php foreach ($earned_badges as $badge): ?>
                        <div class="badge-card earned earned-badge">
                            <div class="badge-icon"><?php echo $badge['icon']; ?></div>
                            <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                            <div class="badge-description"><?php echo htmlspecialchars($badge['description']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Locked Badges Section -->
        <?php if (count($locked_badges) > 0): ?>
            <div class="badges-section">
                <h2>🔒 Locked Badges (<?php echo count($locked_badges); ?>)</h2>
                <div class="badges-grid">
                    <?php foreach ($locked_badges as $badge): ?>
                        <div class="badge-card locked-badge">
                            <div class="badge-icon"><?php echo $badge['icon']; ?></div>
                            <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                            <div class="badge-description"><?php echo htmlspecialchars($badge['description']); ?></div>
                            <div class="badge-progress-bar">
                                <div class="badge-progress-fill" style="width: <?php echo ($badge['progress'] / $badge['max']) * 100; ?>%"></div>
                            </div>
                            <div class="badge-progress-text"><?php echo $badge['progress']; ?>/<?php echo $badge['max']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <footer>
            <p>&copy; 2026 Referral Hub. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

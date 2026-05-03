<?php
include 'config.php';

// Get top referrers
$sql = "
    SELECT 
        u.id,
        u.username,
        u.reward_points,
        COUNT(r.id) as total_referrals,
        SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_referrals,
        u.created_at
    FROM users u
    LEFT JOIN referrals r ON u.id = r.referrer_id
    GROUP BY u.id
    HAVING total_referrals > 0
    ORDER BY u.reward_points DESC
    LIMIT 100
";
$result = $conn->query($sql);
$top_referrers = [];
while ($row = $result->fetch_assoc()) {
    $top_referrers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Referrers - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php if (is_logged_in()): ?>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="leaderboard.php">Leaderboard</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>
    <?php else: ?>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="leaderboard.php">Leaderboard</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="btn btn-primary">Sign Up</a></li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>

    <div class="page-container">
        <div class="container" style="padding: 2rem; flex: 1;">
            <h1 style="margin-bottom: 0.5rem;">🏆 Top Referrers</h1>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">See who's earning the most rewards!</p>

            <?php if (count($top_referrers) > 0): ?>
                <div class="card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th style="text-align: center; width: 60px;">Rank</th>
                                    <th>Username</th>
                                    <th style="text-align: right;">Total Referrals</th>
                                    <th style="text-align: right;">Completed</th>
                                    <th style="text-align: right;">Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_referrers as $index => $referrer): ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: bold;">
                                            <?php 
                                            if ($index === 0) echo '🥇';
                                            elseif ($index === 1) echo '🥈';
                                            elseif ($index === 2) echo '🥉';
                                            else echo ($index + 1);
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($referrer['username']); ?></strong>
                                            <br>
                                            <small style="color: var(--text-secondary);">Joined <?php echo date('M d, Y', strtotime($referrer['created_at'])); ?></small>
                                        </td>
                                        <td style="text-align: right;">
                                            <?php echo isset($referrer['total_referrals']) ? $referrer['total_referrals'] : 0; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <span class="badge badge-success">
                                                <?php echo isset($referrer['completed_referrals']) ? $referrer['completed_referrals'] : 0; ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; font-weight: bold; color: var(--primary-color);">
                                            <?php echo number_format($referrer['reward_points']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="background-color: var(--light-bg); padding: 2rem; border-radius: 0.5rem; margin-top: 2rem; text-align: center;">
                    <h3>Want to reach the top?</h3>
                    <p style="margin-bottom: 1rem;">Start sharing your referral code and climbing the leaderboard!</p>
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="btn btn-primary">Go to Your Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Get Started Now</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <h2>No referrers yet</h2>
                    <p style="margin-bottom: 1rem; color: var(--text-secondary);">Be the first to start earning rewards!</p>
                    <a href="register.php" class="btn btn-primary">Sign Up Now</a>
                </div>
            <?php endif; ?>
        </div>

        <footer>
            <p>&copy; 2026 Referral Hub. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

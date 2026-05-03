<?php
include 'config.php';
require_login();

// Get top referrers by number of referrals
$sql = "SELECT u.id, u.username, u.reward_points, 
        COUNT(r.id) as total_referrals,
        COALESCE(SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_referrals
        FROM users u
        LEFT JOIN referrals r ON u.id = r.referrer_id
        GROUP BY u.id, u.username, u.reward_points
        ORDER BY completed_referrals DESC, total_referrals DESC
        LIMIT 100";
$stmt = $conn->prepare($sql);
$stmt->execute();
$leaderboards = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboards - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .leaderboard-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .leaderboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .leaderboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .leaderboard-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .leaderboard-table {
            width: 100%;
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .leaderboard-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .leaderboard-table thead {
            background-color: var(--primary-color);
            color: white;
        }

        .leaderboard-table thead th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .leaderboard-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
        }

        .leaderboard-table tbody tr:hover {
            background-color: var(--light-bg);
        }

        .leaderboard-table tbody tr:last-child {
            border-bottom: none;
        }

        .leaderboard-table tbody td {
            padding: 1rem;
        }

        .rank {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .rank.top-1 {
            color: #fbbf24;
            font-size: 1.3rem;
        }

        .rank.top-2 {
            color: #a8a29e;
        }

        .rank.top-3 {
            color: #d97706;
        }

        .username {
            font-weight: 600;
            color: var(--text-primary);
        }

        .stat-badge {
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .points {
            font-weight: 600;
            color: var(--primary-color);
        }

        .no-data {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
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
                <li><a href="leaderboards.php" class="active">Leaderboards</a></li>
                <li><a href="badges.php">Badges</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="leaderboard-container">
        <div class="leaderboard-header">
            <h1>🏆 Leaderboards</h1>
            <p>See who's leading in referrals!</p>
        </div>

        <div class="leaderboard-table">
            <?php if ($leaderboards->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Username</th>
                            <th>Completed Referrals</th>
                            <th>Total Referrals</th>
                            <th>Reward Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        while ($row = $leaderboards->fetch_assoc()):
                            $completed = isset($row['completed_referrals']) ? $row['completed_referrals'] : 0;
                            $total = isset($row['total_referrals']) ? $row['total_referrals'] : 0;
                            
                            if ($total === 0) continue;
                            
                            $rank_class = '';
                            if ($rank === 1) $rank_class = 'top-1';
                            elseif ($rank === 2) $rank_class = 'top-2';
                            elseif ($rank === 3) $rank_class = 'top-3';
                        ?>
                        <tr>
                            <td><span class="rank <?php echo $rank_class; ?>">#<?php echo $rank; ?></span></td>
                            <td><span class="username"><?php echo htmlspecialchars($row['username']); ?></span></td>
                            <td><span class="stat-badge"><?php echo $completed; ?></span></td>
                            <td><?php echo $total; ?></td>
                            <td><span class="points"><?php echo $row['reward_points']; ?> pts</span></td>
                        </tr>
                        <?php
                            $rank++;
                        endwhile;
                        ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>No leaderboard data available yet. Be the first to refer someone!</p>
                </div>
            <?php endif; ?>
        </div>

        <footer>
            <p>&copy; 2026 Referral Hub. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

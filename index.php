<?php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Hub - Earn Rewards</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php if (is_logged_in()): ?>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>
    <?php else: ?>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="btn btn-primary">Sign Up</a></li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>

    <div class="page-container">
        <div class="hero">
            <div class="container">
                <h1>Earn Rewards With Every Referral</h1>
                <p>Share your unique referral code and earn points for every successful sign-up. Build trust, grow faster.</p>
                <div class="hero-buttons">
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="btn btn-primary">→ View Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">🚀 Start Earning Now</a>
                        <a href="login.php" class="btn btn-outline">Already a Member?</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="container" style="padding: 4rem 2rem; flex: 1;">
            <!-- Features Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">💎 Easy Earnings</h3>
                    </div>
                    <p style="color: var(--text-secondary); font-weight: 500;">Earn 100 points for every successful referral. No hidden fees, no complications. Convert points to real rewards!</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📊 Real-Time Tracking</h3>
                    </div>
                    <p style="color: var(--text-secondary); font-weight: 500;">View your referrals, earnings, and rewards in real-time on your secure dashboard. Complete transparency.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">🎁 Bonus Rewards</h3>
                    </div>
                    <p style="color: var(--text-secondary); font-weight: 500;">Get bonus points when someone signs up using your code. The more you refer, the more you earn!</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">🔗 Social Growth</h3>
                    </div>
                    <p style="color: var(--text-secondary); font-weight: 500;">Share your unique referral link with friends and build your network. Easy share buttons included.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📈 Unlimited Referrals</h3>
                    </div>
                    <p>No limit on how many people you can refer. Keep earning!</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">✅ Instant Verification</h3>
                    </div>
                    <p>Referrals are verified instantly and rewards are added to your account.</p>
                </div>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <h2>How It Works</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;">
                    <div>
                        <div style="font-size: 3rem; margin-bottom: 1rem;">1️⃣</div>
                        <h3>Sign Up</h3>
                        <p>Create your account and get your unique referral code.</p>
                    </div>
                    <div>
                        <div style="font-size: 3rem; margin-bottom: 1rem;">2️⃣</div>
                        <h3>Share Your Code</h3>
                        <p>Share your referral code with friends and family.</p>
                    </div>
                    <div>
                        <div style="font-size: 3rem; margin-bottom: 1rem;">3️⃣</div>
                        <h3>Earn Points</h3>
                        <p>Get points when they sign up using your code.</p>
                    </div>
                    <div>
                        <div style="font-size: 3rem; margin-bottom: 1rem;">4️⃣</div>
                        <h3>Redeem Rewards</h3>
                        <p>Convert your points to amazing rewards.</p>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <p>&copy; 2026 Referral Hub. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

<?php
include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = sanitize(isset($_POST['token']) ? $_POST['token'] : '');
    $username = sanitize(isset($_POST['username']) ? $_POST['username'] : '');
    $email = sanitize(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $referral_code_input = sanitize(isset($_POST['referral_code']) ? $_POST['referral_code'] : '');

    // Check token first
    if (empty($token)) {
        $error = 'Registration token is required. Please get one first.';
    } else {
        // Verify token
        $token_valid = verify_registration_token($conn, $token);
        
        if (!$token_valid) {
            $error = 'Invalid or expired registration token. Please request a new one.';
        } else {
            // Validation
            if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
                $error = 'All fields are required.';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } else {
                // Check if username or email already exists
                $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $error = 'Username or email already exists.';
                } else {
                    // Check if referral code exists
                    $referred_by_id = null;
                    if (!empty($referral_code_input)) {
                        $sql = "SELECT id FROM users WHERE referral_code = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $referral_code_input);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $referrer = $result->fetch_assoc();
                            $referred_by_id = $referrer['id'];
                        } else {
                            $error = 'Invalid referral code.';
                        }
                    }

                    if (empty($error)) {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Create new user
                        $sql = "INSERT INTO users (username, email, password, referral_code, referred_by_id) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);

                        // Generate initial referral code (will be updated)
                        $temp_code = uniqid();
                        $stmt->bind_param("ssssi", $username, $email, $hashed_password, $temp_code, $referred_by_id);

                        if ($stmt->execute()) {
                            $new_user_id = $conn->insert_id;

                            // Generate proper referral code
                            $referral_code = generate_referral_code($new_user_id);
                            $sql = "UPDATE users SET referral_code = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("si", $referral_code, $new_user_id);
                            $stmt->execute();

                            // If referred by someone, create referral record and give rewards
                            if ($referred_by_id) {
                                $sql = "INSERT INTO referrals (referrer_id, referee_id, status) VALUES (?, ?, 'completed')";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("ii", $referred_by_id, $new_user_id);
                                $stmt->execute();

                                // Add reward points to referrer
                                $sql = "UPDATE users SET reward_points = reward_points + ? WHERE id = ?";
                                $stmt = $conn->prepare($sql);
                                $reward_points = REFERRAL_REWARD_POINTS;
                                $stmt->bind_param("ii", $reward_points, $referred_by_id);
                                $stmt->execute();

                                // Record transaction
                                $sql = "INSERT INTO transactions (user_id, points_change, transaction_type, description) VALUES (?, ?, 'referral_reward', ?)";
                                $stmt = $conn->prepare($sql);
                                $description = "Referral from $username";
                                $stmt->bind_param("iss", $referred_by_id, $reward_points, $description);
                                $stmt->execute();
                            }

                            $success = 'Registration successful! Redirecting to login...';
                            header('refresh:2;url=login.php');
                        } else {
                            $error = 'Registration failed. Please try again.';
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
    <title>Register - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="page-container">
        <div class="form-container">
            <div style="text-align: center; margin-bottom: 2.5rem;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🚀</div>
                <h2 style="margin-bottom: 0.5rem;">Create Your Account</h2>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">Join our growing community of referrers. Start earning rewards today!</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span>✓</span> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="token">Registration Token <span class="required">*</span></label>
                    <input type="text" id="token" name="token" required autofocus placeholder="Paste your registration token here">
                    <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">Need a token? <a href="get-token.php" style="color: var(--primary-color);">Request one here</a></small>
                </div>

                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                    <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">At least 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group">
                    <label for="referral_code">Referral Code</label>
                    <input type="text" id="referral_code" name="referral_code" placeholder="Enter referral code if you have one">
                    <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">✓ Optional - leave blank if you don't have one</small>
                </div>

                <button type="submit" class="form-submit">🎉 Create Account</button>

                <div class="form-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>

        <footer>
            <p>&copy; 2026 Referral Hub. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

<?php
include 'config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize(isset($_POST['email']) ? $_POST['email'] : '');

    if (empty($email)) {
        $message = 'Email is required.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address.';
        $message_type = 'error';
    } else {
        // Check if user already exists with this email
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = 'An account already exists with this email. Please <a href="login.php" style="color: var(--primary-color);">login here</a>.';
            $message_type = 'error';
        } else {
            // Generate registration token
            $token = generate_login_token($conn, $email);
            $message = 'Registration token generated: <strong>' . htmlspecialchars($token) . '</strong><br><br>This token is valid for 1 hour and can only be used once. Copy it and use it when creating your account.';
            $message_type = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Registration Token - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="nav-logo">Referral Hub</a>
            <ul class="nav-menu">
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="btn btn-primary">Sign Up</a></li>
            </ul>
        </div>
    </nav>

    <div class="page-container">
        <div class="form-container">
            <h2 style="text-align: center; margin-bottom: 0.5rem;">Get Registration Token</h2>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">Request a one-time registration token to create your account</p>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autofocus>
                    <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">Enter the email address you want to use for your account</small>
                </div>

                <button type="submit" class="form-submit">Request Token</button>

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

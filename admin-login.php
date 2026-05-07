<?php
include 'config.php';

$error = '';
$success = '';

// If already logged in, redirect to admin dashboard
if (is_admin_logged_in()) {
    header('Location: admin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validation
    if (empty($username)) {
        $error = '⚠️ Please enter your username.';
    } elseif (empty($password)) {
        $error = '⚠️ Please enter your password.';
    } else {
        // Check admin credentials
        $sql = "SELECT * FROM admins WHERE username = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            $error = '⚠️ Database error: ' . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param("s", $username);
            
            if (!$stmt->execute()) {
                $error = '⚠️ Database error: ' . htmlspecialchars($stmt->error);
            } else {
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $error = '⚠️ Invalid username or password.';
                } else {
                    $admin = $result->fetch_assoc();
                    
                    // Verify password
                    if (password_verify($password, $admin['password'])) {
                        // Set session
                        $_SESSION['admin_id'] = $admin['id'];
                        $success = '✅ Login successful! Redirecting...';
                        header('refresh:1;url=admin.php');
                    } else {
                        $error = '⚠️ Invalid username or password.';
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
    <title>Admin Login - Referral Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 2rem;
        }

        .login-box {
            background: white;
            padding: 3rem 2rem;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .login-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
            color: var(--text-primary);
        }

        .login-header p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-group button {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .form-group button:active {
            transform: translateY(0);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            border-left: 4px solid;
            font-size: 0.9rem;
            animation: slideDown 0.3s ease;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left-color: #c62828;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left-color: #2e7d32;
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }

        .login-footer p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .security-info {
            background: #f0f8ff;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-top: 2rem;
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .security-info strong {
            display: block;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 2rem 1.5rem;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }

            .login-header .icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <!-- Header -->
            <div class="login-header">
                <div class="icon">🔐</div>
                <h1>Admin Portal</h1>
                <p>Manage withdrawals and system</p>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="admin-login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Enter your username"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="form-group">
                    <button type="submit">🔓 Login</button>
                </div>
            </form>

            <!-- Security Info -->
            <div class="security-info">
                <strong>🔒 Security Notice</strong>
                Never share your admin credentials with anyone. This portal manages critical system functions.
            </div>

            <!-- Footer -->
            <div class="login-footer">
                <p>
                    Need help? <a href="index.php">← Back to Home</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Set default timezone
date_default_timezone_set('UTC');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'usbw');
define('DB_NAME', 'referral_site');

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Site Configuration
define('SITE_URL', 'http://localhost/referral-site');
define('REFERRAL_REWARD_POINTS', 100);
define('REFERRAL_BONUS_POINTS', 50);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to generate referral code
function generate_referral_code($user_id) {
    $prefix = strtoupper(substr(md5($user_id), 0, 3));
    $suffix = str_pad($user_id, 6, '0', STR_PAD_LEFT);
    return $prefix . $suffix;
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

// Function to check if admin is logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Function to redirect if not admin
function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: admin-login.php');
        exit();
    }
}

// Function to get user by ID
function get_user($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get admin by ID
function get_admin($conn, $admin_id) {
    $sql = "SELECT * FROM admins WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to generate login token
function generate_login_token($conn, $email) {
    $token = bin2hex(openssl_random_pseudo_bytes(32));
    
    $sql = "INSERT INTO login_tokens (token, email, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();
    
    return $token;
}

// Function to verify and use login token
function verify_login_token($conn, $token) {
    $sql = "SELECT * FROM login_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $token_data = $result->fetch_assoc();
    
    // Mark token as used
    $sql = "UPDATE login_tokens SET used = 1, used_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $token_data['id']);
    $stmt->execute();
    
    return $token_data['email'];
}

// Function to verify registration token
function verify_registration_token($conn, $token) {
    $sql = "SELECT * FROM login_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $token_data = $result->fetch_assoc();
    
    // Mark token as used
    $sql = "UPDATE login_tokens SET used = 1, used_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $token_data['id']);
    $stmt->execute();
    
    return true;
}

// Function to sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Compatibility functions for PHP < 5.5 (password_hash and password_verify)
if (!function_exists('password_hash')) {
    define('PASSWORD_DEFAULT', 1);
    
    function password_hash($password, $algo, $options = array()) {
        $salt = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
        if ($salt === false) {
            $salt = uniqid(mt_rand(), true);
        }
        $salt = base64_encode($salt);
        $salt = str_replace('+', '.', $salt);
        $hash = hash('sha256', $salt . $password, false);
        return $salt . ':' . $hash;
    }
}

if (!function_exists('password_verify')) {
    function password_verify($password, $hash) {
        if (strpos($hash, ':') === false) {
            return false;
        }
        list($salt, $stored_hash) = explode(':', $hash);
        $new_hash = hash('sha256', $salt . $password, false);
        return $new_hash === $stored_hash;
    }
}
?>

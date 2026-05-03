<?php
include '../config.php';

// API for admin functions
// NOTE: In production, you should add authentication and authorization checks

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get all users (admin only)
if ($action === 'users' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, username, email, reward_points, created_at FROM users ORDER BY created_at DESC LIMIT 100";
    $result = $conn->query($sql);
    $users = [];

    while ($user = $result->fetch_assoc()) {
        $users[] = $user;
    }

    echo json_encode(['success' => true, 'users' => $users]);
    exit();
}

// Get user details
if ($action === 'user_detail' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = intval(isset($_GET['user_id']) ? $_GET['user_id'] : 0);

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit();
    }

    $user = get_user($conn, $user_id);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    $sql = "SELECT COUNT(*) as total FROM referrals WHERE referrer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    $user['total_referrals'] = $stats['total'];

    echo json_encode(['success' => true, 'user' => $user]);
    exit();
}

// Add bonus points (admin only)
if ($action === 'add_bonus' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval(isset($_POST['user_id']) ? $_POST['user_id'] : 0);
    $points = intval(isset($_POST['points']) ? $_POST['points'] : 0);
    $reason = sanitize(isset($_POST['reason']) ? $_POST['reason'] : 'Admin bonus');

    if (!$user_id || $points <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID or points']);
        exit();
    }

    if (!get_user($conn, $user_id)) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Update points
    $sql = "UPDATE users SET reward_points = reward_points + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $points, $user_id);
    $stmt->execute();

    // Record transaction
    $sql = "INSERT INTO transactions (user_id, points_change, transaction_type, description) VALUES (?, ?, 'bonus', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $points, $reason);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Bonus points added']);
    exit();
}

// Get platform statistics
if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Total users
    $sql = "SELECT COUNT(*) as count FROM users";
    $total_users = $conn->query($sql)->fetch_assoc()['count'];

    // Total referrals
    $sql = "SELECT COUNT(*) as count FROM referrals";
    $total_referrals = $conn->query($sql)->fetch_assoc()['count'];

    // Total completed referrals
    $sql = "SELECT COUNT(*) as count FROM referrals WHERE status = 'completed'";
    $completed_referrals = $conn->query($sql)->fetch_assoc()['count'];

    // Total points distributed
    $sql = "SELECT SUM(reward_points) as total FROM users";
    $total_points = $conn->query($sql)->fetch_assoc()['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'total_users' => $total_users,
        'total_referrals' => $total_referrals,
        'completed_referrals' => $completed_referrals,
        'total_points_distributed' => $total_points
    ]);
    exit();
}

// Return 404 if action not found
header('HTTP/1.0 404 Not Found');
echo json_encode(['success' => false, 'message' => 'API action not found']);
?>

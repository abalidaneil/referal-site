<?php
include '../config.php';

// Handle different API endpoints
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Mark referral as completed
if ($action === 'complete_referral' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $referral_id = intval(isset($_POST['referral_id']) ? $_POST['referral_id'] : 0);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if (!$user_id || !$referral_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }

    // Get referral and verify ownership
    $sql = "SELECT * FROM referrals WHERE id = ? AND referrer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $referral_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Referral not found']);
        exit();
    }

    $referral = $result->fetch_assoc();

    if ($referral['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Referral already processed']);
        exit();
    }

    // Update referral status
    $sql = "UPDATE referrals SET status = 'completed', completed_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $referral_id);
    $stmt->execute();

    // Award points
    $sql = "UPDATE users SET reward_points = reward_points + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $reward_points = REFERRAL_REWARD_POINTS;
    $stmt->bind_param("ii", $reward_points, $user_id);
    $stmt->execute();

    // Record transaction
    $sql = "INSERT INTO transactions (user_id, points_change, transaction_type, description) VALUES (?, ?, 'referral_reward', 'Referral reward for signup verification')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $reward_points);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Referral completed and points awarded']);
    exit();
}

// Get referral stats
if ($action === 'get_stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $user = get_user($conn, $user_id);

    $sql = "SELECT COUNT(*) as total_referrals, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_referrals FROM referrals WHERE referrer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'reward_points' => $user['reward_points'],
        'total_referrals' => isset($stats['total_referrals']) ? $stats['total_referrals'] : 0,
        'completed_referrals' => isset($stats['completed_referrals']) ? $stats['completed_referrals'] : 0
    ]);
    exit();
}

// Verify referral code and get referrer info
if ($action === 'verify_code' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize(isset($_POST['code']) ? $_POST['code'] : '');

    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Code is required']);
        exit();
    }

    $sql = "SELECT id, username FROM users WHERE referral_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid referral code']);
        exit();
    }

    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'user_id' => $user['id'], 'username' => $user['username']]);
    exit();
}

// Get top referrers (public stats)
if ($action === 'top_referrers' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = intval(isset($_GET['limit']) ? $_GET['limit'] : 10);
    $limit = min($limit, 50); // Max 50

    $sql = "SELECT u.id, u.username, u.reward_points, COUNT(r.id) as referral_count FROM users u LEFT JOIN referrals r ON u.id = r.referrer_id GROUP BY u.id ORDER BY u.reward_points DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $referrers = [];
    while ($row = $result->fetch_assoc()) {
        $referrers[] = $row;
    }

    echo json_encode(['success' => true, 'referrers' => $referrers]);
    exit();
}

// Return 404 if action not found
header('HTTP/1.0 404 Not Found');
echo json_encode(['success' => false, 'message' => 'API action not found']);
?>

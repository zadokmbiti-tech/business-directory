<?php
require_once 'config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// ── Must be POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// ── Identity: logged-in user OR anonymous ────────────────────────────────────
$user_id       = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$reviewer_name = trim($_POST['reviewer_name'] ?? '');
$reviewer_name = ($reviewer_name === '') ? 'Anonymous' : htmlspecialchars($reviewer_name, ENT_QUOTES, 'UTF-8');
$ip            = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// ── Collect inputs ───────────────────────────────────────────────────────────
$business_id = (int)($_POST['business_id'] ?? 0);
$rating      = (int)($_POST['rating']      ?? 0);
$review_text = trim($_POST['review_text']  ?? '');

// ── Validate inputs ──────────────────────────────────────────────────────────
if ($business_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid business.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit;
}
if (strlen($review_text) < 10) {
    echo json_encode(['success' => false, 'message' => 'Review must be at least 10 characters.']);
    exit;
}
if (strlen($review_text) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Review must be under 1000 characters.']);
    exit;
}

$conn = getDBConnection();

// ── Rate-limit anonymous users: max 3 reviews per IP per day ─────────────────
if ($user_id === null) {
    $rl = $conn->prepare(
        "SELECT COUNT(*) FROM reviews
         WHERE reviewer_ip = ? AND created_at >= CURDATE() AND user_id IS NULL"
    );
    $rl->bind_param("s", $ip);
    $rl->execute();
    $rl->bind_result($ip_count);
    $rl->fetch();
    $rl->close();

    if ($ip_count >= 3) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'You have reached the daily review limit. Please try again tomorrow.']);
        exit;
    }
}

// ── Check business exists and is approved ────────────────────────────────────
$chk = $conn->prepare("SELECT id FROM businesses WHERE id = ? AND status = 'approved'");
$chk->bind_param("i", $business_id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows === 0) {
    $chk->close(); $conn->close();
    echo json_encode(['success' => false, 'message' => 'Business not found.']);
    exit;
}
$chk->close();

// ── Duplicate check ──────────────────────────────────────────────────────────
// Logged-in: one review per business ever
// Anonymous: one review per business per day per IP
if ($user_id !== null) {
    $dup = $conn->prepare(
        "SELECT id FROM reviews
         WHERE business_id = ? AND user_id = ? AND status = 'approved' LIMIT 1"
    );
    $dup->bind_param("ii", $business_id, $user_id);
} else {
    $dup = $conn->prepare(
        "SELECT id FROM reviews
         WHERE business_id = ? AND reviewer_ip = ? AND DATE(created_at) = CURDATE()
           AND status = 'approved' LIMIT 1"
    );
    $dup->bind_param("is", $business_id, $ip);
}
$dup->execute();
$dup->store_result();
if ($dup->num_rows > 0) {
    $dup->close(); $conn->close();
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this business today.']);
    exit;
}
$dup->close();

// ── Insert review — auto-approved for everyone ───────────────────────────────
$ins = $conn->prepare(
    "INSERT INTO reviews (business_id, user_id, reviewer_name, reviewer_ip, rating, review_text, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW())"
);
$ins->bind_param("iissis",
    $business_id,
    $user_id,
    $reviewer_name,
    $ip,
    $rating,
    $review_text
);

if ($ins->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your review has been posted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review. Please try again.']);
}

$ins->close();
$conn->close();
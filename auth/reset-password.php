<?php
require '../config/db.php';

$token = $_GET['token'] ?? '';
$error = '';
$valid = false;

$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $valid = true;
    $user = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt2 = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt2->bind_param("si", $new_password, $user['id']);
    $stmt2->execute();

    header("Location: login.php?msg=Password updated! Please log in.");
    exit;
}
?>

<?php if (!$valid): ?>
    <p style="color:red;">Invalid or expired reset link.</p>
<?php else: ?>
    <form method="POST">
        <input type="password" name="password" placeholder="New Password" required />
        <button type="submit">Reset Password</button>
    </form>
<?php endif; ?>
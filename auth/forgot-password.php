<?php
require '../config/db.php'; 

$message = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt2 = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt2->bind_param("sss", $token, $expires, $email);
        $stmt2->execute();

        $reset_link = "http://localhost/business-directory/auth/reset-password.php?token=" . $token;
    } else {
        $message = "No account found with that email.";
    }
}
?>

<?php if ($reset_link): ?>
    <div style="background:#d4edda; padding:15px; border-radius:8px; margin-bottom:15px;">
        <p><strong>Dev Mode:</strong> Click the link below to reset your password:</p>
        <a href="<?= $reset_link ?>"><?= $reset_link ?></a>
    </div>
<?php elseif ($message): ?>
    <div style="background:#f8d7da; padding:15px; border-radius:8px; margin-bottom:15px;">
        <?= $message ?>
    </div>
<?php endif; ?>
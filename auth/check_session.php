<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .debug { background: white; padding: 20px; border-radius: 8px; max-width: 600px; }
        h2 { color: #333; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="debug">
        <h2>Session Debug Information</h2>
        <h3>Session Data:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
        
        <h3>Checks:</h3>
        <pre>
user_id set: <?php echo isset($_SESSION['user_id']) ? 'YES' : 'NO'; ?>

user_type value: <?php echo $_SESSION['user_type'] ?? 'NOT SET'; ?>

role value: <?php echo $_SESSION['role'] ?? 'NOT SET'; ?>

Is admin (user_type): <?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') ? 'YES' : 'NO'; ?>

Is admin (role): <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'YES' : 'NO'; ?>
        </pre>
        
        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>
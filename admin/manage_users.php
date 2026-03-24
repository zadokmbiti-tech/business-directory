<?php
require_once '../config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$conn = getDBConnection();
$msg = '';

// Delete user
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header('Location: manage_users.php?msg=deleted');
    exit();
}

// Change role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $id   = (int)$_POST['user_id'];
    $role = $_POST['role'];
    if (in_array($role, ['user','business_owner','admin'])) {
        $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->bind_param("si", $role, $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: manage_users.php?msg=role_updated');
    exit();
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $id       = (int)$_POST['user_id'];
    $new_pw   = trim($_POST['new_password']);
    $confirm  = trim($_POST['confirm_password']);

    if (empty($new_pw) || strlen($new_pw) < 6) {
        $msg = 'error:Password must be at least 6 characters.';
    } elseif ($new_pw !== $confirm) {
        $msg = 'error:Passwords do not match.';
    } else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: manage_users.php?msg=pw_reset');
        exit();
    }
}

$users = $conn->query("SELECT u.*, COUNT(b.id) as biz_count FROM users u LEFT JOIN businesses b ON b.user_id = u.id GROUP BY u.id ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Zaddy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:#0d1b2a; --bg2:#112240; --surface:#1a2f4a; --border:#233554;
            --accent:#4facfe; --accent2:#00f2fe; --accent-soft:rgba(79,172,254,0.12);
            --text:#e8f0fe; --text2:#a8b8d8; --muted:#5a7a9a; --sidebar:256px;
            --green:#43e97b; --yellow:#f9ca24; --red:#ff6b6b;
        }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

        .sidebar { width:var(--sidebar); background:var(--bg2); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:100; }
        .sidebar-brand { padding:28px 22px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px; }
        .brand-icon { width:40px; height:40px; background:linear-gradient(135deg,var(--accent),var(--accent2)); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; box-shadow:0 4px 15px rgba(79,172,254,0.3); }
        .brand-text h2 { font-size:15px; font-weight:700; color:var(--text); }
        .brand-text p { font-size:11px; color:var(--muted); margin-top:1px; }
        .sidebar-nav { padding:16px 14px; flex:1; }
        .nav-label { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:1.2px; color:var(--muted); padding:0 10px; margin:20px 0 6px; }
        .nav-label:first-child { margin-top:4px; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:10px; color:var(--text2); text-decoration:none; font-size:13.5px; font-weight:500; transition:all 0.18s; margin-bottom:1px; }
        .nav-item:hover { background:var(--accent-soft); color:var(--accent); }
        .nav-item.active { background:linear-gradient(90deg,rgba(79,172,254,0.18),rgba(79,172,254,0.06)); color:var(--accent); border-left:3px solid var(--accent); padding-left:9px; }
        .nav-icon { font-size:15px; width:20px; text-align:center; }
        .sidebar-footer { padding:16px 14px 20px; border-top:1px solid var(--border); }
        .btn-logout { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:9px; background:rgba(255,107,107,0.08); border:1px solid rgba(255,107,107,0.2); color:var(--red); border-radius:10px; text-decoration:none; font-size:13px; font-weight:500; }
        .btn-logout:hover { background:rgba(255,107,107,0.15); }

        .main { margin-left:var(--sidebar); flex:1; padding:36px 32px; }
        .page-header { margin-bottom:28px; display:flex; align-items:center; justify-content:space-between; }
        .page-header h1 { font-size:24px; font-weight:800; letter-spacing:-0.5px; }
        .page-header p { color:var(--muted); font-size:14px; margin-top:4px; }

        .msg-success { background:rgba(67,233,123,0.1); border:1px solid rgba(67,233,123,0.3); color:var(--green); padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .msg-error   { background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.3); color:var(--red); padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; }

        .user-grid { display:grid; gap:16px; }

        .user-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:24px; transition:border-color 0.2s; }
        .user-card:hover { border-color:rgba(79,172,254,0.3); }

        .user-top { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:20px; }
        .user-avatar { width:46px; height:46px; border-radius:50%; background:linear-gradient(135deg,var(--accent),var(--accent2)); display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; color:var(--bg); flex-shrink:0; }
        .user-info { flex:1; }
        .user-info h3 { font-size:16px; font-weight:700; color:var(--text); margin-bottom:4px; }
        .user-info p { font-size:13px; color:var(--muted); }
        .user-meta { display:flex; gap:12px; flex-wrap:wrap; margin-top:8px; }
        .user-meta span { font-size:12px; color:var(--muted); }

        .role-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .role-user           { background:rgba(79,172,254,0.12);  color:var(--accent); }
        .role-business_owner { background:rgba(165,105,255,0.12); color:#a569ff; }
        .role-admin          { background:rgba(255,107,107,0.12); color:var(--red); }

        .user-actions { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

        .action-box { background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:12px; padding:16px; }
        .action-box h4 { font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.8px; color:var(--muted); margin-bottom:12px; }

        .role-form { display:flex; gap:8px; align-items:center; }
        select.role-select { flex:1; background:var(--bg); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:var(--text); font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; outline:none; }
        select.role-select option { background:var(--surface); }

        .pw-fields { display:flex; flex-direction:column; gap:8px; }
        .pw-fields input { background:var(--bg); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--text); font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; outline:none; }
        .pw-fields input:focus { border-color:var(--accent); }
        .pw-fields input::placeholder { color:var(--muted); }

        .btn-save { padding:8px 16px; border:none; border-radius:8px; font-family:'Plus Jakarta Sans',sans-serif; font-size:12px; font-weight:700; cursor:pointer; transition:all 0.2s; }
        .btn-blue { background:var(--accent-soft); color:var(--accent); border:1px solid rgba(79,172,254,0.3); }
        .btn-blue:hover { background:rgba(79,172,254,0.2); }
        .btn-green { background:rgba(67,233,123,0.1); color:var(--green); border:1px solid rgba(67,233,123,0.3); width:100%; margin-top:8px; padding:9px; }
        .btn-green:hover { background:rgba(67,233,123,0.2); }

        .delete-row { margin-top:14px; padding-top:14px; border-top:1px solid var(--border); }
        .btn-del { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:rgba(255,107,107,0.08); border:1px solid rgba(255,107,107,0.2); color:var(--red); border-radius:8px; text-decoration:none; font-size:12px; font-weight:600; transition:all 0.2s; }
        .btn-del:hover { background:rgba(255,107,107,0.18); }

        .biz-count { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; background:var(--accent-soft); color:var(--accent); font-size:11px; font-weight:600; }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">⚡</div>
        <div class="brand-text"><h2>Zaddy Admin</h2><p>Management Panel</p></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
        <a href="manage_businesses.php" class="nav-item"><span class="nav-icon">🏢</span> Businesses</a>
        <a href="manage_categories.php" class="nav-item"><span class="nav-icon">📁</span> Categories</a>
        <a href="manage_users.php" class="nav-item active"><span class="nav-icon">👥</span> Users</a>
        <div class="nav-label">Actions</div>
        <a href="add_business.php" class="nav-item"><span class="nav-icon">➕</span> Add Business</a>
        <a href="approve_businesses.php" class="nav-item"><span class="nav-icon">✅</span> Approvals</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout">⎋ Sign Out</a>
    </div>
</aside>

<main class="main">
    <div class="page-header">
        <div>
            <h1>Manage Users</h1>
            <p>View accounts, change roles and reset passwords</p>
        </div>
    </div>

    <?php
    $msg_param = $_GET['msg'] ?? '';
    $msg_map = [
        'deleted'      => ['type'=>'success','text'=>'✅ User deleted successfully.'],
        'role_updated' => ['type'=>'success','text'=>'✅ User role updated successfully.'],
        'pw_reset'     => ['type'=>'success','text'=>'✅ Password reset successfully.'],
    ];
    if (isset($msg_map[$msg_param])):
        $m = $msg_map[$msg_param];
    ?>
        <div class="msg-<?php echo $m['type']; ?>"><?php echo $m['text']; ?></div>
    <?php endif; ?>

    <?php if (str_starts_with($msg, 'error:')): ?>
        <div class="msg-error">⚠️ <?php echo htmlspecialchars(substr($msg, 6)); ?></div>
    <?php endif; ?>

    <div class="user-grid">
        <?php while($u = $users->fetch_assoc()): ?>
        <div class="user-card">
            <div class="user-top">
                <div class="user-avatar"><?php echo strtoupper(substr($u['name'], 0, 1)); ?></div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($u['name']); ?></h3>
                    <p><?php echo htmlspecialchars($u['email']); ?></p>
                    <div class="user-meta">
                        <span class="role-badge role-<?php echo $u['role'] ?? 'user'; ?>">
                            <?php
                            $rl = ['user'=>'User','business_owner'=>'Business Owner','admin'=>'Admin'];
                            echo $rl[$u['role'] ?? 'user'] ?? 'User';
                            ?>
                        </span>
                        <span class="biz-count">🏢 <?php echo $u['biz_count']; ?> business<?php echo $u['biz_count'] != 1 ? 'es' : ''; ?></span>
                        <span>📅 Joined <?php echo date('M d, Y', strtotime($u['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <div class="user-actions">
                <!-- Change Role -->
                <div class="action-box">
                    <h4>👤 Change Role</h4>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <div class="role-form">
                            <select name="role" class="role-select">
                                <option value="user"           <?php echo ($u['role']??'') === 'user'           ? 'selected' : ''; ?>>User</option>
                                <option value="business_owner" <?php echo ($u['role']??'') === 'business_owner' ? 'selected' : ''; ?>>Business Owner</option>
                                <option value="admin"          <?php echo ($u['role']??'') === 'admin'          ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <button type="submit" name="change_role" class="btn-save btn-blue">Save</button>
                        </div>
                    </form>
                </div>

                <!-- Reset Password -->
                <div class="action-box">
                    <h4>🔒 Reset Password</h4>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <div class="pw-fields">
                            <input type="password" name="new_password" placeholder="New password (min 6 chars)" required>
                            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                        </div>
                        <button type="submit" name="reset_password" class="btn-save btn-green">Reset Password</button>
                    </form>
                </div>
            </div>

            <div class="delete-row">
                <a href="?delete=<?php echo $u['id']; ?>" class="btn-del" onclick="return confirm('Delete <?php echo htmlspecialchars($u['name']); ?>? This cannot be undone.')">🗑 Delete Account</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</main>
</body>
</html>
<?php $conn->close(); ?>
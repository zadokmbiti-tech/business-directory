<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/dbconnection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn      = getDBConnection();
$user_id   = (int)$_SESSION['user_id'];

$u = $conn->prepare("SELECT * FROM users WHERE id = ?");
$u->bind_param("i", $user_id);
$u->execute();
$me = $u->get_result()->fetch_assoc();
$u->close();

$full_name    = $me['full_name'] ?? 'User';
$email        = $me['email'] ?? '';
$member_since = $me['created_at'] ?? '';
$role         = $me['role'] ?? 'user';
$is_admin     = ($role === 'admin');

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Own password change
    if (isset($_POST['change_password'])) {
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $con = $_POST['confirm_password'] ?? '';
        if (empty($cur) || empty($new) || empty($con)) {
            $err = 'All password fields are required.';
        } elseif ($new !== $con) {
            $err = 'New passwords do not match.';
        } elseif (strlen($new) < 6) {
            $err = 'Password must be at least 6 characters.';
        } elseif (!password_verify($cur, $me['password'])) {
            $err = 'Current password is incorrect.';
        } else {
            $hp = password_hash($new, PASSWORD_DEFAULT);
            $s  = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $s->bind_param("si", $hp, $user_id);
            $s->execute(); $s->close();
            $msg = 'Password updated successfully!';
        }
    }

    // Business status update (admin)
    if (isset($_POST['update_status']) && $is_admin) {
        $bid = (int)$_POST['business_id'];
        $st  = $_POST['new_status'] ?? '';
        if (in_array($st, ['pending','approved','rejected']) && $bid > 0) {
            $s = $conn->prepare("UPDATE businesses SET status=? WHERE id=?");
            $s->bind_param("si", $st, $bid);
            $s->execute(); $s->close();
            $msg = 'Business status updated.';
        }
    }


    // Delete business (cleans up status logs + image file)
    if (isset($_POST['delete_business'])) {
        $bid = (int)$_POST['delete_business_id'];
        if ($bid > 0) {
            if ($is_admin) {
                $chk = $conn->prepare("SELECT image FROM businesses WHERE id=?");
                $chk->bind_param("i", $bid);
            } else {
                $chk = $conn->prepare("SELECT image FROM businesses WHERE id=? AND user_id=?");
                $chk->bind_param("ii", $bid, $user_id);
            }
            $chk->execute();
            $biz_row = $chk->get_result()->fetch_assoc();
            $chk->close();
            if ($biz_row) {
                $sl = $conn->prepare("DELETE FROM business_status_logs WHERE business_id=?");
                $sl->bind_param("i", $bid);
                $sl->execute(); $sl->close();
                $s = $conn->prepare("DELETE FROM businesses WHERE id=?");
                $s->bind_param("i", $bid);
                if ($s->execute()) {
                    if (!empty($biz_row['image'])) {
                        $img_path = __DIR__ . '/../uploads/' . $biz_row['image'];
                        if (file_exists($img_path)) unlink($img_path);
                    }
                    $msg = 'Business deleted successfully.';
                } else { $err = 'Failed to delete business.'; }
                $s->close();
            } else { $err = 'Business not found or access denied.'; }
        }
    }
    // Admin: create user
    if (isset($_POST['create_user']) && $is_admin) {
        $nname = trim($_POST['new_full_name'] ?? '');
        $nusr  = strtolower(trim($_POST['new_username'] ?? ''));
        $neml  = trim($_POST['new_email'] ?? '');
        $nph   = trim($_POST['new_phone'] ?? '');
        $npw   = $_POST['new_password'] ?? '';
        $nrl   = in_array($_POST['new_role'] ?? '', ['user','admin']) ? $_POST['new_role'] : 'user';

        if (empty($nname) || empty($nusr) || empty($neml) || empty($npw)) {
            $err = 'Name, username, email and password are required.';
        } elseif (!filter_var($neml, FILTER_VALIDATE_EMAIL)) {
            $err = 'Invalid email address.';
        } elseif (strlen($npw) < 6) {
            $err = 'Password must be at least 6 characters.';
        } elseif (!preg_match('/^[a-z0-9_]+$/', $nusr)) {
            $err = 'Username: letters, numbers and underscores only.';
        } else {
            $chk = $conn->prepare("SELECT id FROM users WHERE email=? OR username=?");
            $chk->bind_param("ss", $neml, $nusr);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $err = 'Email or username already in use.';
            } else {
                $hp  = password_hash($npw, PASSWORD_DEFAULT);
                $ins = $conn->prepare("INSERT INTO users (full_name,username,email,phone,password,role) VALUES (?,?,?,?,?,?)");
                $ins->bind_param("ssssss", $nname, $nusr, $neml, $nph, $hp, $nrl);
                $ins->execute() ? $msg = "Account for '{$neml}' created." : $err = 'Failed: '.$ins->error;
                $ins->close();
            }
            $chk->close();
        }
    }

    // Admin: reset user password
    if (isset($_POST['reset_pw']) && $is_admin) {
        $tid = (int)$_POST['target_user_id'];
        $npw = $_POST['target_new_password'] ?? '';
        $cpw = $_POST['target_confirm_password'] ?? '';
        if ($tid <= 0 || empty($npw)) {
            $err = 'Select a user and enter a password.';
        } elseif ($npw !== $cpw) {
            $err = 'Passwords do not match.';
        } elseif (strlen($npw) < 6) {
            $err = 'Password must be at least 6 characters.';
        } else {
            $hp = password_hash($npw, PASSWORD_DEFAULT);
            $s  = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $s->bind_param("si", $hp, $tid);
            $s->execute() ? $msg = 'Password reset successfully.' : $err = 'Failed to reset password.';
            $s->close();
        }
    }

    // Admin: delete user
    if (isset($_POST['delete_user']) && $is_admin) {
        $did = (int)$_POST['delete_user_id'];
        if ($did === $user_id) {
            $err = 'You cannot delete your own account.';
        } elseif ($did > 0) {
            $s = $conn->prepare("DELETE FROM users WHERE id=?");
            $s->bind_param("i", $did);
            $s->execute() ? $msg = 'Account deleted.' : $err = 'Failed to delete.';
            $s->close();
        }
    }
}

$all_users = [];
if ($is_admin) {
    $r = $conn->query("SELECT id,full_name,username,email,phone,role,created_at FROM users ORDER BY created_at DESC");
    while ($row = $r->fetch_assoc()) $all_users[] = $row;
}

if ($is_admin) {
    $bs = $conn->prepare("SELECT b.*,c.name as cat_name,COALESCE(u.full_name,u.email) as owner FROM businesses b LEFT JOIN categories c ON b.category_id=c.id LEFT JOIN users u ON b.user_id=u.id ORDER BY b.created_at DESC");
} else {
    $bs = $conn->prepare("SELECT b.*,c.name as cat_name FROM businesses b LEFT JOIN categories c ON b.category_id=c.id WHERE b.user_id=? ORDER BY b.created_at DESC");
    $bs->bind_param("i", $user_id);
}
$bs->execute();
$biz_result = $bs->get_result();
$biz_list   = [];
$total=$pending=$approved=$rejected=0;
while ($b = $biz_result->fetch_assoc()) {
    $biz_list[] = $b; $total++;
    if ($b['status']==='pending')      $pending++;
    elseif ($b['status']==='approved') $approved++;
    elseif ($b['status']==='rejected') $rejected++;
}
$bs->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Zaddy Business Directory</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f5f5f5}
.header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:20px 0}
.header-content{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;justify-content:space-between;align-items:center}
.header h1{font-size:22px}
.header-nav a{color:white;text-decoration:none;font-size:14px;margin-left:20px}
.header-nav a:hover{opacity:.8}
.container{max-width:1200px;margin:28px auto;padding:0 20px}
.card{background:white;border-radius:10px;padding:26px 28px;box-shadow:0 2px 10px rgba(0,0,0,.07);margin-bottom:22px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:18px;margin-bottom:22px}
.stat{background:white;border-radius:10px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.07)}
.stat-label{color:#888;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px}
.stat-val{font-size:28px;font-weight:800;color:#667eea}
.tab-nav{display:flex;gap:8px;margin-bottom:22px;flex-wrap:wrap}
.tab-btn{padding:9px 20px;border-radius:7px;font-size:14px;font-weight:600;cursor:pointer;border:2px solid #667eea;background:white;color:#667eea;font-family:inherit;transition:all .2s}
.tab-btn.active{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-color:transparent}
.tab-panel{display:none}
.tab-panel.active{display:block}
table{width:100%;border-collapse:collapse}
th{padding:12px 14px;text-align:left;font-size:12px;font-weight:700;text-transform:uppercase;color:#999;background:#fafafa;border-bottom:2px solid #f0f0f0}
td{padding:12px 14px;font-size:14px;border-bottom:1px solid #f5f5f5;color:#555;vertical-align:middle}
tr:hover td{background:#fafafa}
.badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge-pending{background:#fff3cd;color:#856404}
.badge-approved{background:#d4edda;color:#155724}
.badge-rejected{background:#f8d7da;color:#721c24}
.badge-user{background:#e3f2fd;color:#1976d2}
.badge-admin{background:#fff3e0;color:#e65100}
.btn{padding:10px 20px;border:none;border-radius:7px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-block}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:5px;border:none;cursor:pointer;font-weight:600;font-family:inherit;text-decoration:none;display:inline-block}
.btn-blue{background:#667eea;color:white}
.btn-green{background:#28a745;color:white}
.btn-red{background:#e74c3c;color:white}
.btn-red:hover{background:#c0392b}
.fg{display:flex;flex-direction:column;gap:5px}
.fg label{font-size:11px;font-weight:700;color:#777;text-transform:uppercase;letter-spacing:.5px}
.fg input,.fg select{padding:10px 12px;border:2px solid #e8e8e8;border-radius:7px;font-size:14px;font-family:inherit;outline:none;width:100%}
.fg input:focus,.fg select:focus{border-color:#667eea}
.grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:14px}
@media(max-width:650px){.grid3{grid-template-columns:1fr}}
.msg-ok{background:#efe;border:1px solid #cfc;border-radius:7px;padding:11px 15px;margin-bottom:16px;color:#2d5016;font-size:14px}
.msg-err{background:#fee;border:1px solid #fcc;border-radius:7px;padding:11px 15px;margin-bottom:16px;color:#c33;font-size:14px}
.role-badge{padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;display:inline-block}
.info-row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f0f0f0;font-size:14px}
.info-row:last-child{border-bottom:none}
.pw-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:14px}
@media(max-width:650px){.pw-grid{grid-template-columns:1fr}}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal{background:white;border-radius:12px;padding:28px;max-width:400px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,.2)}
.modal h4{font-size:17px;color:#333;margin-bottom:8px}
.modal p{font-size:14px;color:#666;margin-bottom:20px}
.modal-actions{display:flex;gap:10px;justify-content:flex-end}
.btn-cancel{padding:9px 20px;border:2px solid #ddd;background:white;border-radius:7px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit}
</style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <h1>Zaddy Business Directory</h1>
        <nav class="header-nav">
            <a href="../index.php">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <?php if ($is_admin): ?><a href="admin_dashboard.php">Admin Panel</a><?php endif; ?>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</div>

<div class="container">

    <?php if ($msg): ?><div class="msg-ok">&#10003; <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="msg-err">&#9888; <?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <div class="card" style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <h2 style="font-size:24px;color:#333;margin-bottom:5px">Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h2>
            <p style="color:#888;font-size:14px">Here's what's happening with your account.</p>
        </div>
        <span class="role-badge" style="background:<?php echo $is_admin?'#fff3e0':'#e3f2fd'; ?>;color:<?php echo $is_admin?'#e65100':'#1976d2'; ?>">
            <?php echo $is_admin?'Admin':'User'; ?>
        </span>
    </div>

    <div class="stats-grid">
        <div class="stat"><div class="stat-label">Total Businesses</div><div class="stat-val"><?php echo $total; ?></div></div>
        <div class="stat"><div class="stat-label">Pending</div><div class="stat-val" style="color:#d4a017"><?php echo $pending; ?></div></div>
        <div class="stat"><div class="stat-label">Approved</div><div class="stat-val" style="color:#27ae60"><?php echo $approved; ?></div></div>
        <div class="stat"><div class="stat-label">Rejected</div><div class="stat-val" style="color:#e74c3c"><?php echo $rejected; ?></div></div>
        <?php if ($is_admin): ?>
        <div class="stat"><div class="stat-label">Total Users</div><div class="stat-val"><?php echo count($all_users); ?></div></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3 style="font-size:16px;font-weight:700;color:#333;margin-bottom:14px">Quick Actions</h3>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="add-business.php" class="btn btn-primary">+ Add Business</a>
            <a href="../index.php" class="btn" style="background:white;border:2px solid #667eea;color:#667eea">Browse Directory</a>
        </div>
    </div>

    <div class="tab-nav">
        <button class="tab-btn active" onclick="switchTab('businesses',this)">My Businesses</button>
        <?php if ($is_admin): ?>
        <button class="tab-btn" onclick="switchTab('users',this)">User Management</button>
        <?php endif; ?>
        <button class="tab-btn" onclick="switchTab('account',this)">My Account</button>
    </div>

    <div class="tab-panel active" id="tab-businesses">
        <div class="card" style="padding:0;overflow:hidden">
            <?php if (empty($biz_list)): ?>
                <div style="padding:50px;text-align:center;color:#aaa">
                    <div style="font-size:48px;margin-bottom:12px">&#127970;</div>
                    <p style="margin-bottom:16px">No businesses listed yet.</p>
                    <a href="add-business.php" class="btn btn-primary">Add Your First Business</a>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Business</th><th>Category</th>
                        <?php if ($is_admin): ?><th>Owner</th><?php endif; ?>
                        <th>Phone</th><th>Status</th><th>Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($biz_list as $b): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($b['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($b['cat_name'] ?? '-'); ?></td>
                        <?php if ($is_admin): ?><td><?php echo htmlspecialchars($b['owner'] ?? '-'); ?></td><?php endif; ?>
                        <td><?php echo htmlspecialchars($b['phone'] ?? '-'); ?></td>
                        <td><span class="badge badge-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($b['created_at'])); ?></td>
                        <td style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="edit-business.php?id=<?php echo $b['id']; ?>" class="btn-sm btn-blue">Edit</a>
                            <a href="../business_details.php?id=<?php echo $b['id']; ?>" class="btn-sm btn-green" target="_blank">View</a>
                            <button class="btn-sm btn-red" onclick="confirmDeleteBusiness(<?php echo $b['id']; ?>,'<?php echo htmlspecialchars(addslashes($b['name'])); ?>')">Delete</button>
                            <?php if ($is_admin): ?>
                            <form method="POST" style="margin:0">
                                <input type="hidden" name="business_id" value="<?php echo $b['id']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="new_status" onchange="this.form.submit()" style="padding:4px 7px;border:2px solid #ddd;border-radius:5px;font-size:12px;cursor:pointer">
                                    <option value="">Status</option>
                                    <option value="pending"  <?php echo $b['status']==='pending'  ?'selected':''; ?>>Pending</option>
                                    <option value="approved" <?php echo $b['status']==='approved' ?'selected':''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $b['status']==='rejected' ?'selected':''; ?>>Rejected</option>
                                </select>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($is_admin): ?>
    <div class="tab-panel" id="tab-users">

        <div class="card">
            <h3 style="font-size:16px;font-weight:700;color:#333;margin-bottom:16px">Create New Account</h3>
            <form method="POST">
                <div class="grid3">
                    <div class="fg"><label>Full Name *</label><input type="text" name="new_full_name" placeholder="John Doe" required></div>
                    <div class="fg"><label>Username *</label><input type="text" name="new_username" placeholder="johndoe" required></div>
                    <div class="fg"><label>Email *</label><input type="email" name="new_email" placeholder="john@email.com" required></div>
                </div>
                <div class="grid3">
                    <div class="fg"><label>Phone</label><input type="tel" name="new_phone" placeholder="Optional"></div>
                    <div class="fg"><label>Password *</label><input type="password" name="new_password" placeholder="Min 6 characters" required></div>
                    <div class="fg"><label>Role *</label>
                        <select name="new_role">
                            <option value="user">User (Business Owner)</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_user" class="btn btn-primary">Create Account</button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-size:16px;font-weight:700;color:#333;margin-bottom:16px">Reset a User's Password</h3>
            <form method="POST">
                <div class="grid3">
                    <div class="fg"><label>Select User *</label>
                        <select name="target_user_id" required>
                            <option value="">-- Choose user --</option>
                            <?php foreach ($all_users as $u): ?>
                                <?php if ($u['id'] != $user_id): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name'].' ('.$u['email'].')'); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label>New Password *</label><input type="password" name="target_new_password" placeholder="Min 6 characters" required></div>
                    <div class="fg"><label>Confirm Password *</label><input type="password" name="target_confirm_password" placeholder="Repeat password" required></div>
                </div>
                <button type="submit" name="reset_pw" class="btn btn-primary">Reset Password</button>
            </form>
        </div>

        <div class="card" style="padding:0;overflow:hidden">
            <div style="padding:20px 24px;border-bottom:2px solid #f0f0f0">
                <h3 style="font-size:16px;font-weight:700;color:#333">All Accounts (<?php echo count($all_users); ?>)</h3>
            </div>
            <table>
                <thead>
                    <tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $i => $u): ?>
                    <tr>
                        <td><?php echo $i+1; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['username'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                        <td><span class="badge badge-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <?php if ($u['id'] != $user_id): ?>
                                <button class="btn-sm btn-red" onclick="confirmDelete(<?php echo $u['id']; ?>,'<?php echo htmlspecialchars(addslashes($u['full_name'])); ?>')">Delete</button>
                            <?php else: ?>
                                <span style="font-size:12px;color:#bbb">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="tab-panel" id="tab-account">
        <div class="card">
            <h3 style="font-size:16px;font-weight:700;color:#333;margin-bottom:6px">Change My Password</h3>
            <p style="font-size:13px;color:#aaa;margin-bottom:18px">Update your own account password.</p>
            <form method="POST">
                <div class="pw-grid">
                    <div class="fg"><label>Current Password</label><input type="password" name="current_password" placeholder="Current password" required></div>
                    <div class="fg"><label>New Password</label><input type="password" name="new_password" placeholder="Min 6 characters" required></div>
                    <div class="fg"><label>Confirm Password</label><input type="password" name="confirm_password" placeholder="Repeat new password" required></div>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-size:16px;font-weight:700;color:#333;margin-bottom:18px">Account Information</h3>
            <div class="info-row"><span style="color:#888;font-weight:600">Full Name</span><span><?php echo htmlspecialchars($full_name); ?></span></div>
            <div class="info-row"><span style="color:#888;font-weight:600">Email</span><span><?php echo htmlspecialchars($email); ?></span></div>
            <div class="info-row"><span style="color:#888;font-weight:600">Role</span>
                <span class="role-badge" style="background:<?php echo $is_admin?'#fff3e0':'#e3f2fd'; ?>;color:<?php echo $is_admin?'#e65100':'#1976d2'; ?>">
                    <?php echo $is_admin?'Admin':'User'; ?>
                </span>
            </div>
            <div class="info-row"><span style="color:#888;font-weight:600">Member Since</span><span><?php echo date('M d, Y', strtotime($member_since)); ?></span></div>
            <div class="info-row"><span style="color:#888;font-weight:600">Businesses Listed</span><span><?php echo $total; ?></span></div>
        </div>
    </div>

</div>

<!-- Delete User Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h4>Delete Account</h4>
        <p id="deleteMsg">Are you sure? This cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
            <form method="POST" style="margin:0">
                <input type="hidden" name="delete_user_id" id="deleteId">
                <button type="submit" name="delete_user" class="btn-sm btn-red" style="padding:9px 18px;font-size:14px">Yes, Delete</button>
            </form>
        </div>
    </div>
</div>

<!-- Delete Business Modal -->
<div class="modal-overlay" id="deleteBusinessModal">
    <div class="modal">
        <h4>Delete Business</h4>
        <p id="deleteBusinessMsg">Are you sure you want to delete this business? This cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal('deleteBusinessModal')">Cancel</button>
            <form method="POST" style="margin:0">
                <input type="hidden" name="delete_business_id" id="deleteBusinessId">
                <button type="submit" name="delete_business" class="btn-sm btn-red" style="padding:9px 18px;font-size:14px">Yes, Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteMsg').textContent = 'Delete account for "' + name + '"? This cannot be undone.';
    document.getElementById('deleteModal').classList.add('active');
}
function confirmDeleteBusiness(id, name) {
    document.getElementById('deleteBusinessId').value = id;
    document.getElementById('deleteBusinessMsg').textContent = 'Delete "' + name + '"? This cannot be undone.';
    document.getElementById('deleteBusinessModal').classList.add('active');
}
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal('deleteModal');
});
document.getElementById('deleteBusinessModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal('deleteBusinessModal');
});
</script>
</body>
</html>

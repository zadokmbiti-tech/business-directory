<?php
require_once '../config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$conn = getDBConnection();

if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $conn->query("UPDATE businesses SET status='approved' WHERE id=$id");
    header('Location: approve_businesses.php?msg=approved');
    exit();
}

if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $conn->query("UPDATE businesses SET status='rejected' WHERE id=$id");
    header('Location: approve_businesses.php?msg=rejected');
    exit();
}

$pending = $conn->query("SELECT b.*, c.name as cat_name FROM businesses b LEFT JOIN categories c ON b.category_id = c.id WHERE b.status='pending' ORDER BY b.created_at DESC");
$pending_count = $pending->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approvals - Zaddy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0d1b2a; --bg2: #112240; --surface: #1a2f4a;
            --border: #233554; --accent: #4facfe; --accent2: #00f2fe;
            --accent-soft: rgba(79,172,254,0.12); --text: #e8f0fe;
            --text2: #a8b8d8; --muted: #5a7a9a; --sidebar: 256px;
            --green: #43e97b; --yellow: #f9ca24; --red: #ff6b6b;
        }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

        .sidebar { width:var(--sidebar); background:var(--bg2); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:100; }
        .sidebar-brand { padding:28px 22px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px; }
        .brand-icon { width:40px; height:40px; background:linear-gradient(135deg,var(--accent),var(--accent2)); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; box-shadow:0 4px 15px rgba(79,172,254,0.3); }
        .brand-text h2 { font-size:15px; font-weight:700; color:var(--text); letter-spacing:-0.3px; }
        .brand-text p { font-size:11px; color:var(--muted); margin-top:1px; }
        .sidebar-nav { padding:16px 14px; flex:1; overflow-y:auto; }
        .nav-label { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:1.2px; color:var(--muted); padding:0 10px; margin:20px 0 6px; }
        .nav-label:first-child { margin-top:4px; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:10px; color:var(--text2); text-decoration:none; font-size:13.5px; font-weight:500; transition:all 0.18s; margin-bottom:1px; }
        .nav-item:hover { background:var(--accent-soft); color:var(--accent); }
        .nav-item.active { background:linear-gradient(90deg,rgba(79,172,254,0.18),rgba(79,172,254,0.06)); color:var(--accent); border-left:3px solid var(--accent); padding-left:9px; }
        .nav-icon { font-size:15px; width:20px; text-align:center; flex-shrink:0; }
        .nav-badge { margin-left:auto; background:var(--accent); color:var(--bg); font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; }
        .sidebar-footer { padding:16px 14px 20px; border-top:1px solid var(--border); }
        .btn-logout { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:9px; background:rgba(255,107,107,0.08); border:1px solid rgba(255,107,107,0.2); color:var(--red); border-radius:10px; text-decoration:none; font-size:13px; font-weight:500; transition:all 0.2s; }
        .btn-logout:hover { background:rgba(255,107,107,0.15); }

        .main { margin-left:var(--sidebar); flex:1; padding:36px 32px; }
        .page-header { margin-bottom:28px; }
        .page-header h1 { font-size:24px; font-weight:800; letter-spacing:-0.5px; }
        .page-header p { color:var(--muted); font-size:14px; margin-top:4px; }

        .msg { padding:12px 16px; border-radius:10px; margin-bottom:24px; font-size:14px; }
        .msg-approved { background:rgba(67,233,123,0.1); border:1px solid rgba(67,233,123,0.3); color:var(--green); }
        .msg-rejected { background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.3); color:var(--red); }

        .business-cards { display:grid; gap:16px; }

        .business-card {
            background:var(--surface); border:1px solid var(--border);
            border-radius:16px; padding:24px;
            display:flex; justify-content:space-between;
            align-items:flex-start; gap:20px;
            transition:border-color 0.2s;
        }
        .business-card:hover { border-color:rgba(79,172,254,0.3); }

        .business-info { flex:1; }
        .business-info h3 { font-size:18px; font-weight:700; margin-bottom:8px; color:var(--text); }

        .cat-tag {
            display:inline-block; padding:3px 12px;
            background:var(--accent-soft); border-radius:20px;
            font-size:12px; font-weight:600; color:var(--accent);
            margin-bottom:12px;
        }

        .description { font-size:14px; color:var(--text2); line-height:1.6; margin-bottom:14px; }

        .meta { display:flex; gap:20px; flex-wrap:wrap; }
        .meta span { font-size:13px; color:var(--muted); display:flex; align-items:center; gap:5px; }

        .submitted { font-size:12px; color:var(--muted); margin-top:12px; }

        .business-actions { display:flex; flex-direction:column; gap:10px; flex-shrink:0; min-width:130px; }

        .btn-approve {
            padding:11px 20px;
            background:rgba(67,233,123,0.12); border:1px solid rgba(67,233,123,0.3);
            color:var(--green); border-radius:10px; text-decoration:none;
            font-size:13px; font-weight:600; text-align:center; transition:all 0.2s;
        }
        .btn-approve:hover { background:rgba(67,233,123,0.22); }

        .btn-reject {
            padding:11px 20px;
            background:rgba(255,107,107,0.08); border:1px solid rgba(255,107,107,0.2);
            color:var(--red); border-radius:10px; text-decoration:none;
            font-size:13px; font-weight:600; text-align:center; transition:all 0.2s;
        }
        .btn-reject:hover { background:rgba(255,107,107,0.18); }

        .empty { text-align:center; padding:80px 20px; color:var(--muted); }
        .empty-icon { font-size:56px; margin-bottom:16px; }
        .empty p { font-size:15px; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">⚡</div>
            <div class="brand-text">
                <h2>Zaddy Admin</h2>
                <p>Management Panel</p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main</div>
            <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
            <a href="manage_businesses.php" class="nav-item"><span class="nav-icon">🏢</span> Businesses</a>
            <a href="manage_categories.php" class="nav-item"><span class="nav-icon">📁</span> Categories</a>
            <a href="manage_users.php" class="nav-item"><span class="nav-icon">👥</span> Users</a>
            <div class="nav-label">Actions</div>
            <a href="add_business.php" class="nav-item"><span class="nav-icon">➕</span> Add Business</a>
            <a href="approve_businesses.php" class="nav-item active">
                <span class="nav-icon">✅</span> Approvals
                <?php if($pending_count > 0): ?>
                    <span class="nav-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">⎋ Sign Out</a>
        </div>
    </aside>

    <main class="main">
        <div class="page-header">
            <h1>Business Approvals</h1>
            <p><?php echo $pending_count; ?> business<?php echo $pending_count !== 1 ? 'es' : ''; ?> pending review</p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg msg-<?php echo htmlspecialchars($_GET['msg']); ?>">
                <?php echo $_GET['msg'] === 'approved' ? '✅ Business approved and is now live!' : '❌ Business has been rejected.'; ?>
            </div>
        <?php endif; ?>

        <div class="business-cards">
            <?php if ($pending_count === 0): ?>
                <div class="empty">
                    <div class="empty-icon">🎉</div>
                    <p>No pending businesses — you're all caught up!</p>
                </div>
            <?php else: ?>
            <?php while($b = $pending->fetch_assoc()): ?>
                <div class="business-card">
                    <div class="business-info">
                        <h3><?php echo htmlspecialchars($b['name']); ?></h3>
                        <div><span class="cat-tag">📁 <?php echo htmlspecialchars($b['cat_name'] ?? 'Uncategorized'); ?></span></div>
                        <p class="description"><?php echo htmlspecialchars($b['description'] ?? 'No description provided.'); ?></p>
                        <div class="meta">
                            <?php if($b['address']): ?><span>📍 <?php echo htmlspecialchars($b['address']); ?></span><?php endif; ?>
                            <?php if($b['phone']): ?><span>📞 <?php echo htmlspecialchars($b['phone']); ?></span><?php endif; ?>
                            <?php if($b['email']): ?><span>✉️ <?php echo htmlspecialchars($b['email']); ?></span><?php endif; ?>
                            <?php if($b['website']): ?><span>🌐 <?php echo htmlspecialchars($b['website']); ?></span><?php endif; ?>
                        </div>
                        <div class="submitted">📅 Submitted: <?php echo date('M d, Y', strtotime($b['created_at'])); ?></div>
                    </div>
                    <div class="business-actions">
                        <a href="?approve=<?php echo $b['id']; ?>" class="btn-approve">✓ Approve</a>
                        <a href="?reject=<?php echo $b['id']; ?>" class="btn-reject" onclick="return confirm('Reject this business?')">✗ Reject</a>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>
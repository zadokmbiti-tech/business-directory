<?php
require_once '../config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$conn = getDBConnection();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM businesses WHERE id = $id");
    header('Location: manage_businesses.php?msg=deleted');
    exit();
}

// Handle status change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'];
    if (in_array($status, ['open','closed','pending','approved','rejected'])) {
        $conn->query("UPDATE businesses SET status='$status' WHERE id=$id");
    }
    header('Location: manage_businesses.php?msg=updated');
    exit();
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$where = $filter ? "WHERE b.status='$filter'" : '';
$businesses = $conn->query("SELECT b.*, c.name as cat_name FROM businesses b LEFT JOIN categories c ON b.category_id = c.id $where ORDER BY b.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Businesses - Zaddy Admin</title>
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
        .sidebar-footer { padding:16px 14px 20px; border-top:1px solid var(--border); }
        .btn-logout { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:9px; background:rgba(255,107,107,0.08); border:1px solid rgba(255,107,107,0.2); color:var(--red); border-radius:10px; text-decoration:none; font-size:13px; font-weight:500; transition:all 0.2s; }
        .btn-logout:hover { background:rgba(255,107,107,0.15); }

        .main { margin-left:var(--sidebar); flex:1; padding:36px 32px; }
        .page-header { margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; }
        .page-header h1 { font-size:24px; font-weight:800; letter-spacing:-0.5px; }
        .btn-add { padding:10px 20px; background:linear-gradient(135deg,var(--accent),var(--accent2)); border:none; border-radius:10px; color:var(--bg); font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; font-weight:700; text-decoration:none; box-shadow:0 4px 15px rgba(79,172,254,0.25); }
        .btn-add:hover { opacity:0.9; }

        .filters { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
        .filter-btn { padding:7px 16px; border-radius:20px; border:1px solid var(--border); background:transparent; color:var(--muted); font-size:13px; cursor:pointer; text-decoration:none; transition:all 0.2s; font-family:'Plus Jakarta Sans',sans-serif; }
        .filter-btn:hover, .filter-btn.active { background:var(--accent-soft); border-color:var(--accent); color:var(--accent); font-weight:600; }

        .msg-success { background:rgba(67,233,123,0.1); border:1px solid rgba(67,233,123,0.3); color:var(--green); padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; }

        .section-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:12px 20px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.8px; color:var(--muted); background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); }
        td { padding:14px 20px; font-size:13.5px; border-bottom:1px solid var(--border); vertical-align:middle; color:var(--text2); }
        td strong { color:var(--text); font-weight:600; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:rgba(79,172,254,0.03); }

        .badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge::before { content:'●'; font-size:7px; }
        .badge-open     { background:rgba(67,233,123,0.12);  color:var(--green); }
        .badge-closed   { background:rgba(90,122,154,0.15);  color:var(--muted); }
        .badge-pending  { background:rgba(249,202,36,0.12);  color:var(--yellow); }
        .badge-approved { background:rgba(67,233,123,0.12);  color:var(--green); }
        .badge-rejected { background:rgba(255,107,107,0.12); color:var(--red); }

        .action-links { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
        .action-links a { text-decoration:none; font-size:12px; padding:4px 10px; border-radius:6px; border:1px solid transparent; transition:all 0.18s; font-weight:500; }
        .action-links a.edit   { color:var(--accent); border-color:rgba(79,172,254,0.2); background:rgba(79,172,254,0.06); }
        .action-links a.edit:hover { background:rgba(79,172,254,0.15); }
        .action-links a.del    { color:var(--red); border-color:rgba(255,107,107,0.2); background:rgba(255,107,107,0.06); }
        .action-links a.del:hover { background:rgba(255,107,107,0.15); }
        .action-links a.warn   { color:var(--yellow); border-color:rgba(249,202,36,0.2); background:rgba(249,202,36,0.06); }
        .action-links a.warn:hover { background:rgba(249,202,36,0.15); }
        .action-links a.success { color:var(--green); border-color:rgba(67,233,123,0.2); background:rgba(67,233,123,0.06); }
        .action-links a.success:hover { background:rgba(67,233,123,0.15); }

        .empty { text-align:center; padding:48px; color:var(--muted); font-size:14px; }
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
            <a href="manage_businesses.php" class="nav-item active"><span class="nav-icon">🏢</span> Businesses</a>
            <a href="manage_categories.php" class="nav-item"><span class="nav-icon">📁</span> Categories</a>
            <a href="manage_users.php" class="nav-item"><span class="nav-icon">👥</span> Users</a>
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
            <h1>Manage Businesses</h1>
            <a href="add_business.php" class="btn-add">+ Add Business</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg-success">✅ Business <?php echo htmlspecialchars($_GET['msg']); ?> successfully!</div>
        <?php endif; ?>

        <div class="filters">
            <a href="manage_businesses.php" class="filter-btn <?php echo !$filter ? 'active' : ''; ?>">All</a>
            <a href="?filter=open"     class="filter-btn <?php echo $filter=='open'     ? 'active' : ''; ?>">🟢 Open</a>
            <a href="?filter=closed"   class="filter-btn <?php echo $filter=='closed'   ? 'active' : ''; ?>">🔴 Closed</a>
            <a href="?filter=pending"  class="filter-btn <?php echo $filter=='pending'  ? 'active' : ''; ?>">⏳ Pending</a>
            <a href="?filter=approved" class="filter-btn <?php echo $filter=='approved' ? 'active' : ''; ?>">👍 Approved</a>
            <a href="?filter=rejected" class="filter-btn <?php echo $filter=='rejected' ? 'active' : ''; ?>">❌ Rejected</a>
        </div>

        <div class="section-card">
            <table>
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Category</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($businesses->num_rows === 0): ?>
                        <tr><td colspan="5" class="empty">No businesses found.</td></tr>
                    <?php else: ?>
                    <?php while($b = $businesses->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($b['name']); ?></strong><br>
                            <small style="color:var(--muted)"><?php echo htmlspecialchars(substr($b['description'] ?? '', 0, 60)); ?>...</small>
                        </td>
                        <td><?php echo htmlspecialchars($b['cat_name'] ?? 'Uncategorized'); ?></td>
                        <td>
                            <?php echo htmlspecialchars($b['phone'] ?? '-'); ?><br>
                            <small style="color:var(--muted)"><?php echo htmlspecialchars($b['address'] ?? ''); ?></small>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $b['status']; ?>">
                                <?php echo ucfirst($b['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-links">
                                <a href="edit_business.php?id=<?php echo $b['id']; ?>" class="edit">Edit</a>

                                <?php if($b['status'] === 'open'): ?>
                                    <a href="?status=closed&id=<?php echo $b['id']; ?>" class="warn">🔴 Close</a>
                                <?php elseif($b['status'] === 'closed'): ?>
                                    <a href="?status=open&id=<?php echo $b['id']; ?>" class="success">🟢 Open</a>
                                <?php endif; ?>

                                <?php if($b['status'] === 'pending'): ?>
                                    <a href="?status=approved&id=<?php echo $b['id']; ?>" class="success">✅ Approve</a>
                                    <a href="?status=rejected&id=<?php echo $b['id']; ?>" class="del">❌ Reject</a>
                                <?php endif; ?>

                                <a href="?delete=<?php echo $b['id']; ?>" class="del" onclick="return confirm('Delete this business?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>
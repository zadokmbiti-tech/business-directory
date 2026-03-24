<?php
require_once '../config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$conn = getDBConnection();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id=$id");
    header('Location: manage_categories.php?msg=deleted');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $icon = mysqli_real_escape_string($conn, trim($_POST['icon']));
    if ($name) {
        $conn->query("INSERT INTO categories (name, icon) VALUES ('$name', '$icon')");
        header('Location: manage_categories.php?msg=added');
        exit();
    }
}

$categories = $conn->query("SELECT c.*, COUNT(b.id) as biz_count FROM categories c LEFT JOIN businesses b ON c.id = b.category_id GROUP BY c.id ORDER BY c.name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - Zaddy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg:#0a0a0f; --surface:#13131a; --border:#1e1e2e; --accent:#f0c040; --accent2:#e07820; --text:#f0ede8; --muted:#6b6880; --sidebar:240px; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }
        .sidebar { width:var(--sidebar); background:var(--surface); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; }
        .sidebar-brand { padding:24px 20px; border-bottom:1px solid var(--border); }
        .sidebar-brand .icon { width:36px; height:36px; background:linear-gradient(135deg,var(--accent),var(--accent2)); border-radius:10px; display:inline-flex; align-items:center; justify-content:center; font-size:16px; margin-bottom:10px; }
        .sidebar-brand h2 { font-family:'Syne',sans-serif; font-size:16px; font-weight:800; }
        .sidebar-nav { padding:16px 12px; flex:1; }
        .nav-label { font-size:10px; text-transform:uppercase; letter-spacing:1px; color:var(--muted); padding:0 8px; margin:16px 0 8px; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; color:var(--muted); text-decoration:none; font-size:14px; font-weight:500; transition:all 0.2s; margin-bottom:2px; }
        .nav-item:hover,.nav-item.active { background:rgba(240,192,64,0.08); color:var(--accent); }
        .nav-item .icon { font-size:16px; width:20px; text-align:center; }
        .sidebar-footer { padding:16px 12px; border-top:1px solid var(--border); }
        .btn-logout { display:block; width:100%; padding:9px; background:rgba(220,60,60,0.1); border:1px solid rgba(220,60,60,0.2); color:#f87171; border-radius:8px; text-align:center; text-decoration:none; font-size:13px; }
        .main { margin-left:var(--sidebar); flex:1; padding:32px; display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start; }
        .page-header { margin-bottom:24px; grid-column:1/-1; }
        .page-header h1 { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; }
        .msg { padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; grid-column:1/-1; }
        .msg-added,.msg-deleted { background:rgba(52,211,153,0.1); border:1px solid rgba(52,211,153,0.3); color:#34d399; }
        .section-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
        .form-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:24px; }
        .form-card h3 { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; margin-bottom:20px; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:12px 20px; font-size:11px; text-transform:uppercase; letter-spacing:0.8px; color:var(--muted); background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); }
        td { padding:14px 20px; font-size:14px; border-bottom:1px solid var(--border); }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:rgba(255,255,255,0.02); }
        .count-badge { display:inline-block; padding:3px 10px; background:rgba(240,192,64,0.1); border-radius:20px; font-size:12px; color:var(--accent); }
        .del-link { color:#f87171; text-decoration:none; font-size:12px; padding:4px 10px; border-radius:6px; border:1px solid rgba(248,113,113,0.2); }
        .del-link:hover { background:rgba(248,113,113,0.1); }
        label { display:block; font-size:12px; font-weight:500; color:var(--muted); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:8px; }
        input { width:100%; background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:11px 14px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:14px; outline:none; margin-bottom:16px; transition:border-color 0.2s; }
        input:focus { border-color:var(--accent); }
        .btn-submit { width:100%; padding:12px; background:linear-gradient(135deg,var(--accent),var(--accent2)); border:none; border-radius:10px; color:#0a0a0f; font-family:'Syne',sans-serif; font-size:14px; font-weight:700; cursor:pointer; }
        .hint { font-size:11px; color:var(--muted); margin-top:-12px; margin-bottom:16px; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="icon">⚡</div>
            <h2>Zaddy Admin</h2>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main</div>
            <a href="dashboard.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
            <a href="manage_businesses.php" class="nav-item"><span class="icon">🏢</span> Businesses</a>
            <a href="manage_categories.php" class="nav-item active"><span class="icon">📁</span> Categories</a>
            <a href="manage_users.php" class="nav-item"><span class="icon">👥</span> Users</a>
            <div class="nav-label">Actions</div>
            <a href="add_business.php" class="nav-item"><span class="icon">➕</span> Add Business</a>
            <a href="approve_businesses.php" class="nav-item"><span class="icon">✅</span> Approvals</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </aside>

    <main class="main">
        <div class="page-header">
            <h1>Manage Categories</h1>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg msg-<?php echo $_GET['msg']; ?>">✅ Category <?php echo $_GET['msg']; ?> successfully!</div>
        <?php endif; ?>

        <div class="section-card">
            <table>
                <thead>
                    <tr><th>Category</th><th>Icon</th><th>Businesses</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php while($c = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($c['icon'] ?? '-'); ?></td>
                        <td><span class="count-badge"><?php echo $c['biz_count']; ?> businesses</span></td>
                        <td>
                            <a href="?delete=<?php echo $c['id']; ?>" class="del-link" onclick="return confirm('Delete category? Businesses will become uncategorized.')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="form-card">
            <h3>Add New Category</h3>
            <form method="POST">
                <label>Category Name</label>
                <input type="text" name="name" placeholder="e.g. Beauty & Wellness" required>
                <label>Font Awesome Icon</label>
                <input type="text" name="icon" placeholder="e.g. fa-spa">
                <p class="hint">Use Font Awesome class names from fontawesome.com</p>
                <button type="submit" class="btn-submit">Add Category</button>
            </form>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>

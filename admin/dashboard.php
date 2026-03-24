<?php
require_once '../config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$conn = getDBConnection();

$total_businesses = $conn->query("SELECT COUNT(*) as c FROM businesses")->fetch_assoc()['c'];
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_categories = $conn->query("SELECT COUNT(*) as c FROM categories")->fetch_assoc()['c'];
$pending = $conn->query("SELECT COUNT(*) as c FROM businesses WHERE status='pending'")->fetch_assoc()['c'];

$recent_businesses = $conn->query("SELECT b.*, c.name as cat_name FROM businesses b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Zaddy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0d1b2a;
            --bg2: #112240;
            --surface: #1a2f4a;
            --surface2: #1e3554;
            --border: #233554;
            --accent: #4facfe;
            --accent2: #00f2fe;
            --accent-soft: rgba(79,172,254,0.12);
            --text: #e8f0fe;
            --text2: #a8b8d8;
            --muted: #5a7a9a;
            --sidebar: 256px;
            --green: #43e97b;
            --yellow: #f9ca24;
            --red: #ff6b6b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar);
            background: var(--bg2);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 28px 22px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(79,172,254,0.3);
        }

        .brand-text h2 { font-size: 15px; font-weight: 700; color: var(--text); letter-spacing: -0.3px; }
        .brand-text p { font-size: 11px; color: var(--muted); margin-top: 1px; }

        .sidebar-nav { padding: 16px 14px; flex: 1; overflow-y: auto; }

        .nav-label {
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 1.2px; color: var(--muted);
            padding: 0 10px; margin: 20px 0 6px;
        }

        .nav-label:first-child { margin-top: 4px; }

        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 10px;
            color: var(--text2); text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            transition: all 0.18s; margin-bottom: 1px;
        }

        .nav-item:hover { background: var(--accent-soft); color: var(--accent); }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(79,172,254,0.18), rgba(79,172,254,0.06));
            color: var(--accent);
            border-left: 3px solid var(--accent);
            padding-left: 9px;
        }

        .nav-icon { font-size: 15px; width: 20px; text-align: center; flex-shrink: 0; }

        .nav-badge {
            margin-left: auto;
            background: var(--accent);
            color: var(--bg);
            font-size: 10px; font-weight: 700;
            padding: 2px 7px; border-radius: 20px;
        }

        .sidebar-footer { padding: 16px 14px 20px; border-top: 1px solid var(--border); }

        .admin-card {
            background: var(--surface); border-radius: 12px;
            padding: 12px 14px; display: flex; align-items: center;
            gap: 10px; margin-bottom: 10px;
        }

        .admin-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: var(--bg); flex-shrink: 0;
        }

        .admin-name { font-size: 13px; font-weight: 600; color: var(--text); }
        .admin-role { font-size: 11px; color: var(--muted); margin-top: 1px; }

        .btn-logout {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            width: 100%; padding: 9px;
            background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.2);
            color: var(--red); border-radius: 10px; text-decoration: none;
            font-size: 13px; font-weight: 500; transition: all 0.2s;
        }

        .btn-logout:hover { background: rgba(255,107,107,0.15); }

        .main { margin-left: var(--sidebar); flex: 1; padding: 36px 32px; }

        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 36px;
        }

        .topbar h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; color: var(--text); }
        .topbar p { font-size: 13px; color: var(--muted); margin-top: 3px; }

        .btn-primary {
            padding: 9px 18px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: none; border-radius: 10px; color: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px; font-weight: 700; text-decoration: none;
            box-shadow: 0 4px 15px rgba(79,172,254,0.25);
            transition: opacity 0.2s, transform 0.2s;
        }

        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }

        .stats-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 18px; margin-bottom: 28px;
        }

        .stat-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 22px 24px;
            display: flex; align-items: flex-start; gap: 16px;
            transition: transform 0.2s, border-color 0.2s;
        }

        .stat-card:hover { transform: translateY(-2px); border-color: rgba(79,172,254,0.3); }

        .stat-icon-wrap {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }

        .icon-blue   { background: rgba(79,172,254,0.12); }
        .icon-green  { background: rgba(67,233,123,0.12); }
        .icon-purple { background: rgba(165,105,255,0.12); }
        .icon-yellow { background: rgba(249,202,36,0.12); }

        .stat-value { font-size: 28px; font-weight: 800; letter-spacing: -1px; line-height: 1; color: var(--text); }
        .stat-label { font-size: 12px; color: var(--muted); margin-top: 5px; font-weight: 500; }
        .stat-card.highlight .stat-value { color: var(--yellow); }

        .section-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
        }

        .section-header {
            padding: 18px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }

        .section-header h3 { font-size: 15px; font-weight: 700; color: var(--text); }
        .section-header p { font-size: 12px; color: var(--muted); margin-top: 2px; }

        .btn-sm {
            padding: 7px 14px;
            background: var(--accent-soft); border: 1px solid rgba(79,172,254,0.2);
            border-radius: 8px; color: var(--accent);
            font-size: 12px; font-weight: 600; text-decoration: none; transition: all 0.2s;
        }

        .btn-sm:hover { background: rgba(79,172,254,0.2); }

        table { width: 100%; border-collapse: collapse; }

        th {
            text-align: left; padding: 11px 24px;
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.8px; color: var(--muted);
            background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 24px; font-size: 13.5px;
            border-bottom: 1px solid var(--border); color: var(--text2);
        }

        td strong { color: var(--text); font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(79,172,254,0.03); }

        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }

        .badge::before { content: '●'; font-size: 7px; }

        .badge-active   { background: rgba(67,233,123,0.12);  color: var(--green); }
        .badge-approved { background: rgba(67,233,123,0.12);  color: var(--green); }
        .badge-pending  { background: rgba(249,202,36,0.12);  color: var(--yellow); }
        .badge-inactive { background: rgba(90,122,154,0.15);  color: var(--muted); }
        .badge-rejected { background: rgba(255,107,107,0.12); color: var(--red); }

        .action-links { display: flex; gap: 6px; }

        .action-links a {
            padding: 4px 10px; border-radius: 6px;
            font-size: 12px; font-weight: 500;
            text-decoration: none; border: 1px solid transparent; transition: all 0.18s;
        }

        .action-links .edit {
            color: var(--accent); border-color: rgba(79,172,254,0.2);
            background: rgba(79,172,254,0.06);
        }

        .action-links .edit:hover { background: rgba(79,172,254,0.15); }

        .action-links .del {
            color: var(--red); border-color: rgba(255,107,107,0.2);
            background: rgba(255,107,107,0.06);
        }

        .action-links .del:hover { background: rgba(255,107,107,0.15); }
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
            <a href="dashboard.php" class="nav-item active"><span class="nav-icon">📊</span> Dashboard</a>
            <a href="manage_businesses.php" class="nav-item"><span class="nav-icon">🏢</span> Businesses</a>
            <a href="manage_categories.php" class="nav-item"><span class="nav-icon">📁</span> Categories</a>
            <a href="manage_users.php" class="nav-item"><span class="nav-icon">👥</span> Users</a>
            <div class="nav-label">Actions</div>
            <a href="add_business.php" class="nav-item"><span class="nav-icon">➕</span> Add Business</a>
            <a href="approve_businesses.php" class="nav-item">
                <span class="nav-icon">✅</span> Approvals
                <?php if($pending > 0): ?><span class="nav-badge"><?php echo $pending; ?></span><?php endif; ?>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-card">
                <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?></div>
                <div>
                    <div class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
                    <div class="admin-role">Administrator</div>
                </div>
            </div>
            <a href="logout.php" class="btn-logout">⎋ Sign Out</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>! Here's what's happening.</p>
            </div>
            <a href="add_business.php" class="btn-primary">+ Add Business</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon-wrap icon-blue">🏢</div>
                <div>
                    <div class="stat-value"><?php echo $total_businesses; ?></div>
                    <div class="stat-label">Total Businesses</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-wrap icon-green">👥</div>
                <div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Registered Users</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-wrap icon-purple">📁</div>
                <div>
                    <div class="stat-value"><?php echo $total_categories; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            <div class="stat-card highlight">
                <div class="stat-icon-wrap icon-yellow">⏳</div>
                <div>
                    <div class="stat-value"><?php echo $pending; ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <div>
                    <h3>Recent Businesses</h3>
                    <p>Latest additions to the directory</p>
                </div>
                <a href="manage_businesses.php" class="btn-sm">View All →</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Business Name</th>
                        <th>Category</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($b = $recent_businesses->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($b['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($b['cat_name'] ?? 'Uncategorized'); ?></td>
                        <td><?php echo htmlspecialchars($b['phone'] ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $b['status']; ?>">
                                <?php echo ucfirst($b['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-links">
                                <a href="edit_business.php?id=<?php echo $b['id']; ?>" class="edit">Edit</a>
                                <a href="delete_business.php?id=<?php echo $b['id']; ?>" class="del" onclick="return confirm('Delete this business?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>
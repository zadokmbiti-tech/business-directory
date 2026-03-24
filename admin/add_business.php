<?php
require_once '../config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$conn = getDBConnection();
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    $status = $_POST['status'];
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
    }


    if ($conn->query("INSERT INTO businesses (name, category_id, description, address, phone, email, website, image, status) VALUES ('".mysqli_real_escape_string($conn,$name)."', $category_id, '".mysqli_real_escape_string($conn,$description)."', '".mysqli_real_escape_string($conn,$address)."', '".mysqli_real_escape_string($conn,$phone)."', '".mysqli_real_escape_string($conn,$email)."', '".mysqli_real_escape_string($conn,$website)."', '".mysqli_real_escape_string($conn,$image)."', '$status')")) {
        $success = 'Business added successfully!';
    } else {
        $error = 'Failed to add business: ' . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Business - Zaddy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg:#0a0a0f; --surface:#13131a; --border:#1e1e2e; --accent:#f0c040; --accent2:#e07820; --text:#f0ede8; --muted:#6b6880; --sidebar:240px; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

        .sidebar { width:var(--sidebar); background:var(--surface); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:100; }
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

        .main { margin-left:var(--sidebar); flex:1; padding:32px; max-width:800px; }
        .page-header { margin-bottom:32px; }
        .page-header h1 { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; }
        .page-header p { color:var(--muted); font-size:14px; margin-top:4px; }

        .form-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:32px; }

        .msg-success { background:rgba(52,211,153,0.1); border:1px solid rgba(52,211,153,0.3); color:#34d399; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .msg-error { background:rgba(220,60,60,0.1); border:1px solid rgba(220,60,60,0.3); color:#f87171; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; }

        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-group { margin-bottom:0; }
        .form-group.full { grid-column:1/-1; }
        label { display:block; font-size:12px; font-weight:500; color:var(--muted); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:8px; }
        input, select, textarea { width:100%; background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:12px 16px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:14px; outline:none; transition:border-color 0.2s; }
        input:focus, select:focus, textarea:focus { border-color:var(--accent); }
        select option { background:var(--surface); }
        textarea { resize:vertical; min-height:100px; }

        .form-actions { display:flex; gap:12px; margin-top:28px; }
        .btn-submit { padding:12px 28px; background:linear-gradient(135deg,var(--accent),var(--accent2)); border:none; border-radius:10px; color:#0a0a0f; font-family:'Syne',sans-serif; font-size:14px; font-weight:700; cursor:pointer; transition:opacity 0.2s; }
        .btn-submit:hover { opacity:0.9; }
        .btn-cancel { padding:12px 28px; background:transparent; border:1px solid var(--border); border-radius:10px; color:var(--muted); font-size:14px; text-decoration:none; display:inline-flex; align-items:center; }
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
            <a href="manage_categories.php" class="nav-item"><span class="icon">📁</span> Categories</a>
            <a href="manage_users.php" class="nav-item"><span class="icon">👥</span> Users</a>
            <div class="nav-label">Actions</div>
            <a href="add_business.php" class="nav-item active"><span class="icon">➕</span> Add Business</a>
            <a href="approve_businesses.php" class="nav-item"><span class="icon">✅</span> Approvals</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </aside>

    <main class="main">
        <div class="page-header">
            <h1>Add New Business</h1>
            <p>Fill in the details to add a business to the directory</p>
        </div>

        <div class="form-card">
            <?php if ($success): ?><div class="msg-success">✅ <?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-error">⚠️ <?php echo $error; ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Business Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Java House">
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">Select category...</option>
                            <?php while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description" placeholder="Brief description of the business..."></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Address</label>
                        <input type="text" name="address" placeholder="e.g. Westlands, Nairobi">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="e.g. 0700123456">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="e.g. info@business.com">
                    </div>
                    <div class="form-group">
                        <label>Website</label>
                        <input type="text" name="website" placeholder="e.g. https://website.com">
                    </div>
                    <div class="form-group">
                        <label>Business Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Add Business →</button>
                    <a href="manage_businesses.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>

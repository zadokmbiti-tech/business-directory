<?php
require_once '../config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$conn = getDBConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) { header('Location: manage_businesses.php'); exit(); }

$business = $conn->query("SELECT * FROM businesses WHERE id=$id")->fetch_assoc();
if (!$business) { header('Location: manage_businesses.php'); exit(); }

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $category_id = (int)$_POST['category_id'];
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $website = mysqli_real_escape_string($conn, trim($_POST['website']));
    $status = $_POST['status'];
    $image = $business['image'];

    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
    }

    if ($conn->query("UPDATE businesses SET name='$name', category_id=$category_id, description='$description', address='$address', phone='$phone', email='$email', website='$website', image='$image', status='$status' WHERE id=$id")) {
        $success = 'Business updated successfully!';
        $business = $conn->query("SELECT * FROM businesses WHERE id=$id")->fetch_assoc();
    } else {
        $error = 'Update failed: ' . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Business - Zaddy Admin</title>
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
        .main { margin-left:var(--sidebar); flex:1; padding:32px; max-width:800px; }
        .page-header { margin-bottom:32px; display:flex; align-items:center; justify-content:space-between; }
        .page-header h1 { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; }
        .page-header p { color:var(--muted); font-size:14px; margin-top:4px; }
        .back-link { color:var(--muted); text-decoration:none; font-size:13px; display:flex; align-items:center; gap:6px; }
        .back-link:hover { color:var(--accent); }
        .form-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:32px; }
        .msg-success { background:rgba(52,211,153,0.1); border:1px solid rgba(52,211,153,0.3); color:#34d399; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .msg-error { background:rgba(220,60,60,0.1); border:1px solid rgba(220,60,60,0.3); color:#f87171; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-group { display:flex; flex-direction:column; }
        .form-group.full { grid-column:1/-1; }
        label { font-size:12px; font-weight:500; color:var(--muted); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:8px; }
        input, select, textarea { background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:12px 16px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:14px; outline:none; transition:border-color 0.2s; }
        input:focus, select:focus, textarea:focus { border-color:var(--accent); }
        select option { background:var(--surface); }
        textarea { resize:vertical; min-height:100px; }
        .current-image { margin-top:8px; font-size:12px; color:var(--muted); }
        .current-image img { max-width:120px; border-radius:8px; margin-top:6px; display:block; }
        .form-actions { display:flex; gap:12px; margin-top:28px; }
        .btn-submit { padding:12px 28px; background:linear-gradient(135deg,var(--accent),var(--accent2)); border:none; border-radius:10px; color:#0a0a0f; font-family:'Syne',sans-serif; font-size:14px; font-weight:700; cursor:pointer; }
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
            <a href="manage_businesses.php" class="nav-item active"><span class="icon">🏢</span> Businesses</a>
            <a href="manage_categories.php" class="nav-item"><span class="icon">📁</span> Categories</a>
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
            <div>
                <h1>Edit Business</h1>
                <p>Editing: <strong><?php echo htmlspecialchars($business['name']); ?></strong></p>
            </div>
            <a href="manage_businesses.php" class="back-link">← Back to businesses</a>
        </div>

        <div class="form-card">
            <?php if ($success): ?><div class="msg-success">✅ <?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-error">⚠️ <?php echo $error; ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Business Name *</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($business['name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">Select category...</option>
                            <?php while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $business['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <?php foreach(['active','pending','inactive','approved','rejected'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $s === $business['status'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description"><?php echo htmlspecialchars($business['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($business['address'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($business['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($business['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Website</label>
                        <input type="text" name="website" value="<?php echo htmlspecialchars($business['website'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Business Image</label>
                        <input type="file" name="image" accept="image/*">
                        <?php if($business['image']): ?>
                            <div class="current-image">
                                Current image:
                                <img src="../uploads/<?php echo htmlspecialchars($business['image']); ?>" alt="Current image">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Save Changes →</button>
                    <a href="manage_businesses.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>
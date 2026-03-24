<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/dbconnection.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$conn      = getDBConnection();
$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'user';
$id        = (int)($_GET['id'] ?? 0);

// Fetch business
$stmt = $conn->prepare("SELECT b.*, c.name as cat_name FROM businesses b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$biz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$biz || ($user_type !== 'admin' && $biz['user_id'] != $user_id)) {
    header("Location: dashboard.php"); exit();
}

// Fetch categories
$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $cat_id  = (int)($_POST['category_id'] ?? 0);
    $desc    = trim($_POST['description'] ?? '');

    if (empty($name) || empty($phone) || empty($address) || $cat_id === 0) {
        $error = 'Please fill in all required fields.';
    } else {

        // Handle image upload
        $image_name = $biz['image']; // keep existing by default

        if (!empty($_FILES['image']['name'])) {
            $allowed     = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_name   = $_FILES['image']['name'];
            $file_tmp    = $_FILES['image']['tmp_name'];
            $file_size   = $_FILES['image']['size'];
            $file_error  = $_FILES['image']['error'];
            $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($file_error !== 0) {
                $error = 'Image upload error. Please try again.';
            } elseif (!in_array($file_ext, $allowed)) {
                $error = 'Invalid image type. Allowed: JPG, JPEG, PNG, GIF, WEBP.';
            } elseif ($file_size > 5 * 1024 * 1024) {
                $error = 'Image size must be under 5MB.';
            } else {
                $upload_dir = '../uploads/';
                $new_name   = 'biz_' . $id . '_' . time() . '.' . $file_ext;
                $dest       = $upload_dir . $new_name;

                if (move_uploaded_file($file_tmp, $dest)) {
                    // Delete old image if exists
                    if (!empty($biz['image']) && file_exists($upload_dir . $biz['image'])) {
                        unlink($upload_dir . $biz['image']);
                    }
                    $image_name = $new_name;
                } else {
                    $error = 'Failed to save image. Check folder permissions.';
                }
            }
        }

        if (empty($error)) {
            $upd = $conn->prepare("UPDATE businesses SET name=?, phone=?, address=?, category_id=?, description=?, image=? WHERE id=?");
            $upd->bind_param("sssissi", $name, $phone, $address, $cat_id, $desc, $image_name, $id);
            if ($upd->execute()) {
                $success = 'Business updated successfully!';
                $biz['name']        = $name;
                $biz['phone']       = $phone;
                $biz['address']     = $address;
                $biz['category_id'] = $cat_id;
                $biz['description'] = $desc;
                $biz['image']       = $image_name;
            } else {
                $error = 'Update failed. Please try again.';
            }
            $upd->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Business - Zaddy</title>
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
    .header { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 20px; }
    .header-content { max-width: 900px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
    .header h1 { font-size: 22px; }
    .header a { color: white; text-decoration: none; font-size: 14px; }
    .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
    .card { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
    .card h2 { font-size: 20px; color: #333; margin-bottom: 6px; }
    .card p  { color: #aaa; font-size: 13px; margin-bottom: 24px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .form-group { display: flex; flex-direction: column; }
    .form-group.full { grid-column: 1 / -1; }
    label { font-size: 12px; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: 0.7px; margin-bottom: 7px; }
    input, select, textarea { padding: 11px 14px; border: 2px solid #e8e8e8; border-radius: 8px; font-size: 14px; font-family: inherit; outline: none; transition: border-color 0.2s; width: 100%; }
    input:focus, select:focus, textarea:focus { border-color: #667eea; }
    textarea { resize: vertical; min-height: 100px; }
    input[type="file"] { padding: 8px 14px; cursor: pointer; }

    /* Image preview area */
    .image-section { grid-column: 1 / -1; }
    .current-image { margin-bottom: 12px; }
    .current-image img {
        width: 180px;
        height: 130px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #e8e8e8;
        display: block;
        margin-bottom: 6px;
    }
    .current-image span { font-size: 12px; color: #aaa; }
    .no-image {
        width: 180px;
        height: 130px;
        background: #f5f5f5;
        border: 2px dashed #ddd;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: #bbb;
        margin-bottom: 6px;
    }
    .preview-wrap { margin-top: 10px; }
    .preview-wrap img {
        width: 180px;
        height: 130px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #667eea;
        display: none;
    }
    .preview-label { font-size: 12px; color: #667eea; margin-bottom: 6px; display: none; }

    .actions { display: flex; gap: 12px; margin-top: 24px; }
    .btn-save { padding: 12px 28px; background: linear-gradient(135deg,#667eea,#764ba2); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .btn-save:hover { opacity: 0.9; }
    .btn-back { padding: 12px 22px; background: white; color: #667eea; border: 2px solid #667eea; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
    .msg-ok  { background:#efe; border:1px solid #cfc; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#2d5016; font-size:14px; }
    .msg-err { background:#fee; border:1px solid #fcc; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#c33; font-size:14px; }
</style>
</head>
<body>
<div class="header">
    <div class="header-content">
        <h1>Zaddy Business Directory</h1>
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h2>✏️ Edit Business</h2>
        <p>Update the details for <strong><?php echo htmlspecialchars($biz['name']); ?></strong></p>

        <?php if ($success): ?><div class="msg-ok">✅ <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="msg-err">⚠️ <?php echo $error; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">

                <div class="form-group">
                    <label>Business Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($biz['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($biz['phone'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">-- Select Category --</option>
                        <?php $cats->data_seek(0); while($cat = $cats->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $biz['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Address *</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($biz['address'] ?? ''); ?>" required>
                </div>

                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description"><?php echo htmlspecialchars($biz['description'] ?? ''); ?></textarea>
                </div>

                <!-- Image Upload -->
                <div class="form-group image-section">
                    <label>Business Image</label>

                    <!-- Current image -->
                    <div class="current-image">
                        <?php if (!empty($biz['image']) && file_exists('../uploads/' . $biz['image'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($biz['image']); ?>" alt="Current Image">
                            <span>Current image</span>
                        <?php else: ?>
                            <div class="no-image">📷 No image uploaded</div>
                        <?php endif; ?>
                    </div>

                    <!-- File input -->
                    <input type="file" name="image" id="imageInput" accept="image/*">
                    <small style="color:#aaa; font-size:12px; margin-top:6px; display:block;">
                        JPG, JPEG, PNG, GIF or WEBP · Max 5MB · Leave empty to keep current image
                    </small>

                    <!-- Preview new image before saving -->
                    <div class="preview-wrap">
                        <div class="preview-label" id="previewLabel">New image preview:</div>
                        <img id="imagePreview" src="#" alt="Preview">
                    </div>
                </div>

            </div>

            <div class="actions">
                <button type="submit" class="btn-save">💾 Save Changes</button>
                <a href="dashboard.php" class="btn-back">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Live preview before uploading
    document.getElementById('imageInput').addEventListener('change', function() {
        const preview = document.getElementById('imagePreview');
        const label   = document.getElementById('previewLabel');
        const file    = this.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                label.style.display   = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            label.style.display   = 'none';
        }
    });
</script>
</body>
</html>
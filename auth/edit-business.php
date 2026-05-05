<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/dbconnection.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$conn      = getDBConnection();
$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'user';
$id        = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT b.*, c.name as cat_name FROM businesses b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$biz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$biz || ($user_type !== 'admin' && $biz['user_id'] != $user_id)) {
    header("Location: dashboard.php"); exit();
}

$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']        ?? '');
    $phone   = trim($_POST['phone']       ?? '');
    $address = trim($_POST['address']     ?? '');
    $cat_id  = (int)($_POST['category_id'] ?? 0);
    $desc    = trim($_POST['description'] ?? '');
    $email   = trim($_POST['email']       ?? '');
    $website = trim($_POST['website']     ?? '');

    // Operation Hours
    $allowed_days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $hours_raw    = $_POST['hours'] ?? [];
    $hours_clean  = [];
    foreach ($allowed_days as $day) {
        $open   = trim($hours_raw[$day]['open']  ?? '08:00');
        $close  = trim($hours_raw[$day]['close'] ?? '17:00');
        $closed = !empty($hours_raw[$day]['closed']);
        $hours_clean[$day] = ['open' => $open, 'close' => $close, 'closed' => $closed];
    }
    $operation_hours_json = json_encode($hours_clean);

    if (empty($name) || empty($phone) || empty($address) || $cat_id === 0) {
        $error = 'Please fill in all required fields.';
    } else {
        $image_name = $biz['image'];

        if (!empty($_FILES['image']['name'])) {
            $allowed    = ['jpg','jpeg','png','gif','webp'];
            $file_ext   = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $file_error = $_FILES['image']['error'];
            $file_size  = $_FILES['image']['size'];

            if ($file_error !== 0) {
                $error = 'Image upload error.';
            } elseif (!in_array($file_ext, $allowed)) {
                $error = 'Invalid image type. Allowed: JPG, JPEG, PNG, GIF, WEBP.';
            } elseif ($file_size > 5 * 1024 * 1024) {
                $error = 'Image size must be under 5MB.';
            } else {
                $upload_dir = '../uploads/';
                $new_name   = 'biz_' . $id . '_' . time() . '.' . $file_ext;
                $dest       = $upload_dir . $new_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    if (!empty($biz['image']) && file_exists($upload_dir . $biz['image']))
                        unlink($upload_dir . $biz['image']);
                    $image_name = $new_name;
                } else {
                    $error = 'Failed to save image.';
                }
            }
        }

        if (empty($error)) {
            $upd = $conn->prepare(
                "UPDATE businesses SET name=?, phone=?, address=?, category_id=?, description=?, email=?, website=?, image=?, operation_hours=? WHERE id=?"
            );
            $upd->bind_param("sssisssssi",
                $name, $phone, $address, $cat_id, $desc,
                $email, $website, $image_name, $operation_hours_json, $id
            );
            if ($upd->execute()) {
                $success = 'Business updated successfully!';
                $biz['name']            = $name;
                $biz['phone']           = $phone;
                $biz['address']         = $address;
                $biz['category_id']     = $cat_id;
                $biz['description']     = $desc;
                $biz['email']           = $email;
                $biz['website']         = $website;
                $biz['image']           = $image_name;
                $biz['operation_hours'] = $operation_hours_json;
            } else {
                $error = 'Update failed. Please try again.';
            }
            $upd->close();
        }
    }
}

$saved_hours  = [];
if (!empty($biz['operation_hours']))
    $saved_hours = json_decode($biz['operation_hours'], true) ?? [];
$days_of_week = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

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
.header a  { color: white; text-decoration: none; font-size: 14px; }

.container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
.card { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
.card h2 { font-size: 20px; color: #333; margin-bottom: 6px; }
.card > p { color: #aaa; font-size: 13px; margin-bottom: 24px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.form-group { display: flex; flex-direction: column; }
.form-group.full { grid-column: 1 / -1; }
label { font-size: 12px; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: 0.7px; margin-bottom: 7px; }
input, select, textarea {
    padding: 11px 14px; border: 2px solid #e8e8e8; border-radius: 8px;
    font-size: 14px; font-family: inherit; outline: none;
    transition: border-color 0.2s; width: 100%;
}
input:focus, select:focus, textarea:focus { border-color: #667eea; }
textarea { resize: vertical; min-height: 100px; }
input[type="file"] { padding: 8px 14px; cursor: pointer; }

/* Image */
.image-section { grid-column: 1 / -1; }
.current-image { margin-bottom: 12px; }
.current-image img { width: 180px; height: 130px; object-fit: cover; border-radius: 8px; border: 2px solid #e8e8e8; display: block; margin-bottom: 6px; }
.current-image span { font-size: 12px; color: #aaa; }
.no-image { width: 180px; height: 130px; background: #f5f5f5; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #bbb; margin-bottom: 6px; }
.preview-wrap { margin-top: 10px; }
.preview-wrap img { width: 180px; height: 130px; object-fit: cover; border-radius: 8px; border: 2px solid #667eea; display: none; }
.preview-label { font-size: 12px; color: #667eea; margin-bottom: 6px; display: none; }

/* Hours */
.hours-section { grid-column: 1 / -1; }
.hours-table { width: 100%; border-collapse: collapse; margin-top: 4px; border-radius: 10px; overflow: hidden; box-shadow: 0 0 0 2px #e8e8e8; }
.hours-table thead tr { background: linear-gradient(135deg,#667eea,#764ba2); color: white; }
.hours-table thead th { padding: 11px 14px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; text-align: left; }
.hours-table thead th:last-child { text-align: center; }
.hours-table tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.15s; }
.hours-table tbody tr:last-child { border-bottom: none; }
.hours-table tbody tr:hover { background: #fafafa; }
.hours-table tbody tr.is-closed { background: #fdf6f6; }
.hours-table td { padding: 10px 14px; vertical-align: middle; }
.hours-table td.day-name { font-size: 13px; font-weight: 600; color: #444; width: 120px; }
.hours-table td input[type="time"] { padding: 8px 10px; font-size: 13px; border: 2px solid #e8e8e8; border-radius: 7px; width: 130px; cursor: pointer; transition: border-color 0.2s, opacity 0.2s; }
.hours-table td input[type="time"]:focus { border-color: #667eea; }
.hours-table td input[type="time"]:disabled { opacity: 0.35; cursor: not-allowed; }

.toggle-cell { text-align: center; width: 100px; }
.switch { position: relative; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
.switch input { opacity: 0; width: 0; height: 0; position: absolute; }
.slider { width: 42px; height: 24px; background: #ddd; border-radius: 12px; transition: background 0.25s; position: relative; flex-shrink: 0; }
.slider::before { content: ''; position: absolute; width: 18px; height: 18px; border-radius: 50%; background: white; top: 3px; left: 3px; transition: transform 0.25s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.switch input:checked + .slider { background: #e05252; }
.switch input:checked + .slider::before { transform: translateX(18px); }
.switch-label { font-size: 12px; color: #999; white-space: nowrap; }
.switch input:checked ~ .switch-label { color: #e05252; font-weight: 600; }
.closed-badge { display: none; font-size: 10px; font-weight: 700; background: #fee; color: #e05252; border-radius: 4px; padding: 1px 6px; margin-left: 6px; text-transform: uppercase; letter-spacing: 0.5px; vertical-align: middle; }
tr.is-closed .closed-badge { display: inline; }

/* Actions */
.actions { display: flex; gap: 12px; margin-top: 24px; }
.btn-save { padding: 12px 28px; background: linear-gradient(135deg,#667eea,#764ba2); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-save:hover { opacity: 0.9; }
.btn-back { padding: 12px 22px; background: white; color: #667eea; border: 2px solid #667eea; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }

.msg-ok  { background:#efe; border:1px solid #cfc; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#2d5016; font-size:14px; }
.msg-err { background:#fee; border:1px solid #fcc; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#c33; font-size:14px; }

@media (max-width: 600px) {
    .form-grid { grid-template-columns: 1fr; }
    .form-group.full, .image-section, .hours-section { grid-column: 1; }
    .hours-table thead th:nth-child(2), .hours-table thead th:nth-child(3),
    .hours-table td:nth-child(2), .hours-table td:nth-child(3) { display: none; }
}
</style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <h1>Zaddy Business Directory</h1>
        <a href="dashboard.php">&#8592; Back to Dashboard</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h2>&#9999; Edit Business</h2>
        <p>Update the details for <strong><?php echo htmlspecialchars($biz['name']); ?></strong></p>

        <?php if ($success): ?><div class="msg-ok">&#10003; <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="msg-err">&#9888; <?php echo $error; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">

                <!-- Name -->
                <div class="form-group">
                    <label>Business Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($biz['name']); ?>" required>
                </div>

                <!-- Phone -->
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($biz['phone'] ?? ''); ?>" required>
                </div>

                <!-- Category -->
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

                <!-- Address -->
                <div class="form-group">
                    <label>Address *</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($biz['address'] ?? ''); ?>" required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="email"
                           value="<?php echo htmlspecialchars($biz['email'] ?? ''); ?>"
                           placeholder="business@example.com">
                </div>

                <!-- Website -->
                <div class="form-group">
                    <label>Website URL</label>
                    <input type="text" name="website"
                           value="<?php echo htmlspecialchars($biz['website'] ?? ''); ?>"
                           placeholder="https://www.yourbusiness.com">
                </div>

                <!-- Description -->
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description"><?php echo htmlspecialchars($biz['description'] ?? ''); ?></textarea>
                </div>

                <!-- Operation Hours -->
                <div class="form-group hours-section">
                    <label>&#128336; Operation Hours</label>
                    <table class="hours-table">
                        <thead>
                            <tr><th>Day</th><th>Opens</th><th>Closes</th><th>Closed?</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($days_of_week as $day):
                            $s      = $saved_hours[$day] ?? [];
                            $open   = htmlspecialchars($s['open']  ?? '08:00');
                            $close  = htmlspecialchars($s['close'] ?? '17:00');
                            $closed = !empty($s['closed']);
                            $rid    = 'row-' . strtolower($day);
                        ?>
                        <tr id="<?php echo $rid; ?>" <?php echo $closed ? 'class="is-closed"' : ''; ?>>
                            <td class="day-name">
                                <?php echo $day; ?>
                                <span class="closed-badge">Closed</span>
                            </td>
                            <td>
                                <input type="time" name="hours[<?php echo $day; ?>][open]"
                                       value="<?php echo $open; ?>" <?php echo $closed ? 'disabled' : ''; ?>>
                            </td>
                            <td>
                                <input type="time" name="hours[<?php echo $day; ?>][close]"
                                       value="<?php echo $close; ?>" <?php echo $closed ? 'disabled' : ''; ?>>
                            </td>
                            <td class="toggle-cell">
                                <label class="switch">
                                    <input type="checkbox"
                                           name="hours[<?php echo $day; ?>][closed]"
                                           id="closed-<?php echo strtolower($day); ?>"
                                           value="1"
                                           <?php echo $closed ? 'checked' : ''; ?>
                                           onchange="toggleDay('<?php echo $rid; ?>')">
                                    <span class="slider"></span>
                                    <span class="switch-label">Closed</span>
                                </label>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <small style="color:#aaa;font-size:12px;margin-top:8px;display:block;">Toggle the switch to mark a day as closed.</small>
                </div>

                <!-- Business Image -->
                <div class="form-group image-section">
                    <label>Business Image</label>
                    <div class="current-image">
                        <?php if (!empty($biz['image']) && file_exists('../uploads/' . $biz['image'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($biz['image']); ?>" alt="Current Image">
                            <span>Current image</span>
                        <?php else: ?>
                            <div class="no-image">&#128247; No image uploaded</div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="image" id="imageInput" accept="image/*">
                    <small style="color:#aaa;font-size:12px;margin-top:6px;display:block;">
                        JPG, JPEG, PNG, GIF or WEBP &middot; Max 5MB &middot; Leave empty to keep current image
                    </small>
                    <div class="preview-wrap">
                        <div class="preview-label" id="previewLabel">New image preview:</div>
                        <img id="imagePreview" src="#" alt="Preview">
                    </div>
                </div>

            </div>

            <div class="actions">
                <button type="submit" class="btn-save">&#128190; Save Changes</button>
                <a href="dashboard.php" class="btn-back">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('imageInput').addEventListener('change', function () {
    const preview = document.getElementById('imagePreview');
    const label   = document.getElementById('previewLabel');
    const file    = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
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

function toggleDay(rowId) {
    const row      = document.getElementById(rowId);
    const dayId    = rowId.replace('row-', '');
    const checkbox = document.getElementById('closed-' + dayId);
    const isClosed = checkbox.checked;
    row.querySelectorAll('input[type="time"]').forEach(inp => {
        inp.disabled      = isClosed;
        inp.style.opacity = isClosed ? '0.35' : '1';
    });
    row.classList.toggle('is-closed', isClosed);
}
</script>
</body>
</html>
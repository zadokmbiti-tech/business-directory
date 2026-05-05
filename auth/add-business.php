<?php
require_once '../config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors  = [];
$success = '';

$conn = getDBConnection();
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$days_of_week = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = trim($_POST['business_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $address     = trim($_POST['location'] ?? '');
    $phone       = trim($_POST['contact_phone'] ?? '');
    $email       = trim($_POST['contact_email'] ?? '');
    $website     = trim($_POST['website'] ?? '');
    $status      = 'pending';

    // Operation Hours
    $hours_raw   = $_POST['hours'] ?? [];
    $hours_clean = [];
    foreach ($days_of_week as $day) {
        $open   = trim($hours_raw[$day]['open']  ?? '08:00');
        $close  = trim($hours_raw[$day]['close'] ?? '17:00');
        $closed = !empty($hours_raw[$day]['closed']);
        $hours_clean[$day] = ['open' => $open, 'close' => $close, 'closed' => $closed];
    }
    $operation_hours_json = json_encode($hours_clean);

    // Validation
    if (empty($name))        $errors[] = "Business name is required";
    if ($category_id === 0)  $errors[] = "Please select a category";
    if (empty($description)) $errors[] = "Business description is required";
    if (strlen($description) < 20) $errors[] = "Description must be at least 20 characters";
    if (empty($address))     $errors[] = "Business location is required";
    if (empty($phone))       $errors[] = "Contact phone is required";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";

    // Handle image upload
    $image_name = null;
    if (isset($_FILES['business_image']) && $_FILES['business_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['business_image']['type'];
        $file_size = $_FILES['business_image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed";
        } elseif ($file_size > 5000000) {
            $errors[] = "Image size must be less than 5MB";
        } else {
            $file_ext    = pathinfo($_FILES['business_image']['name'], PATHINFO_EXTENSION);
            $image_name  = uniqid('business_') . '.' . $file_ext;
            $upload_path = '../uploads/' . $image_name;

            if (!move_uploaded_file($_FILES['business_image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
                $image_name = null;
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO businesses
             (name, category_id, description, address, phone, email, website, image, status, user_id, operation_hours, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param(
            "sisssssssis",
            $name, $category_id, $description, $address,
            $phone, $email, $website, $image_name,
            $status, $user_id, $operation_hours_json
        );

        if ($stmt->execute()) {
            $success = "Business submitted successfully! Your listing is pending admin approval.";
            $_POST = [];
        } else {
            $errors[] = "Failed to submit business: " . $conn->error;
        }
        $stmt->close();
    }
}

$conn->close();

// Re-populate hours after a failed POST
$saved_hours = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['hours'])) {
    foreach ($days_of_week as $day) {
        $saved_hours[$day] = [
            'open'   => $_POST['hours'][$day]['open']  ?? '08:00',
            'close'  => $_POST['hours'][$day]['close'] ?? '17:00',
            'closed' => !empty($_POST['hours'][$day]['closed']),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Business - Zaddy Business Directory</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }

        .header {
            background: #2c3e50; color: white;
            padding: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px; margin: 0 auto; padding: 0 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { font-size: 24px; }
        .header-nav { display: flex; gap: 20px; }
        .header-nav a { color: white; text-decoration: none; font-size: 14px; transition: opacity 0.3s; }
        .header-nav a:hover { opacity: 0.8; }

        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .form-card {
            background: white; border-radius: 10px;
            padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-card h2 { color: #333; margin-bottom: 10px; font-size: 28px; }
        .form-card > p { color: #666; margin-bottom: 30px; font-size: 14px; }

        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block; margin-bottom: 7px;
            color: #333; font-weight: 600; font-size: 14px;
        }
        .required { color: #e74c3c; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 12px;
            border: 2px solid #ddd; border-radius: 6px;
            font-size: 14px; font-family: inherit;
            transition: border-color 0.3s; outline: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { border-color: #4a90e2; }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .form-group small { display: block; margin-top: 5px; color: #999; font-size: 12px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /* Image upload */
        .image-upload-area {
            border: 2px dashed #ddd; border-radius: 8px;
            padding: 30px; text-align: center; cursor: pointer;
            transition: all 0.3s;
        }
        .image-upload-area:hover { border-color: #4a90e2; background: #f8f9ff; }
        .image-upload-area.has-image { border-color: #27ae60; background: #e8f5e9; }
        .upload-icon { font-size: 48px; margin-bottom: 10px; color: #999; }
        .upload-text { color: #666; font-size: 14px; }
        #business_image { display: none; }
        #image-preview { max-width: 100%; max-height: 200px; margin-top: 15px; border-radius: 8px; display: none; }

        /* Operation Hours table */
        .hours-table {
            width: 100%; border-collapse: collapse;
            border-radius: 8px; overflow: hidden;
            box-shadow: 0 0 0 2px #e8e8e8;
        }
        .hours-table thead tr { background: #2c3e50; color: white; }
        .hours-table thead th {
            padding: 11px 14px; font-size: 12px;
            font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.6px; text-align: left;
        }
        .hours-table thead th:last-child { text-align: center; }
        .hours-table tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.15s; }
        .hours-table tbody tr:last-child { border-bottom: none; }
        .hours-table tbody tr:hover { background: #fafafa; }
        .hours-table tbody tr.is-closed { background: #fdf6f6; }
        .hours-table td { padding: 10px 14px; vertical-align: middle; }
        .hours-table td.day-name { font-size: 13px; font-weight: 600; color: #444; width: 120px; }
        .hours-table td input[type="time"] {
            padding: 8px 10px; font-size: 13px;
            border: 2px solid #ddd; border-radius: 6px;
            width: 130px; cursor: pointer;
            transition: border-color 0.2s, opacity 0.2s;
            font-family: inherit;
        }
        .hours-table td input[type="time"]:focus { border-color: #4a90e2; outline: none; }
        .hours-table td input[type="time"]:disabled { opacity: 0.35; cursor: not-allowed; }

        /* Toggle switch */
        .toggle-cell { text-align: center; width: 100px; }
        .switch { position: relative; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
        .switch input { opacity: 0; width: 0; height: 0; position: absolute; }
        .slider {
            width: 42px; height: 24px; background: #ccc;
            border-radius: 12px; transition: background 0.25s;
            position: relative; flex-shrink: 0;
        }
        .slider::before {
            content: ''; position: absolute;
            width: 18px; height: 18px; border-radius: 50%;
            background: white; top: 3px; left: 3px;
            transition: transform 0.25s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .switch input:checked + .slider { background: #e05252; }
        .switch input:checked + .slider::before { transform: translateX(18px); }
        .switch-label { font-size: 12px; color: #999; white-space: nowrap; }
        .switch input:checked ~ .switch-label { color: #e05252; font-weight: 600; }

        .closed-badge {
            display: none; font-size: 10px; font-weight: 700;
            background: #fee; color: #e05252;
            border-radius: 4px; padding: 1px 6px;
            margin-left: 6px; text-transform: uppercase; letter-spacing: 0.5px;
            vertical-align: middle;
        }
        tr.is-closed .closed-badge { display: inline; }

        /* Alerts */
        .error-messages {
            background: #fee; border: 1px solid #fcc;
            border-radius: 6px; padding: 15px; margin-bottom: 20px;
        }
        .error-messages ul { list-style: none; }
        .error-messages li { color: #c33; font-size: 14px; margin-bottom: 5px; }
        .error-messages li:before { content: "? "; }
        .success-message {
            background: #efe; border: 1px solid #cfc;
            border-radius: 6px; padding: 15px; margin-bottom: 20px;
            color: #2d5016; font-size: 14px;
        }

        /* Actions */
        .form-actions { display: flex; gap: 15px; margin-top: 30px; }
        .btn {
            padding: 12px 30px; border: none; border-radius: 6px;
            font-size: 15px; font-weight: 600; cursor: pointer;
            transition: transform 0.2s; text-decoration: none;
            display: inline-block; text-align: center;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary   { background: #4a90e2; color: white; }
        .btn-secondary { background: white; color: #4a90e2; border: 2px solid #4a90e2; }
        .btn-secondary:hover { background: #f8f9ff; }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .hours-table thead th:nth-child(2),
            .hours-table thead th:nth-child(3),
            .hours-table td:nth-child(2),
            .hours-table td:nth-child(3) { display: none; }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <h1>Zaddy Business Directory</h1>
        <div class="header-nav">
            <a href="../index.php">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="form-card">
        <h2>Add New Business</h2>
        <p>Fill in the details below to submit your business listing for approval.</p>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                ? <?php echo htmlspecialchars($success); ?>
                <a href="dashboard.php" style="color:#2d5016; font-weight:bold; margin-left:8px;">Go to Dashboard</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">

            <!-- Business Name -->
            <div class="form-group">
                <label for="business_name">Business Name <span class="required">*</span></label>
                <input type="text" id="business_name" name="business_name"
                       value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>"
                       placeholder="Enter your business name" required>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label for="category_id">Category <span class="required">*</span></label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select a category...</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Image -->
            <div class="form-group">
                <label>Business Image</label>
                <div class="image-upload-area" id="upload-area"
                     onclick="document.getElementById('business_image').click()">
                    <div class="upload-icon">?</div>
                    <div class="upload-text" id="upload-text">
                        Click to upload business image (JPG, PNG, GIF - Max 5MB)
                    </div>
                    <img id="image-preview" alt="Preview">
                </div>
                <input type="file" id="business_image" name="business_image" accept="image/*">
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Business Description <span class="required">*</span></label>
                <textarea id="description" name="description"
                          placeholder="Describe your business, products and services (minimum 20 characters)"
                          required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <small>Minimum 20 characters</small>
            </div>

            <!-- Location + Phone -->
            <div class="form-row">
                <div class="form-group">
                    <label for="location">Location/Address <span class="required">*</span></label>
                    <input type="text" id="location" name="location"
                           value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                           placeholder="e.g. Maseno, Kisumu" required>
                </div>
                <div class="form-group">
                    <label for="contact_phone">Contact Phone <span class="required">*</span></label>
                    <input type="tel" id="contact_phone" name="contact_phone"
                           value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>"
                           placeholder="e.g. +254 712 345 678" required>
                </div>
            </div>

            <!-- Email + Website -->
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email"
                           value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>"
                           placeholder="business@example.com">
                </div>
                <div class="form-group">
                    <label for="website">Website URL</label>
                    <input type="text" id="website" name="website"
                           value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>"
                           placeholder="https://www.yourbusiness.com">
                </div>
            </div>

            <!-- Operation Hours -->
            <div class="form-group">
                <label>Operation Hours</label>
                <table class="hours-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Opens</th>
                            <th>Closes</th>
                            <th>Closed?</th>
                        </tr>
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
                            <input type="time"
                                   name="hours[<?php echo $day; ?>][open]"
                                   value="<?php echo $open; ?>"
                                   <?php echo $closed ? 'disabled' : ''; ?>>
                        </td>

                        <td>
                            <input type="time"
                                   name="hours[<?php echo $day; ?>][close]"
                                   value="<?php echo $close; ?>"
                                   <?php echo $closed ? 'disabled' : ''; ?>>
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
                <small>Toggle the switch to mark a day as closed. Times default to 8:00 AM - 5:00 PM.</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Business</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<script>
    /* Image preview */
    const imageInput   = document.getElementById('business_image');
    const imagePreview = document.getElementById('image-preview');
    const uploadArea   = document.getElementById('upload-area');
    const uploadText   = document.getElementById('upload-text');

    imageInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                uploadArea.classList.add('has-image');
                uploadText.textContent = file.name;
            };
            reader.readAsDataURL(file);
        }
    });

    /* Operation hours toggle */
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
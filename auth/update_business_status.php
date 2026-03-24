<?php
require_once '../config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$business_id = (int)($_GET['id'] ?? 0);
$errors = [];
$success = '';

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT b.*, c.name as category_name, COALESCE(u.full_name, u.name) as full_name, u.email as user_email 
                        FROM businesses b 
                        LEFT JOIN categories c ON b.category_id = c.id 
                        LEFT JOIN users u ON b.user_id = u.id 
                        WHERE b.id = ?");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$result = $stmt->get_result();
$business = $result->fetch_assoc();
$stmt->close();

if (!$business) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'] ?? '';
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    if (empty($new_status) || !in_array($new_status, ['pending', 'approved', 'rejected'])) {
        $errors[] = "Please select a valid status";
    }
    
    if (empty($errors)) {
        $updated_at = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("UPDATE businesses SET status = ?, admin_notes = ?, updated_at = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_status, $admin_notes, $updated_at, $business_id);
        
        if ($stmt->execute()) {
            $success = "Business status updated successfully!";
            
            try {
                $log_stmt = $conn->prepare("INSERT INTO business_status_logs (business_id, old_status, new_status, changed_by, notes, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                if ($log_stmt) {
                    $log_stmt->bind_param("ississ", $business_id, $business['status'], $new_status, $_SESSION['user_id'], $admin_notes, $updated_at);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            } catch (Exception $e) {
            }
            
            $business['status'] = $new_status;
            $business['admin_notes'] = $admin_notes;
        } else {
            $errors[] = "Failed to update status: " . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Business Status</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .back-btn {
            color: white;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-btn:hover { opacity: 0.8; }
        
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .business-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .status-current {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }
        
        .status-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .status-option {
            position: relative;
        }
        
        .status-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .status-option label {
            display: block;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .status-option input[type="radio"]:checked + label {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .status-option label:hover {
            border-color: #667eea;
        }
        
        .status-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            min-height: 120px;
            resize: vertical;
        }
        
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error-messages {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .error-messages ul {
            list-style: none;
        }
        
        .error-messages li {
            color: #721c24;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .error-messages li:before {
            content: "⚠ ";
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            color: #155724;
            font-size: 14px;
        }
        
        .success-message:before {
            content: "✓ ";
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #f8f9ff;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Update Business Status</h1>
            <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Business Details</h2>
            
            <div class="business-info">
                <div class="info-row">
                    <div class="info-label">Business Name:</div>
                    <div class="info-value"><strong><?php echo htmlspecialchars($business['name']); ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Category:</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['category_name']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Owner:</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($business['full_name'] ?? 'Unknown'); ?>
                        (<?php echo htmlspecialchars($business['user_email']); ?>)
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['phone']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Location:</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['address']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date Added:</div>
                    <div class="info-value"><?php echo date('F d, Y', strtotime($business['created_at'])); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Current Status:</div>
                    <div class="info-value">
                        <span class="status-current status-<?php echo $business['status']; ?>">
                            <?php echo ucfirst($business['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Description:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($business['description'])); ?></div>
                </div>
            </div>
            
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
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Update Status</label>
                    <div class="status-options">
                        <div class="status-option">
                            <input type="radio" name="status" id="status_pending" value="pending" 
                                   <?php echo $business['status'] === 'pending' ? 'checked' : ''; ?>>
                            <label for="status_pending">
                                <div class="status-icon">⏳</div>
                                <div>Pending</div>
                            </label>
                        </div>
                        
                        <div class="status-option">
                            <input type="radio" name="status" id="status_approved" value="approved" 
                                   <?php echo $business['status'] === 'approved' ? 'checked' : ''; ?>>
                            <label for="status_approved">
                                <div class="status-icon">✅</div>
                                <div>Approved</div>
                            </label>
                        </div>
                        
                        <div class="status-option">
                            <input type="radio" name="status" id="status_rejected" value="rejected" 
                                   <?php echo $business['status'] === 'rejected' ? 'checked' : ''; ?>>
                            <label for="status_rejected">
                                <div class="status-icon">❌</div>
                                <div>Rejected</div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="admin_notes">Admin Notes (Optional)</label>
                    <textarea id="admin_notes" name="admin_notes" 
                              placeholder="Add any notes about this status update..."><?php echo htmlspecialchars($business['admin_notes'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Update Status</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
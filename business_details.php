<?php
require_once 'config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$conn = getDBConnection();
$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($business_id <= 0) {
    header('Location: categories.php');
    exit;
}

// Get business details
$stmt = $conn->prepare("SELECT b.*, c.name AS category_name FROM businesses b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ? AND b.status = 'approved'");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$result = $stmt->get_result();
$business = $result->fetch_assoc();
$stmt->close();

if (!$business) {
    header('Location: categories.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($business['name']); ?> - Zaddy Business Directory</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            cursor: pointer;
        }

        .header-nav {
            display: flex;
            gap: 20px;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: opacity 0.3s;
        }

        .header-nav a:hover { opacity: 0.8; }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .back-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .back-link:hover { text-decoration: underline; }

        .business-hero {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .business-hero img {
            width: 100%;
            max-height: 350px;
            object-fit: cover;
        }

        .no-image-hero {
            background: linear-gradient(135deg, #2c3e50, #4a90e2);
            color: white;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
        }

        .business-main-info {
            padding: 30px;
        }

        .business-badge {
            display: inline-block;
            background: #e8f4fd;
            color: #4a90e2;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .business-name {
            font-size: 32px;
            color: #222;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .business-description {
            color: #555;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 25px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 600px) {
            .details-grid { grid-template-columns: 1fr; }
            .business-name { font-size: 24px; }
        }

        .detail-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .detail-icon {
            font-size: 26px;
            flex-shrink: 0;
        }

        .detail-label {
            font-size: 11px;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }

        .detail-value a {
            color: #4a90e2;
            text-decoration: none;
        }

        .detail-value a:hover { text-decoration: underline; }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.2s, transform 0.2s;
            cursor: pointer;
        }

        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .btn-primary { background: #4a90e2; color: white; }
        .btn-success { background: #25D366; color: white; }
        .btn-outline {
            background: white;
            color: #4a90e2;
            border: 2px solid #4a90e2;
        }

        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <h1 onclick="location.href='index.php'">Zaddy Business Directory</h1>
        <nav class="header-nav">
            <a href="index.php">Home</a>
            <a href="categories.php">Categories</a>
            <a href="search.php">Search</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="auth/dashboard.php">Dashboard</a>
                <a href="auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<main class="container">
    <a href="javascript:history.back()" class="back-link">← Back</a>

    <div class="business-hero">
        <?php if (!empty($business['image'])): ?>
            <img src="uploads/<?php echo htmlspecialchars($business['image']); ?>" alt="<?php echo htmlspecialchars($business['name']); ?>">
        <?php else: ?>
            <div class="no-image-hero">🏢</div>
        <?php endif; ?>

        <div class="business-main-info">
            <?php if (!empty($business['category_name'])): ?>
                <span class="business-badge"><?php echo htmlspecialchars($business['category_name']); ?></span>
            <?php endif; ?>

            <h1 class="business-name"><?php echo htmlspecialchars($business['name']); ?></h1>

            <?php if (!empty($business['description'])): ?>
                <p class="business-description"><?php echo nl2br(htmlspecialchars($business['description'])); ?></p>
            <?php endif; ?>

            <div class="details-grid">
                <?php if (!empty($business['address'])): ?>
                <div class="detail-card">
                    <div class="detail-icon">📍</div>
                    <div>
                        <div class="detail-label">Location</div>
                        <div class="detail-value"><?php echo htmlspecialchars($business['address']); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($business['phone'])): ?>
                <div class="detail-card">
                    <div class="detail-icon">📞</div>
                    <div>
                        <div class="detail-label">Phone</div>
                        <div class="detail-value">
                            <a href="tel:<?php echo htmlspecialchars($business['phone']); ?>">
                                <?php echo htmlspecialchars($business['phone']); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($business['email'])): ?>
                <div class="detail-card">
                    <div class="detail-icon">✉️</div>
                    <div>
                        <div class="detail-label">Email</div>
                        <div class="detail-value">
                            <a href="mailto:<?php echo htmlspecialchars($business['email']); ?>">
                                <?php echo htmlspecialchars($business['email']); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($business['website'])): ?>
                <div class="detail-card">
                    <div class="detail-icon">🌐</div>
                    <div>
                        <div class="detail-label">Website</div>
                        <div class="detail-value">
                            <a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank" rel="noopener">
                                Visit Website
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if (!empty($business['phone'])): ?>
                    <a href="tel:<?php echo htmlspecialchars($business['phone']); ?>" class="btn btn-primary">📞 Call Now</a>
                    <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $business['phone']); ?>" target="_blank" class="btn btn-success">💬 WhatsApp</a>
                <?php endif; ?>
                <?php if (!empty($business['email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($business['email']); ?>" class="btn btn-outline">✉️ Send Email</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<div class="footer">
    <p>&copy; <?php echo date('Y'); ?> Zaddy Business Directory. All rights reserved.</p>
</div>

</body>
</html>

<?php $conn->close(); ?>
<?php
require_once 'config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$conn = getDBConnection();
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get category details
if ($category_id > 0) {
    $cat_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $cat_stmt->bind_param("i", $category_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    $category = $cat_result->fetch_assoc();
    $cat_stmt->close();
    
    // Get ONLY APPROVED businesses in this specific category
    $biz_stmt = $conn->prepare("SELECT * FROM businesses WHERE category_id = ? AND status = 'approved' ORDER BY name ASC");
    $biz_stmt->bind_param("i", $category_id);
    $biz_stmt->execute();
    $businesses_result = $biz_stmt->get_result();
} else {
    // Show all categories
    $all_cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category_id > 0 && $category ? htmlspecialchars($category['name']) : 'All Categories'; ?> - Zaddy Business Directory</title>
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
            max-width: 1200px;
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
        
        .category-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .category-header h2 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .category-header p {
            color: #666;
            font-size: 14px;
        }
        
        .business-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .business-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        
        .business-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .no-image {
            background: #2c3e50;
            color: white;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }
        
        .business-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .business-info {
            padding: 20px;
        }
        
        .business-info h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .business-info p {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .btn {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background: #4a90e2;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            transition: opacity 0.3s;
        }
        
        .btn:hover { opacity: 0.9; }
        
        .no-results {
            background: white;
            padding: 60px 20px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .no-results-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .no-results h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .no-results p {
            color: #666;
            font-size: 14px;
        }
        
        /* All Categories Grid */
        .page-title {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .category-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .category-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .category-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
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
        <?php if ($category_id > 0 && $category): ?>
            <!-- Single Category View -->
            <a href="categories.php" class="back-link">← Back to all categories</a>
            
            <div class="category-header">
                <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                <p>
                    <?php echo $businesses_result->num_rows; ?> 
                    <?php echo $businesses_result->num_rows == 1 ? 'business' : 'businesses'; ?> found
                </p>
            </div>

            <?php if ($businesses_result->num_rows > 0): ?>
                <div class="business-grid">
                    <?php while($business = $businesses_result->fetch_assoc()): ?>
                        <div class="business-card" onclick="location.href='business_details.php?id=<?php echo $business['id']; ?>'">
                            <?php if(!empty($business['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($business['image']); ?>" alt="<?php echo htmlspecialchars($business['name']); ?>">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                            <div class="business-info">
                                <h3><?php echo htmlspecialchars($business['name']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($business['description'], 0, 100)); ?>...</p>
                                <p>📍 <?php echo htmlspecialchars($business['address']); ?></p>
                                <p>📞 <?php echo htmlspecialchars($business['phone']); ?></p>
                                <a href="business_details.php?id=<?php echo $business['id']; ?>" class="btn" onclick="event.stopPropagation()">View Details</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">🏢</div>
                    <h3>No businesses found</h3>
                    <p>There are currently no approved businesses in this category.</p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- All Categories View -->
            <h1 class="page-title">All Categories</h1>
            <p class="page-subtitle">Browse businesses by category</p>

            <div class="categories-grid">
                <?php 
                $icons = [
                    'Automotive' => '🚗',
                    'Banks'      => '🏦',
                    'Education' => '🎓',
                    'Entertainment' => '🎬',
                    'Health & Medical' => '🏥',
                    'Hotels' => '🏨',
                    'Restaurants' => '🍽️',
                    'Services' => '🔧',
                    'Shopping' => '🛍️',
                    'Bakery' => '🥐',
                    'Professional Services' => '💼',
                    'Beauty & Wellness' => '💅',


                ];
                
                while($cat = $all_cats->fetch_assoc()): 
                    $icon = $icons[$cat['name']] ?? '🏢';
                ?>
                    <a href="categories.php?id=<?php echo $cat['id']; ?>" class="category-card">
                        <div class="category-icon"><?php echo $icon; ?></div>
                        <div class="category-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>

    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Zaddy Business Directory. All rights reserved.</p>
    </div>
</body>
</html>

<?php 
if (isset($biz_stmt)) $biz_stmt->close();
$conn->close(); 
?>
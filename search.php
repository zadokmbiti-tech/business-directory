<?php
require_once 'config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$conn = getDBConnection();
// Handle both 'q' and 'query' parameters for compatibility
$search_query = trim($_GET['q'] ?? $_GET['query'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$results = [];
$total_results = 0;

// Get all categories for filter dropdown
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Perform search if query is provided
if (!empty($search_query) || $category_filter > 0) {
    $sql = "SELECT b.*, c.name as category_name, c.icon as category_icon 
            FROM businesses b 
            LEFT JOIN categories c ON b.category_id = c.id 
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (!empty($search_query)) {
        $sql .= " AND (b.name LIKE ? OR b.description LIKE ? OR b.address LIKE ?)";
        $search_param = "%{$search_query}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    if ($category_filter > 0) {
        $sql .= " AND b.category_id = ?";
        $params[] = $category_filter;
        $types .= "i";
    }
    
    $sql .= " ORDER BY b.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    
    $total_results = count($results);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Zaddy Business Directory</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .header-nav a:hover {
            opacity: 0.8;
        }
        
        .search-section {
            background: white;
            padding: 30px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .category-select {
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        .category-select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
        }
        
        .results-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .results-header {
            margin-bottom: 20px;
        }
        
        .results-header h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .results-count {
            color: #666;
            font-size: 14px;
        }
        
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
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .business-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        
        .business-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .business-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .business-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .business-category {
            display: inline-block;
            padding: 5px 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .business-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .business-info {
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #666;
        }
        
        .info-icon {
            width: 16px;
            text-align: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #e65100;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 onclick="location.href='index.php'">Zaddy Business Directory</h1>
            <div class="header-nav">
                <a href="index.php">Home</a>
                <a href="categories.php">Categories</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="auth/dashboard.php">Dashboard</a>
                    <a href="auth/logout.php">Logout</a>
                <?php else: ?>
                    <a href="auth/login.php">Login</a>
                    <a href="auth/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="search-section">
        <div class="search-container">
            <form method="GET" action="search.php" class="search-form">
                <input 
                    type="text" 
                    name="query" 
                    class="search-input" 
                    placeholder="Search businesses, services, or products..."
                    value="<?php echo htmlspecialchars($search_query); ?>"
                >
                
                <select name="category" class="category-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>
    </div>
    
    <div class="results-container">
        <?php if (!empty($search_query) || $category_filter > 0): ?>
            <div class="results-header">
                <h2>Search Results</h2>
                <p class="results-count">
                    Found <?php echo $total_results; ?> 
                    <?php echo $total_results == 1 ? 'business' : 'businesses'; ?>
                    <?php if (!empty($search_query)): ?>
                        for "<?php echo htmlspecialchars($search_query); ?>"
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (empty($results)): ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>No businesses found</h3>
                    <p>Try adjusting your search terms or browse all categories</p>
                </div>
            <?php else: ?>
                <div class="results-grid">
                    <?php foreach ($results as $business): ?>
                        <div class="business-card" onclick="location.href='business.php?id=<?php echo $business['id']; ?>'">
                            <div class="business-header">
                                <div>
                                    <div class="business-name">
                                        <?php echo htmlspecialchars($business['name']); ?>
                                    </div>
                                </div>
                                <span class="business-category">
                                    <?php echo htmlspecialchars($business['category_name']); ?>
                                </span>
                            </div>
                            
                            <div class="business-description">
                                <?php echo htmlspecialchars($business['description']); ?>
                            </div>
                            
                            <div class="business-info">
                                <?php if (!empty($business['address'])): ?>
                                    <div class="info-item">
                                        <span class="info-icon">📍</span>
                                        <?php echo htmlspecialchars($business['address']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($business['phone'])): ?>
                                    <div class="info-item">
                                        <span class="info-icon">📞</span>
                                        <?php echo htmlspecialchars($business['phone']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php 
                                // Display approval status badge
                                $approval_status = $business['approval_status'] ?? $business['status'] ?? 'pending';
                                $approval_class = '';
                                $approval_text = '';
                                
                                if ($approval_status == 'approved') {
                                    $approval_class = 'status-active';
                                    $approval_text = '✓ Approved';
                                } elseif ($approval_status == 'rejected') {
                                    $approval_class = 'status-inactive';
                                    $approval_text = '✗ Rejected';
                                } else {
                                    $approval_class = 'status-pending';
                                    $approval_text = '⏳ Pending';
                                }
                                ?>
                                <span class="status-badge <?php echo $approval_class; ?>">
                                    <?php echo $approval_text; ?>
                                </span>
                                
                                <?php 
                                // Display open/closed status if available
                                if (isset($business['is_open']) || isset($business['operating_status'])) {
                                    $is_open = $business['is_open'] ?? $business['operating_status'] ?? 'open';
                                    $open_class = ($is_open == 'open' || $is_open == '1') ? 'status-active' : 'status-inactive';
                                    $open_text = ($is_open == 'open' || $is_open == '1') ? '🟢 Open' : '🔴 Closed';
                                ?>
                                    <span class="status-badge <?php echo $open_class; ?>">
                                        <?php echo $open_text; ?>
                                    </span>
                                <?php } ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>Start Searching</h3>
                <p>Enter a search term or select a category to find businesses</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
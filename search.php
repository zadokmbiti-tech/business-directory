<?php
require_once 'config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$conn = getDBConnection();
$search_query    = trim($_GET['q'] ?? $_GET['query'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$results         = [];
$total_results   = 0;

$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

if (!empty($search_query) || $category_filter > 0) {
    $sql = "SELECT b.*, c.name as category_name, c.icon as category_icon,
                   ROUND(COALESCE(AVG(r.rating), 0), 1) AS avg_rating,
                   COUNT(r.id) AS review_count
            FROM businesses b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN reviews r ON r.business_id = b.id
            WHERE b.status = 'approved'";

    $params = [];
    $types  = '';

    if (!empty($search_query)) {
        $sql .= " AND (b.name LIKE ? OR b.description LIKE ? OR b.address LIKE ?)";
        $p = "%{$search_query}%";
        $params[] = $p; $params[] = $p; $params[] = $p;
        $types .= 'sss';
    }

    if ($category_filter > 0) {
        $sql .= " AND b.category_id = ?";
        $params[] = $category_filter;
        $types   .= 'i';
    }

    $sql .= " GROUP BY b.id ORDER BY avg_rating DESC, b.created_at DESC";

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

function renderStars(float $rating): string {
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i)            $html .= '<span class="star filled">&#9733;</span>';
        elseif ($rating >= $i - 0.5) $html .= '<span class="star half">&#9733;</span>';
        else                          $html .= '<span class="star empty">&#9733;</span>';
    }
    return $html . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Zaddy Business Directory</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px; margin: 0 auto; padding: 0 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { font-size: 24px; cursor: pointer; }
        .header-nav { display: flex; gap: 20px; }
        .header-nav a { color: white; text-decoration: none; font-size: 14px; transition: opacity 0.3s; }
        .header-nav a:hover { opacity: 0.8; }

        .search-section { background: white; padding: 30px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .search-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .search-form { display: flex; gap: 10px; flex-wrap: wrap; }
        .search-input {
            flex: 1; min-width: 250px; padding: 12px 15px;
            border: 2px solid #ddd; border-radius: 5px; font-size: 14px;
        }
        .search-input:focus { outline: none; border-color: #667eea; }
        .category-select {
            padding: 12px 15px; border: 2px solid #ddd; border-radius: 5px;
            font-size: 14px; background: white; cursor: pointer;
        }
        .category-select:focus { outline: none; border-color: #667eea; }
        .search-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 5px;
            font-size: 14px; font-weight: bold; cursor: pointer; transition: transform 0.2s;
        }
        .search-btn:hover { transform: translateY(-2px); }

        .results-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .results-header { margin-bottom: 20px; }
        .results-header h2 { color: #333; font-size: 24px; margin-bottom: 5px; }
        .results-count { color: #666; font-size: 14px; }

        .no-results {
            background: white; padding: 60px 20px; text-align: center;
            border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .no-results-icon { font-size: 64px; margin-bottom: 20px; }
        .no-results h3 { color: #333; font-size: 24px; margin-bottom: 10px; }
        .no-results p  { color: #666; font-size: 14px; }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }

        /* Card */
        .business-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer; overflow: hidden;
            display: flex; flex-direction: column;
        }
        .business-card:hover { transform: translateY(-5px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }

        .card-img { width: 100%; height: 160px; object-fit: cover; display: block; }
        .card-img-placeholder {
            width: 100%; height: 160px;
            background: linear-gradient(135deg, #667eea22, #764ba222);
            display: flex; align-items: center; justify-content: center;
            font-size: 48px;
        }

        .card-body { padding: 18px 20px 20px; flex: 1; display: flex; flex-direction: column; gap: 10px; }

        .card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
        .business-name { font-size: 17px; font-weight: 700; color: #222; line-height: 1.3; }
        .business-category {
            flex-shrink: 0; padding: 4px 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; border-radius: 99px;
            font-size: 11px; font-weight: 700; white-space: nowrap;
        }

        /* Stars */
        .rating-row { display: flex; align-items: center; gap: 7px; }
        .stars { display: inline-flex; }
        .star        { font-size: 15px; color: #ddd; line-height: 1; }
        .star.filled { color: #f5c518; }
        .star.half   { position: relative; color: #ddd; }
        .star.half::after {
            content: '\2605'; position: absolute; left: 0; top: 0;
            color: #f5c518; width: 50%; overflow: hidden; display: block;
        }
        .rating-score  { font-size: 13px; font-weight: 700; color: #f5a800; }
        .rating-count  { font-size: 12px; color: #999; }
        .no-rating     { font-size: 12px; color: #bbb; font-style: italic; }

        .business-description {
            color: #666; font-size: 13px; line-height: 1.6;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }

        .business-info {
            display: flex; flex-direction: column; gap: 5px;
            margin-top: auto; padding-top: 12px; border-top: 1px solid #f0f0f0;
        }
        .info-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #666; }

        /* Open/closed badge */
        .open-now-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 9px; border-radius: 99px;
            font-size: 11px; font-weight: 700; margin-top: 4px;
        }
        .open-now-badge.open   { background: #e8f5e9; color: #2e7d32; }
        .open-now-badge.closed { background: #ffebee; color: #c62828; }
        .open-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
        .open-now-badge.open   .open-dot { background: #22c55e; }
        .open-now-badge.closed .open-dot { background: #ef4444; }
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
            <input type="text" name="query" class="search-input"
                   placeholder="Search businesses, services, or products..."
                   value="<?php echo htmlspecialchars($search_query); ?>">
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
                <div class="no-results-icon">&#128269;</div>
                <h3>No businesses found</h3>
                <p>Try adjusting your search terms or browse all categories</p>
            </div>
        <?php else: ?>
            <div class="results-grid">
                <?php foreach ($results as $biz):

                    // Open/closed from operation_hours JSON
                    $is_open_now = null;
                    $today       = date('l');
                    $now_mins    = (int)date('H') * 60 + (int)date('i');

                    if (!empty($biz['operation_hours'])) {
                        $oh = json_decode($biz['operation_hours'], true) ?? [];
                        if (!empty($oh[$today])) {
                            $day_data = $oh[$today];
                            if (!empty($day_data['closed'])) {
                                $is_open_now = false;
                            } else {
                                $po = explode(':', $day_data['open']  ?? '00:00');
                                $pc = explode(':', $day_data['close'] ?? '00:00');
                                $om = (int)$po[0] * 60 + (int)($po[1] ?? 0);
                                $cm = (int)$pc[0] * 60 + (int)($pc[1] ?? 0);
                                $is_open_now = $now_mins >= $om && $now_mins < $cm;
                            }
                        }
                    }

                    $avg_rating   = (float)($biz['avg_rating']   ?? 0);
                    $review_count = (int)  ($biz['review_count'] ?? 0);
                ?>
                <div class="business-card"
                     onclick="location.href='business_details.php?id=<?php echo $biz['id']; ?>'">

                    <?php if (!empty($biz['image']) && file_exists('uploads/' . $biz['image'])): ?>
                        <img class="card-img"
                             src="uploads/<?php echo htmlspecialchars($biz['image']); ?>"
                             alt="<?php echo htmlspecialchars($biz['name']); ?>">
                    <?php else: ?>
                        <div class="card-img-placeholder">&#127962;</div>
                    <?php endif; ?>

                    <div class="card-body">

                        <div class="card-top">
                            <div class="business-name"><?php echo htmlspecialchars($biz['name']); ?></div>
                            <?php if (!empty($biz['category_name'])): ?>
                                <span class="business-category"><?php echo htmlspecialchars($biz['category_name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Star rating -->
                        <div class="rating-row">
                            <?php echo renderStars($avg_rating); ?>
                            <?php if ($review_count > 0): ?>
                                <span class="rating-score"><?php echo number_format($avg_rating, 1); ?></span>
                                <span class="rating-count">(<?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?>)</span>
                            <?php else: ?>
                                <span class="no-rating">No reviews yet</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($biz['description'])): ?>
                            <div class="business-description"><?php echo htmlspecialchars($biz['description']); ?></div>
                        <?php endif; ?>

                        <div class="business-info">
                            <?php if (!empty($biz['address'])): ?>
                                <div class="info-item"><span>&#128205;</span><?php echo htmlspecialchars($biz['address']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($biz['phone'])): ?>
                                <div class="info-item"><span>&#128222;</span><?php echo htmlspecialchars($biz['phone']); ?></div>
                            <?php endif; ?>
                            <?php if ($is_open_now !== null): ?>
                                <span class="open-now-badge <?php echo $is_open_now ? 'open' : 'closed'; ?>">
                                    <span class="open-dot"></span>
                                    <?php echo $is_open_now ? 'Open Now' : 'Closed Now'; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-results">
            <div class="no-results-icon">&#128269;</div>
            <h3>Start Searching</h3>
            <p>Enter a search term or select a category to find businesses</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
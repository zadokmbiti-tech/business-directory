<?php
require_once 'config/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$conn        = getDBConnection();
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ── Star helper ──────────────────────────────────────────────────────────────
function renderStars(float $rating): string {
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i)            $html .= '<span class="star filled">&#9733;</span>';
        elseif ($rating >= $i - 0.5) $html .= '<span class="star half">&#9733;</span>';
        else                          $html .= '<span class="star empty">&#9733;</span>';
    }
    return $html . '</span>';
}

// ── Open/closed helper ───────────────────────────────────────────────────────
function getOpenStatus(?string $operation_hours_json): ?bool {
    if (empty($operation_hours_json)) return null;
    $oh = json_decode($operation_hours_json, true) ?? [];
    $today    = date('l');
    $now_mins = (int)date('H') * 60 + (int)date('i');
    if (empty($oh[$today])) return null;
    $d = $oh[$today];
    if (!empty($d['closed'])) return false;
    $po = explode(':', $d['open']  ?? '00:00');
    $pc = explode(':', $d['close'] ?? '00:00');
    $om = (int)$po[0] * 60 + (int)($po[1] ?? 0);
    $cm = (int)$pc[0] * 60 + (int)($pc[1] ?? 0);
    return $now_mins >= $om && $now_mins < $cm;
}

$category   = null;
$businesses = [];

if ($category_id > 0) {
    // ── Fetch category ───────────────────────────────────────────────────────
    $cat_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $cat_stmt->bind_param("i", $category_id);
    $cat_stmt->execute();
    $category = $cat_stmt->get_result()->fetch_assoc();
    $cat_stmt->close();

    if ($category) {
        // ── Fetch approved businesses + rating in one query ──────────────────
        $biz_stmt = $conn->prepare(
            "SELECT b.*,
                    ROUND(COALESCE(AVG(r.rating), 0), 1) AS avg_rating,
                    COUNT(r.id) AS review_count
             FROM businesses b
             LEFT JOIN reviews r ON r.business_id = b.id AND r.status = 'approved'
             WHERE b.category_id = ? AND b.status = 'approved'
             GROUP BY b.id
             ORDER BY avg_rating DESC, b.name ASC"
        );
        $biz_stmt->bind_param("i", $category_id);
        $biz_stmt->execute();
        $res = $biz_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $businesses[] = $row;
        }
        $biz_stmt->close();
    }
} else {
    // ── All categories with business count ───────────────────────────────────
    $all_cats = $conn->query(
        "SELECT c.*, COUNT(b.id) AS biz_count
         FROM categories c
         LEFT JOIN businesses b ON b.category_id = c.id AND b.status = 'approved'
         GROUP BY c.id
         ORDER BY c.name ASC"
    );
}

$conn->close();

$icons = [
    'Automotive'           => '&#128663;',
    'Banks'                => '&#127970;',
    'Education'            => '&#127891;',
    'Entertainment'        => '&#127916;',
    'Health & Medical'     => '&#127973;',
    'Hotels'               => '&#127976;',
    'Restaurants'          => '&#127869;',
    'Services'             => '&#128295;',
    'Shopping'             => '&#128717;',
    'Bakery'               => '&#129360;',
    'Professional Services'=> '&#128188;',
    'Beauty & Wellness'    => '&#128133;',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($category_id > 0 && $category) ? htmlspecialchars($category['name']) : 'All Categories'; ?> – Zaddy Business Directory</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ── Page shell ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }

        /* ── Reuse header/footer from style.css; extras only ── */
        .page-title    { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 6px; }
        .page-subtitle { color: #888; font-size: 14px; margin-bottom: 28px; }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #667eea; text-decoration: none; font-size: 14px;
            font-weight: 600; margin-bottom: 20px;
        }
        .back-link:hover { text-decoration: underline; }

        .category-header { margin-bottom: 24px; }
        .category-header h2 { font-size: 24px; color: #333; margin-bottom: 4px; }
        .category-header p  { color: #888; font-size: 14px; }

        /* ── All-categories grid ── */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
        }
        .category-card {
            background: white; border-radius: 12px;
            padding: 28px 16px; text-align: center;
            text-decoration: none; color: #333;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex; flex-direction: column; align-items: center; gap: 10px;
        }
        .category-card:hover { transform: translateY(-4px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .category-icon { font-size: 36px; line-height: 1; }
        .category-name { font-size: 13px; font-weight: 700; color: #444; }
        .category-count {
            font-size: 11px; color: #fff;
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 2px 9px; border-radius: 99px; font-weight: 700;
        }

        /* ── Business grid ── */
        .business-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        /* ── Business card ── */
        .biz-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            overflow: hidden; cursor: pointer;
            display: flex; flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .biz-card:hover { transform: translateY(-5px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }

        .biz-img { width: 100%; height: 170px; object-fit: cover; display: block; }
        .biz-img-placeholder {
            width: 100%; height: 170px;
            background: linear-gradient(135deg, #667eea22, #764ba222);
            display: flex; align-items: center; justify-content: center;
            font-size: 52px;
        }

        .biz-body { padding: 16px 18px 18px; flex: 1; display: flex; flex-direction: column; gap: 9px; }

        .biz-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
        .biz-name { font-size: 16px; font-weight: 700; color: #222; line-height: 1.3; }

        /* Open/closed badge */
        .open-badge {
            flex-shrink: 0;
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 9px; border-radius: 99px;
            font-size: 11px; font-weight: 700; white-space: nowrap;
        }
        .open-badge.open   { background: #e8f5e9; color: #2e7d32; }
        .open-badge.closed { background: #ffebee; color: #c62828; }
        .open-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
        .open-badge.open   .open-dot { background: #22c55e; }
        .open-badge.closed .open-dot { background: #ef4444; }

        /* Stars */
        .rating-row { display: flex; align-items: center; gap: 6px; }
        .stars { display: inline-flex; }
        .star        { font-size: 14px; color: #ddd; line-height: 1; }
        .star.filled { color: #f5c518; }
        .star.half   { position: relative; color: #ddd; }
        .star.half::after {
            content: '\2605'; position: absolute; left: 0; top: 0;
            color: #f5c518; width: 50%; overflow: hidden; display: block;
        }
        .rating-score { font-size: 13px; font-weight: 700; color: #f5a800; }
        .rating-count { font-size: 12px; color: #999; }
        .no-rating    { font-size: 12px; color: #bbb; font-style: italic; }

        /* Description */
        .biz-desc {
            color: #666; font-size: 13px; line-height: 1.6;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }

        /* Info rows */
        .biz-info { display: flex; flex-direction: column; gap: 5px; padding-top: 10px; border-top: 1px solid #f0f0f0; margin-top: auto; }
        .info-row { display: flex; align-items: center; gap: 7px; font-size: 12px; color: #666; }

        /* View button */
        .view-btn {
            display: inline-block; margin-top: 10px;
            padding: 8px 18px; border-radius: 6px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; font-size: 13px; font-weight: 600;
            text-decoration: none; text-align: center;
            transition: opacity 0.2s;
        }
        .view-btn:hover { opacity: 0.88; }

        /* No results */
        .no-results {
            background: white; padding: 60px 20px; text-align: center;
            border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .no-results-icon { font-size: 56px; margin-bottom: 16px; }
        .no-results h3 { color: #333; font-size: 22px; margin-bottom: 8px; }
        .no-results p  { color: #888; font-size: 14px; }
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
        <!-- ── SINGLE CATEGORY VIEW ── -->
        <a href="categories.php" class="back-link">&#8592; Back to all categories</a>

        <div class="category-header">
            <h2><?php echo htmlspecialchars($category['name']); ?></h2>
            <p>
                <?php echo count($businesses); ?>
                <?php echo count($businesses) == 1 ? 'business' : 'businesses'; ?> found
            </p>
        </div>

        <?php if (!empty($businesses)): ?>
            <div class="business-grid">
                <?php foreach ($businesses as $biz):
                    $avg_rating   = (float)($biz['avg_rating']   ?? 0);
                    $review_count = (int)  ($biz['review_count'] ?? 0);
                    $open_status  = getOpenStatus($biz['operation_hours'] ?? null);
                ?>
                <div class="biz-card"
                     onclick="location.href='business_details.php?id=<?php echo $biz['id']; ?>'">

                    <!-- Image -->
                    <?php if (!empty($biz['image']) && file_exists('uploads/' . $biz['image'])): ?>
                        <img class="biz-img"
                             src="uploads/<?php echo htmlspecialchars($biz['image']); ?>"
                             alt="<?php echo htmlspecialchars($biz['name']); ?>">
                    <?php else: ?>
                        <div class="biz-img-placeholder">&#127962;</div>
                    <?php endif; ?>

                    <div class="biz-body">

                        <!-- Name + open badge -->
                        <div class="biz-top">
                            <div class="biz-name"><?php echo htmlspecialchars($biz['name']); ?></div>
                            <?php if ($open_status !== null): ?>
                                <span class="open-badge <?php echo $open_status ? 'open' : 'closed'; ?>">
                                    <span class="open-dot"></span>
                                    <?php echo $open_status ? 'Open' : 'Closed'; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Stars -->
                        <div class="rating-row">
                            <?php echo renderStars($avg_rating); ?>
                            <?php if ($review_count > 0): ?>
                                <span class="rating-score"><?php echo number_format($avg_rating, 1); ?></span>
                                <span class="rating-count">(<?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?>)</span>
                            <?php else: ?>
                                <span class="no-rating">No reviews yet</span>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <?php if (!empty($biz['description'])): ?>
                            <div class="biz-desc"><?php echo htmlspecialchars($biz['description']); ?></div>
                        <?php endif; ?>

                        <!-- Info -->
                        <div class="biz-info">
                            <?php if (!empty($biz['address'])): ?>
                                <div class="info-row"><span>&#128205;</span><?php echo htmlspecialchars($biz['address']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($biz['phone'])): ?>
                                <div class="info-row"><span>&#128222;</span><?php echo htmlspecialchars($biz['phone']); ?></div>
                            <?php endif; ?>
                        </div>

                        <a href="business_details.php?id=<?php echo $biz['id']; ?>"
                           class="view-btn"
                           onclick="event.stopPropagation()">View Details</a>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="no-results">
                <div class="no-results-icon">&#127962;</div>
                <h3>No businesses found</h3>
                <p>There are currently no approved businesses in this category.</p>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- ── ALL CATEGORIES VIEW ── -->
        <h1 class="page-title">All Categories</h1>
        <p class="page-subtitle">Browse businesses by category</p>

        <div class="categories-grid">
            <?php while ($cat = $all_cats->fetch_assoc()):
                $icon = $icons[$cat['name']] ?? '&#127962;';
            ?>
            <a href="categories.php?id=<?php echo $cat['id']; ?>" class="category-card">
                <div class="category-icon"><?php echo $icon; ?></div>
                <div class="category-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                <span class="category-count">
                    <?php echo $cat['biz_count']; ?>
                    <?php echo $cat['biz_count'] == 1 ? 'business' : 'businesses'; ?>
                </span>
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
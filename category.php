<?php
require_once 'config/dbconnection.php';
$conn = getDBConnection();

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get category details
if ($category_id > 0) {
    $cat_result = $conn->query("SELECT * FROM categories WHERE id = $category_id");
    $category = $cat_result->fetch_assoc();
    
    // Get businesses in this category
    $businesses_result = $conn->query("SELECT * FROM businesses WHERE category_id = $category_id AND status = 'active' OR status='approved' ORDER BY name");
} else {
    // Show all categories
    $all_cats = $conn->query("SELECT * FROM categories ORDER BY name");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category_id > 0 ? htmlspecialchars($category['name']) : 'All Categories'; ?> - Zaddy Business Directory</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Zaddy Business Directory</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="categories.php">Categories</a>
            <a href="search.php">Search</a>
            <a href="auth/login.php">Login</a>
            <a href="auth/register.php">Register</a>
        </nav>
    </header>

    <main>
        <?php if ($category_id > 0 && $category): ?>
            <!-- Single Category View -->
            <section class="category-header">
                <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                <a href="categories.php">← Back to all categories</a>
            </section>

            <section class="businesses-list">
                <?php if ($businesses_result->num_rows > 0): ?>
                    <div class="business-grid">
                        <?php while($business = $businesses_result->fetch_assoc()): ?>
                            <div class="business-card">
                                <?php if($business['image']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($business['image']); ?>" alt="<?php echo htmlspecialchars($business['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">No Image</div>
                                <?php endif; ?>
                                <div class="business-info">
                                    <h3><?php echo htmlspecialchars($business['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($business['description']); ?></p>
                                    <p>📍 <?php echo htmlspecialchars($business['address']); ?></p>
                                    <p>📞 <?php echo htmlspecialchars($business['phone']); ?></p>
                                    <a href="business_details.php?id=<?php echo $business['id']; ?>" class="btn">View Details</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="no-results">No businesses found in this category yet.</p>
                <?php endif; ?>
            </section>

        <?php else: ?>
            <!-- All Categories View -->
            <section class="categories-header">
                <h2>All Categories</h2>
            </section>

            <section class="categories">
                <div class="category-grid">
                    <?php while($cat = $all_cats->fetch_assoc()): ?>
                        <div class="category-card">
                            <a href="categories.php?id=<?php echo $cat['id']; ?>">
                                <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                <h4><?php echo htmlspecialchars($cat['name']); ?></h4>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2026 Zaddy Business Directory. All rights reserved.</p>
    </footer>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>

<?php $conn->close(); ?>
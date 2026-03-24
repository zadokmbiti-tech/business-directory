<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/dbconnection.php';
$conn = getDBConnection();

// Get total businesses count
$result = $conn->query("SELECT COUNT(*) as total FROM businesses WHERE status='approved' OR status='active'");
$total_businesses = $result->fetch_assoc()['total'];

$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Directory - Find Local Businesses</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Zaddy Business Directory</h1>
        <nav>
    <a href="index.php">Home</a>
    <a href="categories.php">Categories</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="auth/dashboard.php">Dashboard</a>
        <a href="auth/logout.php">Logout</a>
    <?php else: ?>
        <a href="auth/login.php">Login</a>
        <a href="auth/register.php">Register</a>
    <?php endif; ?>
</nav>
    </header>

    <main>
        <section class="hero">
            <h2>Find Trusted Local Businesses</h2>
            <p>Discover <?php echo $total_businesses; ?> verified businesses in your area</p>
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="query" placeholder="Search for businesses...">
                <button type="submit">Search</button>
            </form>
        </section>

        <section class="categories">
            <h3>Browse by Category</h3>
            <div class="category-grid">
                <?php while($category = $categories_result->fetch_assoc()): ?>
                    <div class="category-card">
<a href="categories.php?id=<?php echo $category['id']; ?>" style="text-decoration:none; color:inherit;">                            <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 Zaddy Business Directory. All rights reserved.</p>
    </footer>
</body>
</html>

<?php $conn->close(); ?>
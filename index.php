<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/dbconnection.php';
$conn = getDBConnection();

$result = $conn->query("SELECT COUNT(*) as total FROM businesses WHERE status='approved' OR status='active'");
$total_businesses = $result->fetch_assoc()['total'];

$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaddy Business Directory — Find Local Businesses</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero {
            position: relative;
            overflow: hidden;
            background: #0d1b2a;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }
        .hero::after {
            content: '';
            position: absolute;
            top: -120px;
            left: 50%;
            transform: translateX(-50%);
            width: 700px;
            height: 500px;
            background: radial-gradient(ellipse, rgba(212,175,55,.18) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        .hero-skyline {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            pointer-events: none;
            z-index: 0;
            opacity: .25;
        }
        .hero-inner {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <a href="index.php" class="nav-logo">Zaddy<span>.</span></a>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="categories.php">Categories</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="auth/dashboard.php">Dashboard</a></li>
            <li><a href="auth/logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="auth/login.php">Login</a></li>
            <li><a href="auth/register.php" class="btn-register">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- HERO -->
<section class="hero">

    <!-- Inline SVG city skyline — zero external dependencies -->
    <svg class="hero-skyline" viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMax slice">
        <!-- Far buildings (darker, shorter) -->
        <rect x="0"    y="200" width="60"  height="120" fill="#d4af37" opacity=".4"/>
        <rect x="10"   y="170" width="40"  height="150" fill="#d4af37" opacity=".5"/>
        <rect x="70"   y="210" width="80"  height="110" fill="#d4af37" opacity=".35"/>
        <rect x="160"  y="180" width="50"  height="140" fill="#d4af37" opacity=".45"/>
        <rect x="165"  y="155" width="40"  height="165" fill="#d4af37" opacity=".3"/>
        <rect x="220"  y="195" width="70"  height="125" fill="#d4af37" opacity=".4"/>
        <rect x="300"  y="175" width="55"  height="145" fill="#d4af37" opacity=".5"/>
        <rect x="310"  y="145" width="35"  height="175" fill="#d4af37" opacity=".35"/>
        <!-- Antenna on tall building -->
        <rect x="325"  y="100" width="4"   height="45"  fill="#d4af37" opacity=".5"/>
        <rect x="365"  y="200" width="90"  height="120" fill="#d4af37" opacity=".4"/>
        <rect x="465"  y="185" width="65"  height="135" fill="#d4af37" opacity=".45"/>
        <rect x="540"  y="160" width="80"  height="160" fill="#d4af37" opacity=".5"/>
        <!-- Skyscraper -->
        <rect x="630"  y="110" width="55"  height="210" fill="#d4af37" opacity=".55"/>
        <rect x="640"  y="90"  width="35"  height="20"  fill="#d4af37" opacity=".55"/>
        <rect x="648"  y="60"  width="5"   height="30"  fill="#d4af37" opacity=".6"/>
        <!-- Windows on skyscraper -->
        <rect x="636"  y="130" width="8" height="10" fill="#0d1b2a" opacity=".6" rx="1"/>
        <rect x="650"  y="130" width="8" height="10" fill="#0d1b2a" opacity=".6" rx="1"/>
        <rect x="664"  y="130" width="8" height="10" fill="#0d1b2a" opacity=".6" rx="1"/>
        <rect x="636"  y="150" width="8" height="10" fill="#0d1b2a" opacity=".6" rx="1"/>
        <rect x="650"  y="150" width="8" height="10" fill="#0d1b2a" opacity=".6" rx="1"/>
        <rect x="664"  y="150" width="8" height="10" fill="#0d1b2a" opacity=".6" rx="1"/>
        <rect x="695"  y="190" width="70"  height="130" fill="#d4af37" opacity=".4"/>
        <rect x="775"  y="175" width="50"  height="145" fill="#d4af37" opacity=".45"/>
        <rect x="835"  y="200" width="85"  height="120" fill="#d4af37" opacity=".35"/>
        <rect x="850"  y="170" width="55"  height="150" fill="#d4af37" opacity=".5"/>
        <!-- Another tall tower -->
        <rect x="930"  y="120" width="60"  height="200" fill="#d4af37" opacity=".55"/>
        <rect x="937"  y="95"  width="46"  height="25"  fill="#d4af37" opacity=".55"/>
        <rect x="957"  y="70"  width="6"   height="25"  fill="#d4af37" opacity=".6"/>
        <rect x="1000" y="185" width="75"  height="135" fill="#d4af37" opacity=".4"/>
        <rect x="1085" y="200" width="60"  height="120" fill="#d4af37" opacity=".4"/>
        <rect x="1090" y="175" width="50"  height="145" fill="#d4af37" opacity=".5"/>
        <rect x="1155" y="190" width="80"  height="130" fill="#d4af37" opacity=".35"/>
        <rect x="1245" y="180" width="55"  height="140" fill="#d4af37" opacity=".45"/>
        <rect x="1250" y="155" width="45"  height="165" fill="#d4af37" opacity=".3"/>
        <rect x="1310" y="200" width="70"  height="120" fill="#d4af37" opacity=".4"/>
        <rect x="1385" y="185" width="55"  height="135" fill="#d4af37" opacity=".45"/>
        <!-- Ground strip -->
        <rect x="0" y="318" width="1440" height="2" fill="#d4af37" opacity=".3"/>
    </svg>

    <div class="hero-inner">
        <div class="hero-badge">Zaddy's Business Network</div>
        <h1>Find <em>Trusted</em><br>Local Businesses</h1>
        <p class="hero-sub">
            Discover <strong><?php echo $total_businesses; ?> verified businesses</strong> in your area.<br>
            From restaurants to professional services — all in one place.
        </p>

        <div class="search-wrap">
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="query" placeholder="Search businesses, services, categories...">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="hero-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $total_businesses; ?>+</span>
                <span class="stat-label">Businesses</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">10+</span>
                <span class="stat-label">Categories</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">100%</span>
                <span class="stat-label">Verified</span>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="categories-section">
    <div class="section-header">
        <div>
            <div class="section-label">Browse</div>
            <h2 class="section-title">Explore by Category</h2>
        </div>
        <a href="categories.php" class="section-link">View all →</a>
    </div>

    <?php
    $icons = [
        'automotive'            => '🚗',
        'bakery'                => '🥐',
        'banks'                 => '🏦',
        'beauty'                => '💄',
        'beauty & wellness'     => '💄',
        'education'             => '📚',
        'entertainment'         => '🎭',
        'health'                => '🏥',
        'health & medical'      => '🏥',
        'hotels'                => '🏨',
        'professional'          => '💼',
        'professional services' => '💼',
        'restaurants'           => '🍽️',
        'retail'                => '🛍️',
        'technology'            => '💻',
        'travel'                => '✈️',
        'fitness'               => '💪',
        'legal'                 => '⚖️',
        'real estate'           => '🏠',
        'finance'               => '💰',
        'construction'          => '🏗️',
        'grocery'               => '🛒',
        'pharmacy'              => '💊',
        'transport'             => '🚌',
    ];

    function getCategoryIcon($name, $icons) {
        $lower = strtolower(trim($name));
        foreach ($icons as $key => $icon) {
            if (str_contains($lower, $key)) return $icon;
        }
        return '🏢';
    }
    ?>

    <div class="category-grid">
        <?php
        while($category = $categories_result->fetch_assoc()):
            $icon = getCategoryIcon($category['name'], $icons);
        ?>
            <div class="category-card">
                <a href="categories.php?id=<?php echo $category['id']; ?>">
                    <div class="cat-icon"><?php echo $icon; ?></div>
                    <span class="cat-name"><?php echo htmlspecialchars($category['name']); ?></span>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- CTA BANNER -->
<div class="cta-banner">
    <div class="cta-text">
        <h2>Own a business?</h2>
        <p>List your business <strong>today</strong> and reach thousands of local customers today.</p>
    </div>
    <div class="cta-actions">
        <a href="auth/register.php" class="btn-gold">List Your Business →</a>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <a href="index.php" class="footer-logo">Zaddy<span>.</span></a>
    <p>&copy; 2026 Zaddy Business Directory. All rights reserved.</p>
</footer>

</body>
</html>

<?php $conn->close(); ?>

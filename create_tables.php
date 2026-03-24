<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "Businessdirectory";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>building the Business Directory Database...</h2>";
// 1. users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('customer', 'business_owner', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created successfully!<br>";
} else {
    echo "Error creating table: " . $conn->error;
}


// 2. Categories Table
$sql_categories = "CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_categories) === TRUE) {
    echo "✓ Table 'categories' created<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// 3. Businesses Table
$sql_businesses = "CREATE TABLE IF NOT EXISTS businesses (
    business_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    category_id INT NOT NULL,
    business_name VARCHAR(150) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    website VARCHAR(255),
    operating_hours VARCHAR(255),
    logo_image VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT
)";

if ($conn->query($sql_businesses) === TRUE) {
    echo "✓ Table 'businesses' created<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// 4. Reviews Table
$sql_reviews = "CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(business_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql_reviews) === TRUE) {
    echo "✓ Table 'reviews' created<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// 5. Admins Table for separate admin tracking
$sql_admins = "CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    permissions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql_admins) === TRUE) {
    echo "✓ Table 'admins' created<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

echo "<br><strong>Database setup complete!</strong><br>";
echo "<a href='http://localhost/phpmyadmin/'>View in phpMyAdmin</a>";

$conn->close();
?>
<?php
// Database Setup for Zaddy Business Directory
// Run this file ONCE to create all tables

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'business-directory';

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Setting up Business Directory Database...</h2>";

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS `$database`";
if ($conn->query($sql) === TRUE) {
    echo "✓ Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($database);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `user_type` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✓ Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✓ Categories table created successfully<br>";
} else {
    echo "Error creating categories table: " . $conn->error . "<br>";
}

// Create businesses table
$sql = "CREATE TABLE IF NOT EXISTS `businesses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` text,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✓ Businesses table created successfully<br>";
} else {
    echo "Error creating businesses table: " . $conn->error . "<br>";
}

// Create business_status_logs table (optional)
$sql = "CREATE TABLE IF NOT EXISTS `business_status_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `changed_by` (`changed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✓ Business status logs table created successfully<br>";
} else {
    echo "Error creating status logs table: " . $conn->error . "<br>";
}

// Insert default categories
$categories = [
    ['Restaurants', 'fa-utensils'],
    ['Hotels', 'fa-hotel'],
    ['Shopping', 'fa-shopping-bag'],
    ['Services', 'fa-wrench'],
    ['Education', 'fa-graduation-cap'],
    ['Health & Medical', 'fa-hospital'],
    ['Automotive', 'fa-car'],
    ['Entertainment', 'fa-film']
];

$stmt = $conn->prepare("INSERT INTO categories (name, icon) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=name");
foreach ($categories as $cat) {
    $stmt->bind_param("ss", $cat[0], $cat[1]);
    $stmt->execute();
}
echo "✓ Default categories inserted<br>";
$stmt->close();

// Create default admin user (username: admin, password: admin123)
$admin_username = 'admin';
$admin_email = 'admin@business-directory.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_name = 'Administrator';
$admin_type = 'admin';

$stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, user_type) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE username=username");
$stmt->bind_param("sssss", $admin_username, $admin_email, $admin_password, $admin_name, $admin_type);
if ($stmt->execute()) {
    echo "✓ Default admin user created (username: admin, password: admin123)<br>";
} else {
    echo "Admin user might already exist<br>";
}
$stmt->close();

$conn->close();

echo "<br><h3 style='color: green;'>✅ Database setup complete!</h3>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Go to <a href='index.php'>Homepage</a> to test the site</li>";
echo "<li>Login as admin: <strong>username: admin</strong> / <strong>password: admin123</strong></li>";
echo "<li>Change the admin password after logging in!</li>";
echo "<li>You can delete this setup_database.php file now for security</li>";
echo "</ol>";
?>
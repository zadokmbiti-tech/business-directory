<?php
require_once '../config/dbconnection.php';

$conn = getDBConnection();

// 1. Mark expired subscriptions
$conn->query("UPDATE subscriptions SET status = 'expired' 
              WHERE expiry_date < CURDATE() AND status = 'active'");

// 2. Deactivate businesses with no active subscription
$conn->query("UPDATE businesses SET status = 'inactive' 
              WHERE id NOT IN (
                  SELECT business_id FROM subscriptions WHERE status = 'active'
              )");

closeDBConnection($conn);
echo "Subscriptions checked: " . date('Y-m-d H:i:s') . PHP_EOL;
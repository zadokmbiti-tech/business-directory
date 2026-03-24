<?php
require_once '../config/dbconnection.php';

$conn = getDBConnection();

$userId = $_SESSION['user_id'] ?? 0;

$sql = "SELECT * FROM listings WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo $row['business_name'] . "<br>";
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html>
<head>
<title>My Listings</title>

<style>
body{
    font-family: Arial;
    background:#f4f6f9;
    padding:20px;
}

.card{
    background:white;
    padding:15px;
    margin-bottom:15px;
    border-radius:8px;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

.status{
    padding:5px 10px;
    border-radius:5px;
    color:white;
    font-size:12px;
}

.pending{background:orange;}
.approved{background:green;}
.rejected{background:red;}

.active{background:green;}
.inactive{background:gray;}

.btn{
    padding:6px 12px;
    text-decoration:none;
    border-radius:5px;
    font-size:13px;
}

.toggle{
    background:#007bff;
    color:white;
}

.edit{
    background:#ffc107;
    color:black;
}

.delete{
    background:#dc3545;
    color:white;
}
</style>

</head>
<body>

<h2>My Business Listings</h2>

<?php if ($result->num_rows > 0): ?>

<?php while($row = $result->fetch_assoc()): ?>

<div class="card">

<h3><?= htmlspecialchars($row['business_name']) ?></h3>

<p><?= htmlspecialchars($row['description']) ?></p>

<p>
Approval Status:
<span class="status <?= $row['approval_status'] ?>">
<?= ucfirst($row['approval_status']) ?>
</span>
</p>

<p>
Activity Status:
<span class="status <?= $row['is_active'] ? 'active':'inactive' ?>">
<?= $row['is_active'] ? 'Active':'Inactive' ?>
</span>
</p>

<br>

<a href="?toggle=<?= $row['id'] ?>" class="btn toggle">
Toggle Active
</a>

<a href="edit_listing.php?id=<?= $row['id'] ?>" class="btn edit">
Edit
</a>

<a href="delete_listing.php?id=<?= $row['id'] ?>" class="btn delete"
onclick="return confirm('Delete this listing?')">
Delete
</a>

</div>

<?php endwhile; ?>

<?php else: ?>

<p>No businesses added yet.</p>

<?php endif; ?>

</body>
</html>
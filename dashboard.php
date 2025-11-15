<?php
// dashboard.php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); exit;
}
$user = $_SESSION['user'];
?>
<!doctype html>
<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Dashboard</title>
</head>

<body class="bg-light">
    <div class="container py-5">
        <h3>Welcome, <?= htmlspecialchars($user['username']) ?></h3>
        <p>Dashboard masih minimal. Nanti kita tambahkan watchlist & transaksi.</p>
        <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</body>

</html>
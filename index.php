<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Dashboard | ArtaCrypto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />

</head>

<body class="bg-light">
    <div class="container mt-5 text-center">
        <h2>Selamat datang, <?= htmlspecialchars($user['username']) ?> 👋</h2>
        <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
    </div>
</body>

</html>
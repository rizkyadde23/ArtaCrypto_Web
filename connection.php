<?php
$host = (PHP_OS_FAMILY === 'Windows') ? '127.0.0.1' : '127.0.0.1';
$user = "root";
$pass = "";
$db = "artacrypto";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
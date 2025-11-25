<?php
session_start();
include "connection.php";
include "./services/PortfolioService.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$uid = $user['id'];

$coin_id = $_POST['coin_id'];
$amount = (float)$_POST['amount'];
$price = (float)$_POST['price'];

$total = (float) str_replace(",", "", $amount * $price);


// cek saldo user & lock row
$conn->begin_transaction();

$q = "SELECT balance_demo FROM users WHERE id = ? FOR UPDATE";
$st = $conn->prepare($q);
$st->bind_param("i", $uid);
$st->execute();
$res = $st->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    $conn->rollback();
    exit("User not found");
}

$balance = (float)$row['balance_demo'];

if ($balance < $total) {
    $conn->rollback();
    exit("Saldo tidak cukup");
}

// insert transaction
$ins = "INSERT INTO transactions (user_id, coin_id, type, amount, price)
        VALUES (?, ?, 'buy', ?, ?)";
$st = $conn->prepare($ins);
$st->bind_param("isdd", $uid, $coin_id, $amount, $price);
$st->execute();

// kurangi saldo
$upd = "UPDATE users SET balance_demo = balance_demo - ? WHERE id = ?";
$su = $conn->prepare($upd);
$su->bind_param("di", $total, $uid);
$su->execute();

// update portfolio
$portfolio = new PortfolioService($conn);
$portfolio->updateOnBuy($uid, $coin_id, $amount, $price);

$conn->commit();

header("Location: dashboard.php");
exit;
?>
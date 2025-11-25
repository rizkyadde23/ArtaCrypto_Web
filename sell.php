<?php
session_start();
include "connection.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$uid = $user['id'];

$coin_id = $_POST['coin_id'];
$amount  = (float)$_POST['amount'];
$price   = (float)$_POST['price'];

$total   = $amount * $price;

// Mulai transaksi SQL
$conn->begin_transaction();

// Ambil total holdings (buy - sell) dan lock row
$q = "
    SELECT 
        COALESCE(
            SUM(CASE WHEN type='buy' THEN amount WHEN type='sell' THEN -amount END),
        0) AS holdings
    FROM transactions
    WHERE user_id = ? AND coin_id = ?
    FOR UPDATE
";

$st = $conn->prepare($q);
$st->bind_param("is", $uid, $coin_id);
$st->execute();
$res = $st->get_result();
$row = $res->fetch_assoc();

$holdings = (float)$row['holdings'];

if ($holdings < $amount) {
    $conn->rollback();
    exit("Holdings coin tidak cukup untuk SELL");
}

// insert transaksi sell
$ins = "INSERT INTO transactions (user_id, coin_id, type, amount, price)
        VALUES (?, ?, 'sell', ?, ?)";
$st = $conn->prepare($ins);
$st->bind_param("isdd", $uid, $coin_id, $amount, $price);
$st->execute();

// tambahkan saldo user
$upd = "UPDATE users SET balance_demo = balance_demo + ? WHERE id = ?";
$su = $conn->prepare($upd);
$su->bind_param("di", $total, $uid);
$su->execute();

$conn->commit();

header("Location: dashboard.php");
exit;
?>
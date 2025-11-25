<?php
include 'connection.php';

// Supaya tidak timeout
set_time_limit(60);

// Ambil API data
$data = @file_get_contents("http://localhost/praktikum%20web/Projek/get_coins.php");
$coins = json_decode($data, true);

if (!$coins) {
    file_put_contents("sync_log.txt", date("Y-m-d H:i:s") . " - ERROR API\n", FILE_APPEND);
    exit;
}

$query = "
INSERT INTO coins (id, name, symbol, current_price, market_cap, price_change_24h, last_updated)
VALUES (?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    symbol = VALUES(symbol),
    current_price = VALUES(current_price),
    market_cap = VALUES(market_cap),
    price_change_24h = VALUES(price_change_24h),
    last_updated = VALUES(last_updated)
";

$stmt = $conn->prepare($query);
$count = 0;

foreach ($coins as $c) {
    $stmt->bind_param(
        "ssssdds",
        $c['id'],
        $c['name'],
        $c['symbol'],
        $c['current_price'],
        $c['market_cap'],
        $c['price_change_24h'],
        $c['last_updated']
    );
    $stmt->execute();
    $count++;
}

// Log setiap update
file_put_contents(
    "sync_log.txt",
    date("Y-m-d H:i:s") . " - Synced $count coins\n",
    FILE_APPEND
);

echo "Cron Sync Done";
<?php
// sync_coins.php - cron-friendly, auto-retry, logging
include 'connection.php';
set_time_limit(120);

$logFile = __DIR__ . "/sync_log.txt"; // log di folder proyek

function logMessage($msg) {
    global $logFile;
    $time = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
}

// URL API CoinGecko (atau get_coins.php lokal)
$apiUrl = "http://localhost/ArtaCrypto_Web/get_coins.php"; // sesuaikan jika di live server

$maxRetries = 3;
$retry = 0;
$coinsData = false;

while ($retry < $maxRetries && !$coinsData) {
    $retry++;
    $json = @file_get_contents($apiUrl);
    if ($json) {
        $coinsData = json_decode($json, true);
        if ($coinsData) break;
    }
    logMessage("Fetch attempt $retry failed.");
    sleep(2);
}

if (!$coinsData) {
    logMessage("ERROR: Failed to fetch coins data after $maxRetries attempts.");
    exit;
}

// Sync ke database
$updateQuery = "
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

$stmt = $conn->prepare($updateQuery);
$count = 0;

foreach ($coinsData as $c) {
    $stmt->bind_param(
        "sssdds",
        $c['id'],
        $c['name'],
        $c['symbol'],
        $c['current_price'],
        $c['market_cap'],
        $c['price_change_24h'],
        $c['last_updated']
    );
    if ($stmt->execute()) $count++;
}

logMessage("Synced $count coins successfully.");
echo "Sync completed: $count coins.\n";

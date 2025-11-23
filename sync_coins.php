<?php
// sync_coins.php
include 'connection.php';
set_time_limit(120); // biar tidak timeout

$logFile = __DIR__ . "/sync_log.txt"; // log file di folder yang sama

function logMessage($msg) {
    global $logFile;
    $time = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
}

// URL API CoinGecko
$apiUrl = "http://localhost/ArtaCrypto_Web/get_coins.php"; // ganti jika live server

// Coba fetch data maksimal 3 kali kalau gagal
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
    sleep(2); // jeda sebelum retry
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
echo "Sync completed: $count coins.";

<?php
// sync_coins.php
date_default_timezone_set('Asia/Jakarta');
$logFile = __DIR__ . "/sync_coins.log";
$apiKey = "https://api.coingecko.com/api/v3/coins/"; // ganti dengan API key mu

function logMsg($msg) {
    global $logFile;
    $time = date("[Y-m-d H:i:s] ");
    file_put_contents($logFile, $time . $msg . "\n", FILE_APPEND);
}

// --- DATABASE CONNECTION ---
$host = '127.0.0.1';
$user = 'root';  
$pass = '';      
$db   = 'artacrypto';

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    logMsg("DB Connection Error: " . $conn->connect_error);
    exit("DB Error: " . $conn->connect_error);
}

// --- FETCH COINS WITH RETRY ---
$url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd";
$maxAttempts = 3;
$attempt = 0;
$response = false;

while ($attempt < $maxAttempts) {
    $attempt++;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "x-cg-pro-api-key: $apiKey"
        ],
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($response && $httpCode === 200){
        break;
    } else {
        logMsg("Fetch attempt $attempt failed. HTTP: $httpCode");
        sleep(2);
    }
}

if(!$response || $httpCode !== 200){
    logMsg("ERROR: Failed to fetch coins data after $maxAttempts attempts.");
    exit("Fetch Error. Check log for details.");
}

// --- PARSE & SYNC ---
$coins = json_decode($response, true);
if(!$coins){
    logMsg("JSON Decode Error");
    exit("JSON Error");
}

foreach($coins as $coin){
    $id = $conn->real_escape_string($coin['id']);
    $name = $conn->real_escape_string($coin['name']);
    $symbol = $conn->real_escape_string($coin['symbol']);
    $price = $coin['current_price'] ?? 0;
    $market_cap = $coin['market_cap'] ?? 0;
    $change24h = $coin['price_change_percentage_24h'] ?? 0;

    $sql = "INSERT INTO coins (id, name, symbol, current_price, market_cap, price_change_24h, last_updated)
            VALUES ('$id','$name','$symbol','$price','$market_cap','$change24h',NOW())
            ON DUPLICATE KEY UPDATE
                name='$name',
                symbol='$symbol',
                current_price='$price',
                market_cap='$market_cap',
                price_change_24h='$change24h',
                last_updated=NOW()";

    if($conn->query($sql)){
        logMsg("Synced coin: $name ($symbol)");
    } else {
        logMsg("DB Error for $name: " . $conn->error);
    }
}

logMsg("Sync completed successfully.");
$conn->close();

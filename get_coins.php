<?php
$apiKey = "YOUR_API_KEY";

$url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $apiKey ? ["x-cg-demo-api-key: $apiKey"] : []
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'Failed to fetch from CoinGecko', 'code' => $httpCode]);
    exit;
}

header("Content-Type: application/json");
echo $response;
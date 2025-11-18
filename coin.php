<?php
// coin.php
session_start();
$coinId = isset($_GET['id']) ? preg_replace('/[^a-z0-9\\-]/i', '', $_GET['id']) : 'bitcoin';
?>
<!DOCTYPE html>
<html lang="en">

<!-- first commit -->
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Coin Detail - <?= htmlspecialchars($coinId) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: #0b0b0b;
        color: #fff
    }

    .card-detail {
        background: #111;
        border: none
    }

    .center {
        margin: 0 auto;
        width: fit-content;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container"><a class="navbar-brand" href="index.php">ArtaCrypto</a></div>
    </nav>

    <div class="container py-5">
        <div id="coin-detail" class="card card-detail p-4 mx-auto" style="max-width:900px">
            Loading...
        </div>
    </div>

    <div class="center">
        <a href="index.php" class="btn btn-primary btn-lg">Kembali</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    fetch('get_coin_detail.php?id=<?= json_encode($coinId) ?>'.replace(/\"/g, '')) // ensure plain string
        .then(r => r.json())
        .then(coin => {
            if (coin.error) {
                document.getElementById('coin-detail').innerText = 'Gagal ambil data coin.';
                return;
            }
            const md = coin.market_data || {};
            const price = md.current_price ? md.current_price.usd : 'N/A';
            const change24 = md.price_change_percentage_24h ? md.price_change_percentage_24h.toFixed(2) : '0.00';
            document.getElementById('coin-detail').innerHTML = `
      <div class="d-flex align-items-center gap-3 mb-3">
        <img src="${coin.image.large}" width="64">
        <div>
          <h3 class="mb-0">${coin.name} <small class="text-muted">(${coin.symbol.toUpperCase()})</small></h3>
          <small>Rank: ${coin.market_cap_rank}</small>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <h4>$${price.toLocaleString ? price.toLocaleString() : price}</h4>
          <p class="${change24 >= 0 ? 'text-success' : 'text-danger'}">24h: ${change24}%</p>
          <p>Market Cap: $${md.market_cap ? md.market_cap.usd.toLocaleString() : 'N/A'}</p>
        </div>
        <div class="col-md-6">
          <h6>Description</h6>
          <div style="max-height:220px; overflow:auto; color:#ddd">
            ${coin.description.en ? coin.description.en : 'No description available.'}
          </div>
        </div>
      </div>
    `;
        })
        .catch(err => {
            console.error(err);
            document.getElementById('coin-detail').innerText = 'Terjadi kesalahan saat fetch.';
        });
    </script>
</body>

</html>
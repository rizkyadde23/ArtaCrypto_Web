<?php
session_start();
include 'connection.php';
include 'coin_controller.php';
include 'transaction_controller.php';
include 'auth_refresh.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

refreshUserSession($conn);
$user = $_SESSION['user'];
$uid = $user['id'];
$balance_demo = (float)$user['balance_demo'];

$coinController = new CoinController($conn);
$transactionController = new TransactionController($conn);

// -------- GET coin_id ----------
$coin_id = $_GET['coin_id'] ?? null;

// ambil semua coins untuk dropdown
$coins = $coinController->getAllCoins();

// jika coin dipilih, ambil datanya
$selectedCoin = null;
if ($coin_id) {
    $selectedCoin = $coinController->getCoinById($coin_id);
}

// -------- Hitung HOLDINGS User ----------
$holdings = 0;

if ($selectedCoin) {
    $qh = "
        SELECT COALESCE(
            SUM(CASE WHEN type='buy' THEN amount WHEN type='sell' THEN -amount END), 0
        ) AS hold
        FROM transactions
        WHERE user_id = ? AND coin_id = ?
    ";

    $sh = $conn->prepare($qh);
    $sh->bind_param("is", $uid, $coin_id);
    $sh->execute();
    $resH = $sh->get_result();
    $rowH = $resH->fetch_assoc();
    $holdings = (float)$rowH['hold'];
    $sh->close();
}

// -------- Handle TRANSACT --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

    $type = $_POST['type'];
    $coin_id = $_POST['coin_id'];
    $amount = (float)$_POST['amount'];

    // harga real-time dari DB
    $coin = $coinController->getCoinById($coin_id);
    $price = (float)$coin['current_price'];

    // transaksi
    if ($type === "buy") {
        $flash = $transactionController->buy($uid, $coin_id, $amount, $price);
    } else {
        $flash = $transactionController->sell($uid, $coin_id, $amount, $price);
    }

    // refresh session
    refreshUserSession($conn);

    header("Location: dashboard.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Transaksi | ArtaCrypto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    :root {
        --bg: #0d0d0d;
        --card: #111216;
        --muted: #99a0aa;
        --accent: #00cf8a;
    }

    body {
        background: var(--bg);
        color: #e8eef6;
        font-family: 'Inter', sans-serif;
    }

    .navbar {
        background: rgba(14, 14, 14, 0.9);
        border-bottom: 1px solid #151517;
        backdrop-filter: blur(6px);
    }

    .section-card {
        background: linear-gradient(180deg, #0f1113, #0c0d0f);
        border: 1px solid rgba(255, 255, 255, 0.03);
        padding: 18px;
        border-radius: 12px;
    }

    .btn-accent {
        background: var(--accent);
        border: none;
        color: #001b14;
        font-weight: 600;
    }

    .small-muted {
        color: var(--muted);
        font-size: 0.9rem;
    }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">ArtaCrypto</a>

            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="index.php" class="nav-link">Market</a></li>
                    <li class="nav-item"><a href="logout.php" class="nav-link text-danger">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">

        <?php if(!empty($flash)): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <!-- SELECT COIN -->
        <div class="section-card mb-3">
            <h4 class="mb-1">Transaksi Crypto</h4>
            <p class="small-muted mb-2">Pilih coin yang ingin kamu beli atau jual.</p>

            <form method="GET">
                <label class="small-muted">Pilih Coin</label>
                <select name="coin_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Pilih Coin --</option>

                    <?php foreach($coins as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($coin_id === $c['id']) ? 'selected' : '' ?>>
                        <?= strtoupper($c['symbol']) ?> — <?= $c['name'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($selectedCoin): ?>
        <div class="section-card mb-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1"><?= $selectedCoin['name'] ?> (<?= strtoupper($selectedCoin['symbol']) ?>)</h5>
                    <div class="small-muted">Harga Saat Ini</div>
                </div>
                <div class="text-end">
                    <h4 class="fw-bold">$<?= number_format($selectedCoin['current_price'], 2, '.', ',') ?></h4>
                </div>
            </div>

            <!-- SALDO -->
            <div class="mb-3 p-3 rounded" style="background:#0f1113; border:1px solid #151517">
                <div class="small-muted">Saldo Demo Kamu</div>
                <h4 class="fw-bold">$<?= number_format($balance_demo,2,'.',',') ?></h4>
            </div>

            <!-- HOLDINGS -->
            <div class="mb-3 p-3 rounded" style="background:#0f1113; border:1px solid #151517">
                <div class="small-muted">Holdings Kamu</div>
                <h4 class="fw-bold">
                    <?= rtrim(rtrim(number_format($holdings, 8, '.', ','),'0'),'.') ?>
                    <span class="small-muted">(<?= strtoupper($selectedCoin['symbol']) ?>)</span>
                </h4>
                <div class="small-muted">
                    $<?= number_format($holdings * $selectedCoin['current_price'], 2, '.', ',') ?>
                </div>
            </div>

            <!-- FORM TRANSAKSI -->
            <form method="POST">
                <input type="hidden" name="coin_id" value="<?= $selectedCoin['id'] ?>">
                <input type="hidden" id="price" value="<?= $selectedCoin['current_price'] ?>">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="small-muted">Tipe Transaksi</label>
                        <select name="type" class="form-select">
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="small-muted">Jumlah (qty)</label>
                        <input type="number" step="0.00000001" class="form-control" id="amount" name="amount">
                    </div>

                    <div class="col-md-4">
                        <label class="small-muted">Total (USD)</label>
                        <input type="number" step="0.01" class="form-control" id="total">
                    </div>

                    <div class="col-md-12">
                        <button class="btn btn-accent w-100 mt-2">Submit</button>
                    </div>

                </div>
            </form>
        </div>
        <?php endif; ?>

    </div>

    <script>
    // auto update total & amount
    document.addEventListener("DOMContentLoaded", () => {
        const price = parseFloat(document.getElementById("price")?.value || 0);
        const amount = document.getElementById("amount");
        const total = document.getElementById("total");

        if (!amount || !total) return;

        amount.addEventListener("input", () => {
            const a = parseFloat(amount.value || 0);
            total.value = (a * price).toFixed(2);
        });

        total.addEventListener("input", () => {
            const t = parseFloat(total.value || 0);
            amount.value = (t / price).toFixed(8);
        });
    });
    </script>

</body>

</html>
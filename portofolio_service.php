<?php
class PortfolioService
{
    private $db;
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // Ambil data portfolio 1 coin
    public function getUserCoin($user_id, $coin_id)
    {
        $sql = "SELECT * FROM portofolio WHERE user_id = ? AND coin_id = ?";
        $st = $this->db->prepare($sql);
        $st->bind_param("is", $user_id, $coin_id);
        $st->execute();
        $res = $st->get_result();
        return $res->fetch_assoc();
    }

    // BUY — update holdings + average price
    public function updateOnBuy($user_id, $coin_id, $amount, $price)
    {
        $totalBuyValue = $amount * $price;

        $current = $this->getUserCoin($user_id, $coin_id);

        if (!$current) {
            // insert baru
            $avgPrice = $price;

            $sql = "INSERT INTO portofolio (user_id, coin_id, amount, average_price, total_value)
                    VALUES (?, ?, ?, ?, ?)";
            $st = $this->db->prepare($sql);
            $st->bind_param("issdd", $user_id, $coin_id, $amount, $avgPrice, $totalBuyValue);
            return $st->execute();
        }

        // update existing holdings
        $oldAmount = (float)$current['amount'];
        $oldAvg = (float)$current['average_price'];

        $newAmount = $oldAmount + $amount;
        $newAvg = (($oldAmount * $oldAvg) + $totalBuyValue) / $newAmount;

        $newTotalValue = $newAmount * $price;

        $sql = "UPDATE portofolio
                SET amount = ?, average_price = ?, total_value = ?
                WHERE user_id = ? AND coin_id = ?";
        $st = $this->db->prepare($sql);
        $st->bind_param("ddd is", $newAmount, $newAvg, $newTotalValue, $user_id, $coin_id);
        return $st->execute();
    }

    // SELL — kurangi amount + hapus kalau holdings 0
    public function updateOnSell($user_id, $coin_id, $amount, $price)
    {
        $current = $this->getUserCoin($user_id, $coin_id);
        if (!$current) return false;

        $oldAmount = (float)$current['amount'];

        if ($amount > $oldAmount) return false; // tidak cukup coin

        $newAmount = $oldAmount - $amount;

        if ($newAmount <= 0) {
            // hapus dari portfolio
            $sql = "DELETE FROM portofolio WHERE user_id = ? AND coin_id = ?";
            $st = $this->db->prepare($sql);
            $st->bind_param("is", $user_id, $coin_id);
            return $st->execute();
        }

        // update sisa holdings
        $newTotalValue = $newAmount * $price;

        $sql = "UPDATE portofolio
                SET amount = ?, total_value = ?
                WHERE user_id = ? AND coin_id = ?";
        $st = $this->db->prepare($sql);
        $st->bind_param("ddis", $newAmount, $newTotalValue, $user_id, $coin_id);
        return $st->execute();
    }
}

?>
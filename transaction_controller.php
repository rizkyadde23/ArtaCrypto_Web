<?php
include "connection.php";

class TransactionController 
{
    private $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // hitung holdings user untuk 1 coin
    public function getHoldings($user_id, $coin_id)
    {
        $sql = "
            SELECT COALESCE(
                SUM(
                    CASE 
                        WHEN type='buy' THEN amount
                        WHEN type='sell' THEN -amount
                    END
                ), 0
            ) AS holdings
            FROM transactions
            WHERE user_id = ? AND coin_id = ?
        ";

        $st = $this->db->prepare($sql);
        $st->bind_param("is", $user_id, $coin_id);
        $st->execute();

        return $st->get_result()->fetch_assoc()['holdings'];
    }

    // jalankan transaksi BUY
    public function buy($user_id, $coin_id, $amount, $price)
    {
        $total = $amount * $price;

        $this->db->begin_transaction();

        // lock saldo user
        $q = "SELECT balance_demo FROM users WHERE id = ? FOR UPDATE";
        $st = $this->db->prepare($q);
        $st->bind_param("i", $user_id);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $balance = (float)$row['balance_demo'];

        if ($balance < $total) {
            $this->db->rollback();
            return "Saldo tidak cukup!";
        }

        // insert transaksi
        $ins = "INSERT INTO transactions (user_id, coin_id, type, amount, price)
                VALUES (?, ?, 'buy', ?, ?)";
        $st = $this->db->prepare($ins);
        $st->bind_param("isdd", $user_id, $coin_id, $amount, $price);
        $st->execute();

        // kurangi saldo
        $upd = "UPDATE users SET balance_demo = balance_demo - ? WHERE id = ?";
        $su = $this->db->prepare($upd);
        $su->bind_param("di", $total, $user_id);
        $su->execute();

        $this->db->commit();
        return true;
    }

    // jalankan transaksi SELL
    public function sell($user_id, $coin_id, $amount, $price)
    {
        $holdings = $this->getHoldings($user_id, $coin_id);

        if ($holdings < $amount) {
            return "Holdings tidak cukup!";
        }

        $total = $amount * $price;
        $this->db->begin_transaction();

        // insert transaksi
        $ins = "INSERT INTO transactions (user_id, coin_id, type, amount, price)
                VALUES (?, ?, 'sell', ?, ?)";
        $s = $this->db->prepare($ins);
        $s->bind_param("isdd", $user_id, $coin_id, $amount, $price);
        $s->execute();

        // tambahkan saldo
        $up = "UPDATE users SET balance_demo = balance_demo + ? WHERE id = ?";
        $u = $this->db->prepare($up);
        $u->bind_param("di", $total, $user_id);
        $u->execute();

        $this->db->commit();
    }
}
?>
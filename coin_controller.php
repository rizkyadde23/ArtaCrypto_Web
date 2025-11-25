<?php

class CoinController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Ambil semua coin dari database (bisa dipakai untuk dropdown, market list, dll)
     */
    public function getAllCoins()
    {
        $sql = "SELECT id, name, symbol, current_price, market_cap, price_change_24h 
                FROM coins 
                ORDER BY market_cap DESC";

        $result = $this->conn->query($sql);

        $coins = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $coins[] = $row;
            }
        }

        return $coins;
    }

    /**
     * Ambil coin berdasarkan ID
     */
    public function getCoinById($coin_id)
    {
        $sql = "SELECT id, name, symbol, current_price, market_cap, price_change_24h
                FROM coins
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $coin_id);
        $stmt->execute();

        $res = $stmt->get_result();
        $coin = $res->fetch_assoc();

        $stmt->close();

        return $coin ?: null;
    }

    /**
     * Search coin berdasarkan nama / symbol (optional untuk future feature)
     */
    public function searchCoins($keyword)
    {
        $keyword = "%$keyword%";

        $sql = "SELECT id, name, symbol, current_price 
                FROM coins
                WHERE name LIKE ? OR symbol LIKE ?
                ORDER BY market_cap DESC
                LIMIT 20";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $keyword, $keyword);
        $stmt->execute();

        $res = $stmt->get_result();
        $coins = [];

        while ($row = $res->fetch_assoc()) {
            $coins[] = $row;
        }

        return $coins;
    }
}

?>
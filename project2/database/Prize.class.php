<?php


class Prize {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Метод для получения всех призов по ID лауреата
    public function getPrizesByLaureateId($laureateId) {
        $stmt = $this->pdo->prepare("
            SELECT p.* FROM prizes p
            JOIN laureates_prizes lp ON p.id = lp.prize_id
            WHERE lp.laureate_id = :laureate_id
        ");
        $stmt->execute(['laureate_id' => $laureateId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
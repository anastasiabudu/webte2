<?php

class Country {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Метод для получения всех стран
    public function index() {
        $stmt = $this->pdo->query("SELECT * FROM countries");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Метод для получения страны по ID
    public function show($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM countries WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Метод для получения стран по ID лауреата
    public function getCountriesByLaureateId($laureateId) {
        $stmt = $this->pdo->prepare("
            SELECT c.* FROM countries c
            JOIN laureates_counteries lc ON c.id = lc.country_id
            WHERE lc.laureate_id = :laureate_id
        ");
        $stmt->execute(['laureate_id' => $laureateId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
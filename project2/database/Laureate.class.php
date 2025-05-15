<?php

use OpenApi\Annotations as OA;
/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Documentation",
 *     description="Description removed for better illustration of structure.",
 *     @OA\Contact(
 *         email="admin@example.test",
 *         name="company",
 *         url="https://example.test"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 */

class Laureate {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;  // Используем $pdo вместо $db
    }

    // Метод для получения всех лауреатов с их странами и призами
    public function getLaureatesWithDetails() {
        $stmt = $this->pdo->query("
            SELECT 
                l.id AS laureate_id,
                l.fullname,
                l.sex,
                l.birth_year,
                l.death_year,
                GROUP_CONCAT(DISTINCT c.country_name SEPARATOR ', ') AS country,
                p.year,
                p.category,
                p.contrib_sk AS contribution_sk,
                p.contrib_en AS contribution_en,
                pd.language_sk,
                pd.language_en,
                pd.genre_sk,
                pd.genre_en
            FROM 
                laureates l
            LEFT JOIN 
                laureates_counteries lc ON l.id = lc.laureate_id
            LEFT JOIN 
                countries c ON lc.country_id = c.id
            LEFT JOIN 
                laureates_prizes lp ON l.id = lp.laureate_id
            LEFT JOIN 
                prizes p ON lp.prize_id = p.id
            LEFT JOIN 
                prize_details pd ON p.details_id = pd.id
            GROUP BY 
                l.id, p.id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLaureateWithDetails($id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                l.id AS laureate_id,
                l.fullname,
                l.sex,
                l.birth_year,
                l.death_year,
                GROUP_CONCAT(DISTINCT c.country_name SEPARATOR ', ') AS country,
                p.year,
                p.category,
                p.contrib_sk AS contribution_sk,
                p.contrib_en AS contribution_en,
                pd.language_sk,
                pd.language_en,
                pd.genre_sk,
                pd.genre_en
            FROM 
                laureates l
            LEFT JOIN 
                laureates_counteries lc ON l.id = lc.laureate_id
            LEFT JOIN 
                countries c ON lc.country_id = c.id
            LEFT JOIN 
                laureates_prizes lp ON l.id = lp.laureate_id
            LEFT JOIN 
                prizes p ON lp.prize_id = p.id
            LEFT JOIN 
                prize_details pd ON p.details_id = pd.id
            WHERE 
                l.id = :id
            GROUP BY 
                l.id, p.id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
 * @OA\Schema(
 *     schema="Laureate",
 *     type="object",
 *     @OA\Property(
 *         property="laureate_id",
 *         type="integer",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="fullname",
 *         type="string",
 *         example="Albert Einstein"
 *     ),
 *     @OA\Property(
 *         property="sex",
 *         type="string",
 *         example="M"
 *     ),
 *     @OA\Property(
 *         property="birth_year",
 *         type="string",
 *         example="1879"
 *     ),
 *     @OA\Property(
 *         property="death_year",
 *         type="string",
 *         example="1955"
 *     ),
 *     @OA\Property(
 *         property="country",
 *         type="string",
 *         example="Germany"
 *     ),
 *     @OA\Property(
 *         property="year",
 *         type="string",
 *         example="1921"
 *     ),
 *     @OA\Property(
 *         property="category",
 *         type="string",
 *         example="Physics"
 *     ),
 *     @OA\Property(
 *         property="contribution_sk",
 *         type="string",
 *         example="Teória relativity"
 *     ),
 *     @OA\Property(
 *         property="contribution_en",
 *         type="string",
 *         example="Theory of relativity"
 *     ),
 *     @OA\Property(
 *         property="language_sk",
 *         type="string",
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="language_en",
 *         type="string",
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="genre_sk",
 *         type="string",
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="genre_en",
 *         type="string",
 *         example=null
 *     )
 * )
 */

    // Get all records
    public function index() {
        $stmt = $this->pdo->prepare("SELECT * FROM laureates");
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
 * @OA\Get(
 *     path="/laureates/{id}",
 *     summary="Get a laureate by ID",
 *     tags={"laureates"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the laureate",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Laureate")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Laureate not found"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */

    // Get one record
    public function show($id) {
        return $this->getLaureateWithDetails($id);
    }

    // Check if laureate exists
    public function exists($fullname) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM laureates WHERE fullname = :fullname");
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
 /**
 * @OA\Post(
 *     path="/laureates",
 *     summary="Add a new laureate",
 *     tags={"laureates"},
 *     @OA\RequestBody(
 *         description="Data for adding a new laureate",
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="fullname", type="string", example="Albert Einstein"),
 *             @OA\Property(property="sex", type="string", example="M"),
 *             @OA\Property(property="birth_year", type="string", example="1879"),
 *             @OA\Property(property="death_year", type="string", example="1955", nullable=true),
 *             @OA\Property(property="organisation", type="string", example="Princeton University", nullable=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Laureate successfully added",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */

   
    // Create a new record
    public function store($fullname, $sex, $birth_year, $death_year = null, $organisation = null) {
        if (empty($fullname) || empty($sex) || empty($birth_year)) {
            return [
                'error' => true,
                'message' => 'Missing required fields: fullname, sex, or birth_year'
            ];
        }

        $stmt = $this->pdo->prepare("INSERT INTO laureates (fullname, organisation, sex, birth_year, death_year) VALUES (:fullname, :organisation, :sex, :birth_year, :death_year)");
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_INT);
        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }

        return $this->pdo->lastInsertId();
    }

    public function findCountryIdByName($countryName) {
        $stmt = $this->pdo->prepare("SELECT id FROM countries WHERE name = :name");
        $stmt->bindParam(':name', $countryName, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }

    public function findCurrentPrizeId($laureateId) {
        $stmt = $this->pdo->prepare("SELECT prize_id FROM laureates_prizes WHERE laureate_id = :laureate_id");
        $stmt->bindParam(':laureate_id', $laureateId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


/**
 * @OA\Put(
 *     path="/laureates/{id}",
 *     summary="Update laureate data",
 *     tags={"laureates"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the laureate",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         description="Data for updating the laureate",
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="fullname", type="string", example="Albert Einstein"),
 *             @OA\Property(property="sex", type="string", example="M"),
 *             @OA\Property(property="birth_year", type="string", example="1879"),
 *             @OA\Property(property="death_year", type="string", example="1955", nullable=true),
 *             @OA\Property(property="organisation", type="string", example="Princeton University", nullable=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Data successfully updated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Laureate not found"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */

    // Update a record
    public function update($laureateId, $fullname, $sex, $birth_year, $death_year = null, $organisation = null) {
        $stmt = $this->pdo->prepare("
            UPDATE laureates 
            SET 
                fullname = :fullname, 
                sex = :sex, 
                birth_year = :birth_year, 
                death_year = :death_year, 
                organisation = :organisation 
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $laureateId, PDO::PARAM_INT);
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_STR);
        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_STR);
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
    
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    
        return true;
    }

    public function updateLaureateCountries($laureateId, $countryName) {
        // Находим ID страны по названию
        $countryStmt = $this->pdo->prepare("SELECT id FROM countries WHERE country_name = :country_name");
        $countryStmt->bindParam(':country_name', $countryName, PDO::PARAM_STR);
        $countryStmt->execute();
        $countryId = $countryStmt->fetchColumn();
    
        if ($countryId) {
            // Удаляем старую связь
            $deleteStmt = $this->pdo->prepare("DELETE FROM laureates_counteries WHERE laureate_id = :laureate_id");
            $deleteStmt->bindParam(':laureate_id', $laureateId, PDO::PARAM_INT);
            $deleteStmt->execute();
    
            // Создаем новую связь
            $insertStmt = $this->pdo->prepare("
                INSERT INTO laureates_counteries (laureate_id, country_id) 
                VALUES (:laureate_id, :country_id)
            ");
            $insertStmt->bindParam(':laureate_id', $laureateId, PDO::PARAM_INT);
            $insertStmt->bindParam(':country_id', $countryId, PDO::PARAM_INT);
            $insertStmt->execute();
        }
    
        return true;
    }

    public function updateLaureatePrizes($laureateId, $prizeId) {
        // Удаляем старую связь
        $deleteStmt = $this->pdo->prepare("DELETE FROM laureates_prizes WHERE laureate_id = :laureate_id");
        $deleteStmt->bindParam(':laureate_id', $laureateId, PDO::PARAM_INT);
        $deleteStmt->execute();
    
        // Создаем новую связь
        $insertStmt = $this->pdo->prepare("
            INSERT INTO laureates_prizes (laureate_id, prize_id) 
            VALUES (:laureate_id, :prize_id)
        ");
        $insertStmt->bindParam(':laureate_id', $laureateId, PDO::PARAM_INT);
        $insertStmt->bindParam(':prize_id', $prizeId, PDO::PARAM_INT);
        $insertStmt->execute();
    
        return true;
    }

  


    public function updatePrize($prizeId, $year, $category, $contrib_sk, $contrib_en) {
        $stmt = $this->pdo->prepare("
            UPDATE prizes 
            SET 
                year = :year, 
                category = :category, 
                contrib_sk = :contrib_sk, 
                contrib_en = :contrib_en 
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $prizeId, PDO::PARAM_INT);
        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':contrib_sk', $contrib_sk, PDO::PARAM_STR);
        $stmt->bindParam(':contrib_en', $contrib_en, PDO::PARAM_STR);
    
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    
        return true;
    }

    public function updatePrizeDetails($detailsId, $language_sk, $language_en, $genre_sk, $genre_en) {
        $stmt = $this->pdo->prepare("
            UPDATE prize_details 
            SET 
                language_sk = :language_sk, 
                language_en = :language_en, 
                genre_sk = :genre_sk, 
                genre_en = :genre_en 
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $detailsId, PDO::PARAM_INT);
        $stmt->bindParam(':language_sk', $language_sk, PDO::PARAM_STR);
        $stmt->bindParam(':language_en', $language_en, PDO::PARAM_STR);
        $stmt->bindParam(':genre_sk', $genre_sk, PDO::PARAM_STR);
        $stmt->bindParam(':genre_en', $genre_en, PDO::PARAM_STR);
    
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    
        return true;
    }

 

    // Filter laureates with prizes and countries
    public function filter($year = null, $category = null, $country = null) {
        $sql = "
            SELECT 
                l.id AS laureate_id,
                l.fullname,
                l.organisation,
                l.sex,
                l.birth_year,
                l.death_year,
                p.year AS prize_year,
                p.category AS prize_category,
                c.country_name AS country
            FROM 
                laureates l
            LEFT JOIN 
                laureates_prizes lp ON l.id = lp.laureate_id
            LEFT JOIN 
                prizes p ON lp.prize_id = p.id
            LEFT JOIN 
                laureates_countries lc ON l.id = lc.laureate_id
            LEFT JOIN 
                countries c ON lc.country_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if ($year) {
            $sql .= " AND p.year = :year";
            $params[':year'] = $year;
        }
        if ($category) {
            $sql .= " AND p.category = :category";
            $params[':category'] = $category;
        }
        if ($country) {
            $sql .= " AND c.country_name = :country";
            $params[':country'] = $country;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Link laureate to a country
    public function linkToCountry($laureateId, $countryId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO laureates_counteries (laureate_id, country_id) 
            VALUES (:laureate_id, :country_id)
        ");
        $stmt->bindParam(':laureate_id', $laureateId, PDO::PARAM_INT);
        $stmt->bindParam(':country_id', $countryId, PDO::PARAM_INT);
    
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    
        return true;
    }

    // Link laureate to a prize
    public function linkToPrize($laureateId, $prizeId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO laureates_prizes (laureate_id, prize_id) 
            VALUES (:laureate_id, :prize_id)
        ");
        $stmt->bindParam(':laureate_id', $laureateId, PDO::PARAM_INT);
        $stmt->bindParam(':prize_id', $prizeId, PDO::PARAM_INT);
    
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    
        return true;
    }

    public function storeMultiple($laureates) {
        if (!is_array($laureates)) {
            throw new InvalidArgumentException("Ожидается массив данных лауреатов.");
        }
    
        $this->pdo->beginTransaction();
    
        try {
            foreach ($laureates as $laureate) {
                // Вставка лауреата
                $laureateData = [
                    'fullname' => $laureate['fullname'],
                    'sex' => $laureate['sex'],
                    'birth_year' => $laureate['birth_year'],
                    'death_year' => $laureate['death_year'],
                    'organisation' => $laureate['organisation']
                ];
    
                $laureateId = $this->store($laureateData);
    
                // Вставка стран
                if (isset($laureate['countries'])) {
                    foreach ($laureate['countries'] as $countryName) {
                        $this->addCountryToLaureate($laureateId, $countryName);
                    }
                }
    
                // Вставка наград
                if (isset($laureate['prizes'])) {
                    foreach ($laureate['prizes'] as $prizeData) {
                        $this->addPrizeToLaureate($laureateId, $prizeData);
                    }
                }
            }
    
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new RuntimeException("Ошибка при добавлении лауреатов: " . $e->getMessage());
        }
    }
    
    // Метод для добавления страны к лауреату
    private function addCountryToLaureate($laureateId, $countryName) {
        // Проверяем, существует ли страна в базе данных
        $stmt = $this->pdo->prepare("SELECT id FROM countries WHERE country_name = :country_name");
        $stmt->execute([':country_name' => $countryName]);
        $countryId = $stmt->fetchColumn();
    
        // Если страны нет, добавляем её
        if (!$countryId) {
            $stmt = $this->pdo->prepare("INSERT INTO countries (country_name) VALUES (:country_name)");
            $stmt->execute([':country_name' => $countryName]);
            $countryId = $this->pdo->lastInsertId();
        }
    
        // Связываем лауреата со страной
        $stmt = $this->pdo->prepare("INSERT INTO laureates_counteries (laureate_id, country_id) VALUES (:laureate_id, :country_id)");
        $stmt->execute([':laureate_id' => $laureateId, ':country_id' => $countryId]);
    }
    
    // Метод для добавления награды к лауреату
    private function addPrizeToLaureate($laureateId, $prizeData) {
        // Вставляем данные о награде
        $stmt = $this->pdo->prepare("
            INSERT INTO prizes (year, category, contrib_sk, contrib_en) 
            VALUES (:year, :category, :contrib_sk, :contrib_en)
        ");
        $stmt->execute([
            ':year' => $prizeData['year'],
            ':category' => $prizeData['category'],
            ':contrib_sk' => $prizeData['contribution_sk'],
            ':contrib_en' => $prizeData['contribution_en']
        ]);
        $prizeId = $this->pdo->lastInsertId();
    
        // Вставляем детали награды
        $stmt = $this->pdo->prepare("
            INSERT INTO prize_details (prize_id, language_sk, language_en, genre_sk, genre_en) 
            VALUES (:prize_id, :language_sk, :language_en, :genre_sk, :genre_en)
        ");
        $stmt->execute([
            ':prize_id' => $prizeId,
            ':language_sk' => $prizeData['language_sk'],
            ':language_en' => $prizeData['language_en'],
            ':genre_sk' => $prizeData['genre_sk'],
            ':genre_en' => $prizeData['genre_en']
        ]);
    
        // Связываем лауреата с наградой
        $stmt = $this->pdo->prepare("INSERT INTO laureates_prizes (laureate_id, prize_id) VALUES (:laureate_id, :prize_id)");
        $stmt->execute([':laureate_id' => $laureateId, ':prize_id' => $prizeId]);
    }

  /**
 * Delete a laureate by ID.
 *
 * @OA\Delete(
 *     path="/laureates/{id}",
 *     summary="Delete a laureate by ID",
 *     tags={"laureates"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the laureate",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Laureate successfully deleted",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Laureate successfully deleted")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Laureate not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Laureate not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Server error")
 *         )
 *     )
 * )
 */


public function destroy($id) {
    $stmt = $this->pdo->prepare("DELETE FROM laureates WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            return ["message" => "Лауреат успешно удалён"];
        } else {
            http_response_code(404);
            return ["message" => "Лауреат не найден"];
        }
    } catch (PDOException $e) {
        http_response_code(500);
        return ["message" => "Ошибка сервера: " . $e->getMessage()];
    }
}



}
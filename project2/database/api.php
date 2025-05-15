<?php

require_once('../../config.php');  // Подключаем конфигурацию с $pdo
require_once('Laureate.class.php');
require_once('Prize.class.php');  // Подключаем класс Prize
require_once('Country.class.php');  // Подключаем новый класс Country

$laureate = new Laureate($pdo);
$prize = new Prize($pdo);  // Создаем объект для работы с призами
$country = new Country($pdo);  // Создаем объект для работы с странами

header("Content-Type: application/json");

// Получаем метод и маршрут
$method = $_SERVER['REQUEST_METHOD'];
$route = explode('/', $_GET['route']);



switch ($method) {
    case 'GET':
        // Обработка запроса для получения всех лауреатов с их странами и призами
        if ($route[0] == 'laureates' && count($route) == 1) {
            $data = $laureate->getLaureatesWithDetails();
            if ($data) {
                http_response_code(200);
                echo json_encode($data);
                break;
            }
            http_response_code(404);
            echo json_encode(['message' => 'No laureates found']);
            break;
        }
        // Обработка запроса для получения одного лауреата по ID
        elseif ($route[0] == 'laureates' && count($route) == 2 && is_numeric($route[1])) {
            $id = $route[1];
            $data = $laureate->show($id);
            if ($data) {
                http_response_code(200);
                echo json_encode($data);
                break;
            }
        }
        // Обработка запроса для получения всех призов по ID лауреата
        elseif ($route[0] == 'laureates' && count($route) == 3 && $route[2] == 'prizes' && is_numeric($route[1])) {
            $laureateId = $route[1];
            $prizes = $prize->getPrizesByLaureateId($laureateId);
            if ($prizes) {
                http_response_code(200);
                echo json_encode($prizes);
                break;
            }
            http_response_code(404);
            echo json_encode(['message' => 'No prizes found for this laureate']);
            break;
        }
        // Обработка запроса для получения всех стран
        elseif ($route[0] == 'countries' && count($route) == 1) {
            http_response_code(200);
            echo json_encode($country->index());  // Получаем все страны
            break;
        }
        // Обработка запроса для получения одной страны по ID
        elseif ($route[0] == 'countries' && count($route) == 2 && is_numeric($route[1])) {
            $id = $route[1];
            $data = $country->show($id);
            if ($data) {
                http_response_code(200);
                echo json_encode($data);
                break;
            }
        }
        // Обработка запроса для получения стран по ID лауреата
        elseif ($route[0] == 'laureates' && count($route) == 3 && $route[2] == 'countries' && is_numeric($route[1])) {
            $laureateId = $route[1];
            $countries = $country->getCountriesByLaureateId($laureateId);
            if ($countries) {
                http_response_code(200);
                echo json_encode($countries);
                break;
            }
            http_response_code(404);
            echo json_encode(['message' => 'No countries found for this laureate']);
            break;
        }
        // Если маршрут не найден
        http_response_code(404);
        echo json_encode(['message' => 'Not found']);
        break;

        
        case 'POST':
            if ($route[0] == 'laureates' && count($route) == 1) {
                // Обработка добавления одного лауреата
                $data = json_decode(file_get_contents('php://input'), true);
    
                // Проверяем обязательные поля
                if (!isset($data['fullname']) || !isset($data['sex']) || !isset($data['birth_year'])) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Missing required fields: fullname, sex, or birth_year']);
                    break;
                }
    
                // Создаем лауреата
                $newID = $laureate->store(
                    $data['fullname'],
                    $data['sex'],
                    $data['birth_year'],
                    $data['death_year'] ?? null,
                    $data['organisation'] ?? null
                );
    
                // Проверяем, что лауреат был успешно создан
                if (!is_numeric($newID)) {
                    http_response_code(400);
                    echo json_encode(['message' => "Bad request", 'data' => $newID]);
                    break;
                }
    
                // Создаем приз (если указаны данные)
                if (isset($data['year']) && isset($data['category'])) {
                    $prizeData = [
                        'year' => $data['year'],
                        'category' => $data['category'],
                        'contrib_sk' => $data['contribution_sk'] ?? '',
                        'contrib_en' => $data['contribution_en'] ?? '',
                        'details_id' => null  // Если есть details, нужно добавить их отдельно
                    ];
    
                    $stmt = $pdo->prepare("
                        INSERT INTO prizes (year, category, contrib_sk, contrib_en, details_id) 
                        VALUES (:year, :category, :contrib_sk, :contrib_en, :details_id)
                    ");
                    $stmt->execute($prizeData);
                    $prizeId = $pdo->lastInsertId();
    
                    // Связываем лауреата с призом
                    $stmt = $pdo->prepare("
                        INSERT INTO laureates_prizes (laureate_id, prize_id) 
                        VALUES (:laureate_id, :prize_id)
                    ");
                    $stmt->execute(['laureate_id' => $newID, 'prize_id' => $prizeId]);
                }
    
                // Связываем лауреата с страной (если указана)
                if (isset($data['country'])) {
                    // Ищем страну по имени
                    $stmt = $pdo->prepare("SELECT id FROM countries WHERE country_name = :country");
                    $stmt->execute(['country' => $data['country']]);
                    $country = $stmt->fetch(PDO::FETCH_ASSOC);
    
                    if ($country) {
                        // Если страна найдена, связываем лауреата с ней
                        $stmt = $pdo->prepare("
                            INSERT INTO laureates_counteries (laureate_id, country_id) 
                            VALUES (:laureate_id, :country_id)
                        ");
                        $stmt->execute(['laureate_id' => $newID, 'country_id' => $country['id']]);
                    } else {
                        // Если страна не найдена, создаем новую
                        $stmt = $pdo->prepare("INSERT INTO countries (country_name) VALUES (:country_name)");
                        $stmt->execute(['country_name' => $data['country']]);
                        $countryId = $pdo->lastInsertId();
    
                        // Связываем лауреата с новой страной
                        $stmt = $pdo->prepare("
                            INSERT INTO laureates_counteries (laureate_id, country_id) 
                            VALUES (:laureate_id, :country_id)
                        ");
                        $stmt->execute(['laureate_id' => $newID, 'country_id' => $countryId]);
                    }
                }
    
                // Возвращаем полную информацию о лауреате
                $new_laureate = $laureate->getLaureateWithDetails($newID);
                http_response_code(201);
                echo json_encode([
                    'message' => "Created successfully",
                    'data' => $new_laureate
                ]);
                break;
            } elseif ($route[0] == 'laureates' && $route[1] == 'upload') {
                // Обработка загрузки нескольких лауреатов
                $data = json_decode(file_get_contents('php://input'), true);
    
                if (!is_array($data)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Invalid JSON format: expected an array of laureates']);
                    break;
                }
    
                $results = [];
                foreach ($data as $laureateData) {
                    // Проверяем обязательные поля для каждого лауреата
                    if (!isset($laureateData['fullname']) || !isset($laureateData['sex']) || !isset($laureateData['birth_year'])) {
                        $results[] = ['error' => 'Missing required fields: fullname, sex, or birth_year'];
                        continue;
                    }
    
                    // Создаем лауреата
                    $newID = $laureate->store(
                        $laureateData['fullname'],
                        $laureateData['sex'],
                        $laureateData['birth_year'],
                        $laureateData['death_year'] ?? null,
                        $laureateData['organisation'] ?? null
                    );
    
                    if (!is_numeric($newID)) {
                        $results[] = ['error' => 'Failed to create laureate', 'data' => $newID];
                        continue;
                    }
    
                    // Создаем приз (если указаны данные)
                    if (isset($laureateData['year']) && isset($laureateData['category'])) {
                        $prizeData = [
                            'year' => $laureateData['year'],
                            'category' => $laureateData['category'],
                            'contrib_sk' => $laureateData['contribution_sk'] ?? '',
                            'contrib_en' => $laureateData['contribution_en'] ?? '',
                            'details_id' => null  // Если есть details, нужно добавить их отдельно
                        ];
    
                        $stmt = $pdo->prepare("
                            INSERT INTO prizes (year, category, contrib_sk, contrib_en, details_id) 
                            VALUES (:year, :category, :contrib_sk, :contrib_en, :details_id)
                        ");
                        $stmt->execute($prizeData);
                        $prizeId = $pdo->lastInsertId();
    
                        // Связываем лауреата с призом
                        $stmt = $pdo->prepare("
                            INSERT INTO laureates_prizes (laureate_id, prize_id) 
                            VALUES (:laureate_id, :prize_id)
                        ");
                        $stmt->execute(['laureate_id' => $newID, 'prize_id' => $prizeId]);
                    }
    
                    // Связываем лауреата с страной (если указана)
                    if (isset($laureateData['country'])) {
                        // Ищем страну по имени
                        $stmt = $pdo->prepare("SELECT id FROM countries WHERE country_name = :country");
                        $stmt->execute(['country' => $laureateData['country']]);
                        $country = $stmt->fetch(PDO::FETCH_ASSOC);
    
                        if ($country) {
                            // Если страна найдена, связываем лауреата с ней
                            $stmt = $pdo->prepare("
                                INSERT INTO laureates_counteries (laureate_id, country_id) 
                                VALUES (:laureate_id, :country_id)
                            ");
                            $stmt->execute(['laureate_id' => $newID, 'country_id' => $country['id']]);
                        } else {
                            // Если страна не найдена, создаем новую
                            $stmt = $pdo->prepare("INSERT INTO countries (country_name) VALUES (:country_name)");
                            $stmt->execute(['country_name' => $laureateData['country']]);
                            $countryId = $pdo->lastInsertId();
    
                            // Связываем лауреата с новой страной
                            $stmt = $pdo->prepare("
                                INSERT INTO laureates_counteries (laureate_id, country_id) 
                                VALUES (:laureate_id, :country_id)
                            ");
                            $stmt->execute(['laureate_id' => $newID, 'country_id' => $countryId]);
                        }
                    }
    
                    // Возвращаем информацию о созданном лауреате
                    $new_laureate = $laureate->getLaureateWithDetails($newID);
                    $results[] = ['success' => 'Laureate created successfully', 'data' => $new_laureate];
                }
    
                // Возвращаем результаты
                http_response_code(201);
                echo json_encode([
                    'message' => 'Batch processing completed',
                    'results' => $results
                ]);
                break;
            }
            http_response_code(400);
            echo json_encode(['message' => 'Bad request']);
            break;
    
        default:
            http_response_code(405);
            echo json_encode(['message' => 'Method not allowed']);
            break;
        
            case 'PUT':
                if ($route[0] == 'laureates' && count($route) == 2 && is_numeric($route[1])) {
                    $currentID = $route[1];
                    $currentData = $laureate->show($currentID);
            
                    // Проверяем, существует ли лауреат
                    if (!$currentData) {
                        http_response_code(404);
                        echo json_encode(['message' => 'Laureate not found']);
                        break;
                    }
            
                    // Получаем обновленные данные из тела запроса
                    $updatedData = json_decode(file_get_contents('php://input'), true);
            
                    // Отключаем вывод ошибок
                    error_reporting(0);
                    ini_set('display_errors', 0);
                    // Проверяем наличие обязательных полей
                    if (!isset($updatedData['fullname'], $updatedData['sex'], $updatedData['birth_year'])) {
                        http_response_code(400);
                        echo json_encode(['message' => 'Missing required fields']);
                        break;
                    }
            
                    // Обновляем данные лауреата
                    $status = $laureate->update(
                        $currentID,
                        $updatedData['fullname'],
                        $updatedData['sex'],
                        $updatedData['birth_year'],
                        $updatedData['death_year'] ?? $currentData['death_year'],
                        $updatedData['organisation'] ?? $currentData['organisation']
                    );
            
                    if ($status !== true) {
                        http_response_code(400);
                        echo json_encode(['message' => "Bad request", 'data' => $status]);
                        break;
                    }
            
                    // Обновляем страны (если указано)
                    if (isset($updatedData['country'])) {
                        $updateCountries = $laureate->updateLaureateCountries($currentID, $updatedData['country']);
                        if ($updateCountries !== true) {
                            http_response_code(400);
                            echo json_encode(['message' => "Failed to update country", 'data' => $updateCountries]);
                            break;
                        }
                    }
            
                    // Обновляем призы (если указано)
                    if (isset($updatedData['year']) && isset($updatedData['category'])) {
                        // Находим текущий prize_id
                        $prizeId = $laureate->findCurrentPrizeId($currentID);
            
                        // Если приз не найден, создаем новый
                        if (!$prizeId) {
                            $stmt = $this->pdo->prepare("
                                INSERT INTO prizes (year, category, contrib_sk, contrib_en) 
                                VALUES (:year, :category, :contrib_sk, :contrib_en)
                            ");
                            $stmt->bindParam(':year', $updatedData['year'], PDO::PARAM_STR);
                            $stmt->bindParam(':category', $updatedData['category'], PDO::PARAM_STR);
                            $stmt->bindParam(':contrib_sk', $updatedData['contribution_sk'] ?? null, PDO::PARAM_STR);
                            $stmt->bindParam(':contrib_en', $updatedData['contribution_en'] ?? null, PDO::PARAM_STR);
                            $stmt->execute();
            
                            // Получаем ID нового приза
                            $prizeId = $this->pdo->lastInsertId();
                        }
            
                        // Обновляем данные о призе
                        $updatePrize = $laureate->updatePrize(
                            $prizeId,
                            $updatedData['year'],
                            $updatedData['category'],
                            $updatedData['contribution_sk'] ?? $currentData['contribution_sk'],
                            $updatedData['contribution_en'] ?? $currentData['contribution_en']
                        );
            
                        if ($updatePrize !== true) {
                            http_response_code(400);
                            echo json_encode(['message' => "Failed to update prize", 'data' => $updatePrize]);
                            break;
                        }
            
                        // Обновляем связь лауреата с призом
                        $updatePrizes = $laureate->updateLaureatePrizes($currentID, $prizeId);
                        if ($updatePrizes !== true) {
                            http_response_code(400);
                            echo json_encode(['message' => "Failed to update prize links", 'data' => $updatePrizes]);
                            break;
                        }
                    }
            
                    // Возвращаем обновленные данные
                    $updatedLaureate = $laureate->getLaureateWithDetails($currentID);
                    http_response_code(200);
                    echo json_encode([
                        'message' => "Updated successfully",
                        'data' => $updatedLaureate
                    ]);
                    break;
                }
            
                // Если маршрут не найден
                http_response_code(404);
                echo json_encode(['message' => 'Not found']);
                break;
            
                case 'DELETE':
                    if ($route[0] == 'laureates' && count($route) == 2 && is_numeric($route[1])) {
                        $id = $route[1];
                        $exist = $laureate->show($id);
                        if (!$exist) {
                            http_response_code(404);
                            echo json_encode(['message' => 'Laureate not found']);
                            break;
                        }
                
                        // Начинаем транзакцию
                        $pdo->beginTransaction();
                
                        try {
                            // 1. Получаем prize_id, связанные с лауреатом
                            $stmt = $pdo->prepare("SELECT prize_id FROM laureates_prizes WHERE laureate_id = :id");
                            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                            $stmt->execute();
                            $prizeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                            // 2. Получаем country_id, связанные с лауреатом
                            $stmt = $pdo->prepare("SELECT country_id FROM laureates_counteries WHERE laureate_id = :id");
                            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                            $stmt->execute();
                            $countryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                            // 3. Удаляем связи с призами
                            if (!empty($prizeIds)) {
                                $stmt = $pdo->prepare("DELETE FROM laureates_prizes WHERE prize_id IN (" . 
                                    implode(',', array_fill(0, count($prizeIds), '?')) . ")");
                                $stmt->execute($prizeIds);
                            }
                
                            // 4. Удаляем призы
                            if (!empty($prizeIds)) {
                                $stmt = $pdo->prepare("DELETE FROM prizes WHERE id IN (" . 
                                    implode(',', array_fill(0, count($prizeIds), '?')) . ")");
                                $stmt->execute($prizeIds);
                            }
                
                            // 5. Удаляем связи со странами
                            $stmt = $pdo->prepare("DELETE FROM laureates_counteries WHERE laureate_id = :id");
                            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                            $stmt->execute();
                
                            // 6. Удаляем страны, если они больше ни с кем не связаны
                            if (!empty($countryIds)) {
                                foreach ($countryIds as $countryId) {
                                    // Проверяем, есть ли другие лауреаты, связанные с этой страной
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM laureates_counteries WHERE country_id = :country_id");
                                    $stmt->bindParam(':country_id', $countryId, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $count = $stmt->fetchColumn();
                
                                    if ($count == 0) {
                                        // Если нет других связей, удаляем страну
                                        $stmt = $pdo->prepare("DELETE FROM countries WHERE id = :country_id");
                                        $stmt->bindParam(':country_id', $countryId, PDO::PARAM_INT);
                                        $stmt->execute();
                                        error_log("Deleted country with ID: $countryId");
                                    }
                                }
                            }
                
                            // 7. Удаляем самого лауреата
                            $stmt = $pdo->prepare("DELETE FROM laureates WHERE id = :id");
                            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                            $stmt->execute();
                
                            $pdo->commit();
                            http_response_code(200);
                            echo json_encode(['message' => "Deleted successfully"]);
                            break;
                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            error_log("Transaction rolled back due to error: " . $e->getMessage());
                            http_response_code(400);
                            echo json_encode(['message' => "Failed to delete laureate", 'data' => $e->getMessage()]);
                            break;
                        }
                    }
                    http_response_code(404);
                    echo json_encode(['message' => 'Not found']);
                    break;
                }
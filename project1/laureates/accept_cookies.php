<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['accept']) && $data['accept'] === true) {
        // Зберігаємо вибір користувача у сесії
        $_SESSION['cookies_accepted'] = true;
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false]);
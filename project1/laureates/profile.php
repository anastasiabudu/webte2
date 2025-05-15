<?php
session_start();

// Проверка, был ли вход через Google
$isGoogleUser = isset($_SESSION["login_type"]) && $_SESSION["login_type"] === "google";

// Zobrazenie všetkých chýb pre ladenie
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pripojenie k databáze
$hostname = "localhost";
$database = "nobels";
$username = "xbudu";
$password = "23122002";

function connectDatabase($hostname, $database, $username, $password) {
    try {
        $conn = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Chyba pripojenia: " . $e->getMessage());
    }
}

$pdo = connectDatabase($hostname, $database, $username, $password);

// Overenie prihlásenia používateľa
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$userId = $_SESSION["id"] ?? null; // Используем оператор null coalescing для избежания ошибки
$error = "";
$success = "";

// Если пользователь вошел через Google, пропускаем запросы к базе данных
if (!$isGoogleUser && $userId) {
    // Načítanie údajov používateľa z databázy
    $sql = "SELECT fullname, email, password FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Overenie, či bol používateľ nájdený
    if (!$user) {
        die("Chyba: Používateľ nebol nájdený.");
    }
    $_SESSION["fullname"] = $user["fullname"];
} else {
    // Для пользователей, вошедших через Google, используем данные из сессии
    $user = [
        "fullname" => $_SESSION["fullname"] ?? "Google User",
        "email" => $_SESSION["email"] ?? "No email",
        "password" => null // Пароль отсутствует для Google-пользователей
    ];
}

// Spracovanie zmeny mena
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["new_name"]) && !$isGoogleUser) {
    $newName = trim($_POST["new_name"]);
    if (empty($newName)) {
        $error = "❌ Zadajte nové meno.";
    } elseif (strlen($newName) > 128) {
        $error = "❌ Meno nemôže byť dlhšie ako 128 znakov.";
    } else {
        $sql = "UPDATE users SET fullname = :fullname WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":fullname", $newName, PDO::PARAM_STR);
        $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION["fullname"] = $newName;
            $success = "✅ Meno bolo úspešne zmenené!";
        } else {
            $error = "❌ Chyba pri aktualizácii mena.";
        }
    }
}

// Spracovanie zmeny hesla
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["current_password"], $_POST["new_password"], $_POST["confirm_password"])) {
    // Проверка, вошел ли пользователь через Google
    if ($isGoogleUser) {
        $error = "❌ Používateľ prihlásený cez Google nemôže meniť heslo.";
    } else {
        // Для обычных пользователей проверяем пароль
        $currentPassword = trim($_POST["current_password"]);
        $newPassword = trim($_POST["new_password"]);
        $confirmPassword = trim($_POST["confirm_password"]);

        // Проверка, что пароль не равен null
        if ($user["password"] === null) {
            $error = "❌ Chyba: Heslo nie je k dispozícii.";
        } elseif (!password_verify($currentPassword, $user["password"])) {
            $error = "❌ Nesprávne aktuálne heslo.";
        } elseif (strlen($newPassword) < 6) {
            $error = "❌ Heslo musí mať aspoň 6 znakov.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "❌ Nové heslá sa nezhodujú.";
        } else {
            // Hashovanie nového hesla a aktualizácia v databáze
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":password", $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $success = "✅ Heslo bolo úspešne zmenené!";
            } else {
                $error = "❌ Chyba pri aktualizácii hesla.";
            }
        }
    }
}

// Načítanie histórie prihlásení používateľa
if (!$isGoogleUser && $userId) {
    $sql = "SELECT login_type, email, login_time FROM users_login WHERE user_id = :user_id ORDER BY login_time DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $logins = []; // Для Google-пользователей история отсутствует
}
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Používateľský profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <!-- Informácie o profile -->
            <div class="card shadow-sm" style="max-width: 500px; margin: auto;">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="mb-0">Informácie o profile</h5>
                </div>
                <div class="card-body text-center">
                    <p><strong>Meno:</strong> <?= htmlspecialchars($_SESSION["fullname"]) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user["email"]) ?></p>
                    <?php if ($isGoogleUser): ?>
                        <p><strong>Prihlásený cez:</strong> Google</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($isGoogleUser): ?>
                <div class="alert alert-info text-center mt-2">
                    Prihlásený cez Google. Zmena mena a hesla nie je možná.
                </div>
            <?php endif; ?>

            <!-- Zobrazenie chybových a úspešných správ -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center mt-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success text-center mt-3"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Formulár na zmenu mena -->
            <?php if (!$isGoogleUser): ?>
                <div class="card shadow-sm mt-4" style="max-width: 500px; margin: auto;">
                    <div class="card-header bg-warning text-dark text-center">
                        <h5 class="mb-0">Zmeniť meno</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <input type="text" name="new_name" class="form-control text-center my-2"
                                   value="<?= htmlspecialchars($_SESSION["fullname"]) ?>" required>
                            <button type="submit" class="btn btn-warning w-100">Zmeniť meno</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulár na zmenu hesla -->
            <?php if (!$isGoogleUser): ?>
                <div class="card shadow-sm mt-4" style="max-width: 500px; margin: auto;">
                    <div class="card-header bg-danger text-white text-center">
                        <h5 class="mb-0">Zmeniť heslo</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <input type="password" name="current_password" class="form-control text-center my-2"
                                   placeholder="Aktuálne heslo" required>
                            <input type="password" name="new_password" class="form-control text-center my-2"
                                   placeholder="Nové heslo" required>
                            <input type="password" name="confirm_password" class="form-control text-center my-2"
                                   placeholder="Potvrdiť heslo" required>
                            <button type="submit" class="btn btn-danger w-100">Zmeniť heslo</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm mt-4" style="max-width: 500px; margin: auto;">
                <div class="card-header bg-info text-white text-center">
                    <h5 class="mb-0">História prihlásení</h5>
                </div>
                <div class="card-body history-body">
                    <table class="table table-striped history-table">
                        <thead>
                            <tr>
                                <th>Typ</th>
                                <th>Email</th>
                                <th>Dátum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logins as $login): ?>
                                <tr>
                                    <td><?= htmlspecialchars($login["login_type"]) ?></td>
                                    <td><?= htmlspecialchars($login["email"]) ?></td>
                                    <td><?= htmlspecialchars($login["login_time"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($logins)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">Žiadne záznamy</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tlačidlo späť k laureátom -->
            <div class="text-center mt-3">
                <a href="all_laureates.php" class="btn btn-secondary btn-sm px-3">Späť k laureátom</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
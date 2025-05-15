<?php
require_once '../config.php';
session_start();
$db = connectDatabase($hostname, $database, $username, $password);

// Získavame ID laureáta z GET požiadavky
$laureate_id = $_GET['id'] ?? null;

if (!$laureate_id) {
    die("Chyba: ID laureáta nie je zadané.");
}

try {
    // Pripravujeme SQL dotaz
    $sql = "SELECT 
                p.year AS prize_year,
                p.category,
                l.fullname,
                l.birth_year,
                l.death_year,
                c.country_name,
                p.contrib_en,
                p.contrib_sk
            FROM laureates l
            JOIN laureates_prizes lp ON l.id = lp.laureate_id
            JOIN prizes p ON lp.prize_id = p.id
            JOIN laureates_counteries lc ON l.id = lc.laureate_id
            JOIN countries c ON lc.country_id = c.id
            WHERE l.id = :laureate_id";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':laureate_id', $laureate_id, PDO::PARAM_INT);
    $stmt->execute();

    // Získavame údaje o laureátovi
    $laureate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$laureate) {
        die("Laureát s ID $laureate_id nebol nájdený.");
    }
} catch (PDOException $e) {
    die("Chyba pri vykonávaní dotazu: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laureát Nobelovej ceny <?= htmlspecialchars($laureate['fullname']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .cap { text-transform: capitalize;}
    </style>
</head>
<body class="container">
<?php

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Užívateľ nie je prihlásený, zobrazíme odkazy na prihlásenie a registráciu
    echo '
    <div class="text-center mt-4">
        <p class="lead">Pre pokračovanie sa prosím prihláste alebo zaregistrujte.</p>
        <a href="login.php" class="btn btn-primary btn-custom">Prihlásiť sa</a>
        <a href="register.php" class="btn btn-success btn-custom">Zaregistrovať sa</a>
    </div>';
} else {

?>

<header style="background: #e1f2fa;">
  <nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
      <a class="navbar-brand" href="/">
        <img src="../img/logo.jpg" alt="Logo" width="50" height="50" style="background-color: #E1F2FA;">
        Laureáti Nobelovej ceny
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Prepínač navigácie">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
            <li class="nav-item d-flex align-items-center">
                <?php echo $_SESSION['fullname']; ?>
                <a class="nav-link d-flex align-items-center ms-2" title="zmeniť" href="/profile.php">
                    <i class="bi bi-pencil"></i>
                </a>
            </li>
            <li class="nav-item">
            <a class="nav-link" href="/logout.php">Odhlásiť sa</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>
<main>
    <h1 class="mt-2 mb-4">Nositeľ Nobelovej ceny <?= htmlspecialchars($laureate['fullname']) ?></h1>
    <table class="table table-bordered">
        <tr>
            <th>Rok ceny</th>
            <td><?= htmlspecialchars($laureate['prize_year']) ?></td>
        </tr>
        <tr>
            <th>Kategória</th>
            <td class="cap"><?= htmlspecialchars($laureate['category']) ?></td>
        </tr>
        <tr>
            <th>Meno</th>
            <td><?= htmlspecialchars($laureate['fullname']) ?></td>
        </tr>
        <tr>
            <th>Rok narodenia</th>
            <td><?= htmlspecialchars($laureate['birth_year']) ?></td>
        </tr>
        <tr>
            <th>Rok úmrtia</th>
            <td><?= htmlspecialchars($laureate['death_year'] ?? '-') ?></td>
            </tr>
        <tr>
            <th>Štát</th>
            <td><?= htmlspecialchars($laureate['country_name']) ?></td>
        </tr>
        <tr>
            <th>Opis (angl.)</th>
            <td class="cap"><?= htmlspecialchars($laureate['contrib_en']) ?></td>
        </tr>
        <tr>
            <th>Opis (slovenský)</th>
            <td class="cap"><?= htmlspecialchars($laureate['contrib_sk']) ?></td>
        </tr>
    </table>

    <a href="/test2/all_laureates.php" class="btn btn-primary">Späť na zoznam laureátov</a>
</main>

    <?php
}
?>
</body>
</html>
<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

require_once('config.php');  // $pdo
require_once 'vendor/autoload.php';

use Google\Client;

$client = new Client();
// Required, call the setAuthConfig function to load authorization credentials from
// client_secret.json file. The file can be downloaded from Google Cloud Console.
$client->setAuthConfig('client_secret.json');

// User granted permission as an access token is in the session.
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);

    // Get the user profile info from Google OAuth 2.0.
    $oauth = new Google\Service\Oauth2($client);
    $account_info = $oauth->userinfo->get();

    $_SESSION['fullname'] = $account_info->name;
    $_SESSION['gid'] = $account_info->id;
    $_SESSION['email'] = $account_info->email;
}

?>

<!doctype html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Zabezpečená stránka</title>

    <!-- Подключение Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #0d6efd;
        }
        .btn-custom {
            margin: 5px;
        }
        .welcome-message {
            margin-top: 20px;
            font-size: 1.2em;
            color: #198754;
        }
        .err {
            color: red;
        }
        .cookie-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #343a40;
            color: white;
            padding: 10px;
            text-align: center;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <header class="mb-4" style="background: #e1f2fa;">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="/">
                    <img src="img/logo.jpg" alt="Логотип" width="50" height="50">
                    Zabezpečená stránka
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="logout.php">Odhlásenie</a></li>
                        <li class="nav-item"><a class="nav-link" href="all_laureates.php">Laureáti Nobelovej ceny</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="container py-4">
        <header class="text-center mb-5">
            <hgroup>
                <h1 class="display-4">Zabezpečená stránka</h1>
                <h2 class="display-6">Obsah tejto stránky je dostupný len po prihlásení.</h2>
            </hgroup>
        </header>

        <main>
            <h3 class="welcome-message">Vitaj <?php echo $_SESSION['fullname']; ?></h3>
            <p><strong>e-mail:</strong> <?php echo $_SESSION['email']; ?></p>

            <?php if (isset($_SESSION['gid'])) : ?>
                <p><strong>Si prihlásený cez Google účet, ID:</strong> <?php echo $_SESSION['gid']; ?></p>
            <?php else : ?>
                <p><strong>Si prihlásený cez lokálne údaje.</strong></p>
                <p><strong>Dátum vytvorenia konta:</strong> <?php echo $_SESSION['created_at'] ?></p>
            <?php endif; ?>

            <p><a href="logout.php" class="btn btn-danger btn-custom">Odhlásiť sa</a></p>
        </main>
    </div>

    <!-- Cookie banner -->
    <?php if (!isset($_SESSION["cookies_accepted"])) : ?>
        <div class="cookie-banner">
            <div class="container d-flex justify-content-between align-items-center">
                <p class="mb-0">Táto stránka používa súbory cookies na zlepšenie používateľskej skúsenosti.</p>
                <button class="btn btn-light btn-sm" id="cookieAccept">Súhlasím</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Подключение Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('cookieAccept').addEventListener('click', function() {
            document.querySelector('.cookie-banner').style.display = 'none';
            fetch('accept_cookies.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ accept: true })
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      console.log('Cookies accepted.');
                  }
              });
        });
    </script>
</body>

</html>

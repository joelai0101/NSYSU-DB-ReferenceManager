<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5;url=index.php">
    <title>歡迎 - 文獻管理系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Noto Sans TC', sans-serif;
            background: linear-gradient(45deg, #6CC1ED, #31708E);
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        h1 {
            font-size: 2.5rem; /* Adjust size accordingly */
            color: white;
        }
        p {
            font-size: 1.25rem; /* Adjust size accordingly */
            color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="display-4">歡迎，<?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
                <p class="lead">您將在幾秒鐘後被自動重新導向到儀表板。</p>
            </div>
        </div>
    </div>
</body>
</html>

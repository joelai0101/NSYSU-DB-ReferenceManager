<?php
// Include config file
require_once "dbconfig.php";

$username = $password = $confirm_password = $email = "";
$username_err = $password_err = $confirm_password_err = $email_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "請輸入使用者名稱。";
    } else {
        // Prepare a select statement to check if username exists
        $sql = "SELECT UserID FROM Users WHERE username = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "此使用者名稱已被佔用。";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "哎呀！出了點問題。請稍後再試。";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "請輸入電子郵件。";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "請輸入密碼。";
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "密碼至少需要6個字元。";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "請確認密碼。";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if($password != $confirm_password){
            $confirm_password_err = "密碼不匹配。";
        }
    }

    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO Users (username, password, email) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password, $param_email);
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_email = $email;
            if(mysqli_stmt_execute($stmt)){
                // Redirect to login page
                header("location: login.php");
            } else {
                echo "哎呀！出了點問題。請稍後再試。";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊 - 文獻管理系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Noto Sans TC', sans-serif;
            background: linear-gradient(45deg, #6CC1ED, #31708E);
        }
        .container {
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card {
            width: 22rem;
            background-color: rgba(255, 255, 255, 0.85);
        }
        .btn-primary {
            background-color: #4A90E2;
            border: none;
        }
        .btn-primary:hover {
            background-color: #31708E;
        }
        .form-control {
            border-radius: 0.25rem;
        }
        .card-body {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">註冊</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group mb-3">
                        <label class="form-label">使用者名稱</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">電子郵件</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <div class="invalid-feedback"><?php echo $email_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">密碼</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">確認密碼</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                        <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                    </div>
                    <div class="form-group mb-4">
                        <input type="submit" class="btn btn-primary w-100" value="註冊">
                    </div>
                    <p class="text-center">已經有帳號？<a href="login.php">立即登入</a>.</p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

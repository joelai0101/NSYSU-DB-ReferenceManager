<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect them to the welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: welcome.php");
    exit;
}

// Include config file
require_once "dbconfig.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "請輸入使用者名稱。";
    } else{
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "請輸入密碼。";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT UserID, username, password FROM Users WHERE username = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["UserID"] = $id; // UserID 記得大寫
                            $_SESSION["username"] = $username;                            
                            
                            // Redirect user to welcome page
                            header("location: welcome.php");
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "提供的密碼不正確。";
                        }
                    }
                } else{
                    // Username doesn't exist, display a generic error message
                    $login_err = "找不到該使用者名稱。";
                }
            } else{
                echo "哎呀！出了點問題。請稍後再試。";
            }

            // Close statement
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
    <title>登入 - 文獻管理系統</title>
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
                <h2 class="card-title text-center mb-4">文獻管理系統 - 登入</h2>
                <?php 
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }
                ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group mb-3">
                        <label class="form-label">使用者名稱</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">密碼</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    </div>
                    <div class="form-group mb-4">
                        <input type="submit" class="btn btn-primary w-100" value="登入">
                    </div>
                    <p class="text-center">還沒有帳號？<a href="register.php">現在註冊</a></p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

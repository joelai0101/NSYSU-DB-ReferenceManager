<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "dbconfig.php"; // Assumes you have a dbconfig.php file to handle the database connection

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$categoryID = $_GET['categoryID'] ?? null;
$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $categoryName = trim($_POST["categoryName"]);
    $categoryDescription = trim($_POST["categoryDescription"]);
    $isPublic = isset($_POST["isPublic"]) ? 1 : 0;

    if (empty($categoryName)) {
        $error = "請輸入分類名稱。";
    } else {
        $sql = "UPDATE Categories SET CategoryName = ?, CategoryDescription = ?, IsPublic = ? WHERE CategoryID = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssii", $categoryName, $categoryDescription, $isPublic, $categoryID);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "分類已成功更新。";
                header("Location: successPage.php");
                exit;
            } else {
                $error = "發生錯誤，請再試一次。";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
} else {
    // Load existing category data
    if ($categoryID) {
        $sql = "SELECT CategoryName, CategoryDescription, isPublic FROM Categories WHERE CategoryID = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $categoryID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $category = mysqli_fetch_assoc($result);
            if (!$category) {
                header("location: errorPage.php"); // Redirect if the category doesn't exist
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯分類 - 文獻管理系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Style same as addCategory.php -->
    <style>
        body, html {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            height: 100%;
        }
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            background-color: #343a40;
            padding-top: 0; /* Adjust as needed */
            transition: width 0.3s;
            overflow-y: auto; /* For scrollable sidebar */
        }
        .main-header {
            width: calc(100% - 250px);
            height: 50px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            left: 250px; /* Aligns with the right edge of the sidebar */
            top: 0;
            z-index: 1030;
            transition: left 0.3s, width 0.3s;
        }
        .content-wrapper {
            padding-top: 50px; /* Space for the header */
            margin-left: 250px;
            transition: margin-left 0.3s;
        }
        .sidebar.collapsed {
            width: 0;
        }
        .content-wrapper.collapsed, .main-header.collapsed {
            margin-left: 0;
            left: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar and Main Header same as addCategory.php -->
        <div id="sidebar" class="bg-dark sidebar">
            <div class="sidebar-header">
                <a href="/" class="brand-link">
                    <h5 class="text-white text-center py-3">文獻管理系統</h5>
                </a>
            </div>
            <h4 class="text-white text-center" style="line-height: 1em;"><?php echo htmlspecialchars($_SESSION["username"]); ?> 的儀表板</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white" href="/browseDocuments.php"><i class="bi bi-journal-text"></i> 公開文獻</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/myDocuments.php"><i class="bi bi-collection"></i> 我的文獻</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/myUploads.php"><i class="bi bi-collection-fill"></i> 我的提交</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/addDocument.php"><i class="bi bi-journal-plus"></i> 新增文獻</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/browseCategories.php"><i class="bi bi-tags"></i> 公開分類</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/myCategories.php"><i class="bi bi-tags-fill"></i> 我的分類</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/addCategory.php"><i class="bi bi-tag"></i> 新增分類</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/logout.php"><i class="bi bi-box-arrow-left"></i> 登出</a>
                </li>
            </ul>
        </div>
        <!-- Main Header -->
        <div id="mainHeader" class="main-header">
            <button class="btn" type="button" onclick="toggleSidebar()" style="margin-left: 10px;">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <!-- Content Wrapper. Contains page content -->
        <div id="content" class="content-wrapper">
            <div class="container-fluid pt-3">
                <div class="row">
                    <div class="col-md-12">
                        <!-- Display messages -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Form to edit a Category -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">編輯分類</h3>
                            </div>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?categoryID=' . $categoryID; ?>" method="post">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="categoryName">分類名稱</label>
                                        <input type="text" class="form-control" id="categoryName" name="categoryName" value="<?php echo htmlspecialchars($category['CategoryName']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="categoryDescription">描述</label>
                                        <textarea class="form-control" id="categoryDescription" name="categoryDescription" rows="4"><?php echo htmlspecialchars($category['CategoryDescription']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="isPublic">是否公開</label>
                                        <input type="checkbox" id="isPublic" name="isPublic" value="1" <?php echo ($category['isPublic'] ? 'checked' : ''); ?>>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">更新</button>
                                    <a href="browseCategories.php" class="btn btn-secondary">返回</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Initial check on page load
        document.addEventListener('DOMContentLoaded', function() {
            handlePublicationTypeChange(); // Adjust fields visibility based on initial selection
        });

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('content').classList.toggle('collapsed');
            document.getElementById('mainHeader').classList.toggle('collapsed');
        }
    </script>
    <!-- Sidebar toggle script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

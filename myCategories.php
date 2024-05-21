<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "dbconfig.php"; // Assumes a dbconfig.php file to handle the database connection

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Fetch categories created by the logged-in user
$categories = [];
$query = "SELECT CategoryID, CategoryName, CategoryDescription, IsPublic FROM Categories WHERE UserID = ?";
if ($stmt = mysqli_prepare($link, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['UserID']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的分類 - 文獻管理系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Style same as index.php -->
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
            padding-top: 0;
            transition: width 0.3s;
            overflow-y: auto;
        }
        .main-header {
            width: calc(100% - 250px);
            height: 50px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            left: 250px;
            top: 0;
            z-index: 1030;
            transition: left 0.3s, width 0.3s;
        }
        .content-wrapper {
            padding-top: 50px;
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
        <!-- Sidebar and Main Header same as index.php -->
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
        <div id="mainHeader" class="main-header">
            <button class="btn" type="button" onclick="toggleSidebar()" style="margin-left: 10px;">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <!-- Page Content -->
        <div id="content" class="content-wrapper">
            <div class="container-fluid">
                <h2 class="mt-3">瀏覽我的分類</h2>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>分類名稱</th>
                            <th>公開性</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['CategoryName']); ?></td>
                            <td><?php echo $category['IsPublic'] ? '公開' : '私有'; ?></td>
                            <td>
                                <a href="viewCategory.php?categoryID=<?php echo $category['CategoryID']; ?>" class="btn btn-primary">檢視</a>
                                <a href="editCategory.php?categoryID=<?php echo $category['CategoryID']; ?>" class="btn btn-info">編輯</a>
                                <a href="deleteCategory.php?categoryID=<?php echo $category['CategoryID']; ?>" class="btn btn-danger" onclick="return confirm('確定要刪除這個分類嗎？');">刪除</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Sidebar toggle script same as index.php -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('content').classList.toggle('collapsed');
            document.getElementById('mainHeader').classList.toggle('collapsed');
        }
    </script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

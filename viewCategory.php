<?php
session_start();
require_once "dbconfig.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$categoryID = $_GET['categoryID'] ?? null; // Get category ID from URL
$category = [];
$documents = [];
$creatorUsername = '';

if ($categoryID) {
    // Fetch category details
    $sql = "SELECT c.CategoryName, c.CategoryDescription, c.IsPublic, u.username AS Creator FROM Categories c JOIN Users u ON c.UserID = u.UserID WHERE c.CategoryID = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $categoryID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $category = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
    }

    // Fetch documents in this category
    $docSql = "SELECT * FROM Documents WHERE CategoryID = ?";
    if ($docStmt = mysqli_prepare($link, $docSql)) {
        mysqli_stmt_bind_param($docStmt, "i", $categoryID);
        mysqli_stmt_execute($docStmt);
        $docResult = mysqli_stmt_get_result($docStmt);
        while ($row = mysqli_fetch_assoc($docResult)) {
            $documents[] = $row;
        }
        mysqli_stmt_close($docStmt);
    }
}

mysqli_close($link);

if (!$category) {
    echo "分類不存在或無法訪問。";
    exit;
}
?>


<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>檢視分類 - 文獻管理系統</title>
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
        <!-- Sidebar and Main Header same as index.php -->
        <!-- Sidebar -->
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
        <!-- Page Content -->
        <div id="content" class="content-wrapper">
            <div class="container-fluid">
                <h2 class="mt-3">檢視分類</h2>
                <p>分類名稱：<?php echo htmlspecialchars($category['CategoryName']); ?></p>
                <p>分類描述：<?php echo htmlspecialchars($category['CategoryDescription']); ?></p>
                <p>公開性：<?php echo $category['IsPublic'] ? '公開' : '私有'; ?></p>
                <p>創建者：<?php echo htmlspecialchars($category['Creator']); ?></p>
                <a href="editCategory.php?categoryID=<?php echo $categoryID; ?>" class="btn btn-primary">編輯</a>
                <a href="deleteCategory.php?categoryID=<?php echo $categoryID; ?>" class="btn btn-danger" onclick="return confirm('確定要刪除這個分類嗎？');">刪除</a>
                <h3 class="mt-4">分類下的文獻：</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>標題</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach ($documents as $doc) {
                                // Determine if it's a journal based on JournalVol and JournalNo
                                $isJournal = !empty($doc['JournalVol']) && !empty($doc['JournalNo']);

                                // Display format based on type
                                if ($isJournal) {
                                    $pageNumberDisplay = !empty($doc['PageNumber']) && $doc['PageNumber'] != 0 ? "pp." . htmlspecialchars($doc['PageNumber']) . ", " : "";
                                    $publicationTypeDisplay = htmlspecialchars($doc['Authors']) . ", “" . htmlspecialchars($doc['Title']) . "”, <i>" . htmlspecialchars($doc['PublicationSource']) . "</i>, Vol." . htmlspecialchars($doc['JournalVol']) . ", No." . htmlspecialchars($doc['JournalNo']) . ", " . $pageNumberDisplay . htmlspecialchars($doc['PublicationYear']) . ".";
                                } else {
                                    $pageNumberDisplay = !empty($doc['PageNumber']) && $doc['PageNumber'] != 0 ? "pp." . htmlspecialchars($doc['PageNumber']) . ", ": "";
                                    $publicationTypeDisplay = htmlspecialchars($doc['Authors']) . ", “" . htmlspecialchars($doc['Title']) . "”, <i>" . htmlspecialchars($doc['PublicationSource']) . "</i>, " . $pageNumberDisplay . htmlspecialchars($doc['PublicationYear']) . ".";
                                }
                        ?>
                            <tr>
                                <td><?php echo $publicationTypeDisplay; ?></td>
                                <td><a href="viewDocument.php?documentID=<?php echo $doc['DocumentID']; ?>" class="btn btn-primary">檢視</a></td>
                            </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Scripts -->
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

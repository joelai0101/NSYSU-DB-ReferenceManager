<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "dbconfig.php"; // Assumes you have a dbconfig.php file for database connection

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$search = $categoryFilter = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}
if (isset($_GET['category'])) {
    $categoryFilter = trim($_GET['category']);
}

// Fetch categories for filter
$categories = [];
$categoryQuery = "SELECT CategoryID, CategoryName FROM Categories WHERE UserID = ? OR IsPublic = 1";
if ($categoryStmt = mysqli_prepare($link, $categoryQuery)) {
    mysqli_stmt_bind_param($categoryStmt, "i", $_SESSION['UserID']);
    mysqli_stmt_execute($categoryStmt);
    $categoryResult = mysqli_stmt_get_result($categoryStmt);
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row;
    }
    mysqli_stmt_close($categoryStmt);
}

// Fetch documents uploaded by the logged-in user, filtered by search and category
$documents = [];
$query = "SELECT * FROM Documents WHERE UserID = ? AND (Title LIKE CONCAT('%',?,'%') OR Authors LIKE CONCAT('%',?,'%'))";
if (!empty($categoryFilter)) {
    $query .= " AND CategoryID = ?";
}
if ($stmt = mysqli_prepare($link, $query)) {
    if (!empty($categoryFilter)) {
        mysqli_stmt_bind_param($stmt, "issi", $_SESSION['UserID'], $search, $search, $categoryFilter);
    } else {
        mysqli_stmt_bind_param($stmt, "iss", $_SESSION['UserID'], $search, $search);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $documents[] = $row;
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
    <title>我的提交 - 文獻管理系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Styles similar to other pages -->
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
        <!-- Page Content -->
        <div id="content" class="content-wrapper">
            <div class="container-fluid">
                <h2 class="mt-3">瀏覽我的提交</h2>
                <!-- Search and Filter Form -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="search" name="search" placeholder="搜索標題或作者" value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-control" id="category" name="category" onchange="applyFilters()">
                            <option value="">所有分類</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['CategoryID']; ?>" <?php echo ($categoryFilter == $category['CategoryID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['CategoryName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">應用篩選</button>
                    </div>
                </div>
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
    <!-- Scripts for sidebar toggle -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('content').classList.toggle('collapsed');
            document.getElementById('mainHeader').classList.toggle('collapsed');
        }
        function applyFilters() {
            // Get the search and category values from the input and select elements
            var searchValue = document.getElementById('search').value;
            var categoryValue = document.getElementById('category').value;

            // Construct the query string
            var queryString = '?';
            if (searchValue) {
                queryString += 'search=' + encodeURIComponent(searchValue) + '&';
            }
            if (categoryValue) {
                queryString += 'category=' + encodeURIComponent(categoryValue);
            }

            // Reload the page with the new query string
            window.location.href = 'myUploads.php' + queryString;
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

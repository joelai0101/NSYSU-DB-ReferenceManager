<?php
session_start();
require_once "dbconfig.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$documentID = $_GET['documentID'] ?? null;
$document = [];
$userCanEdit = false;

if ($documentID) {
    $sql = "SELECT Documents.*, Categories.CategoryName, Users.username AS UserName 
            FROM Documents 
            LEFT JOIN Categories ON Documents.CategoryID = Categories.CategoryID 
            LEFT JOIN Users ON Documents.UserID = Users.UserID 
            WHERE Documents.DocumentID = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $documentID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $document = mysqli_fetch_assoc($result);
        if ($document && $_SESSION['UserID'] == $document['UserID']) {
            $userCanEdit = true; // User can edit/delete if they are the owner
        }
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
    }
}
mysqli_close($link);

if (!$document) {
    echo "文獻不存在或無法訪問。";
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>檢視文獻 - 文獻管理系統</title>
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
        <?php
            $isJournal = !empty($document['JournalVol']) && !empty($document['JournalNo']);
            $volNoDisplay = $isJournal ? "Vol." . htmlspecialchars($document['JournalVol']) . " No." . htmlspecialchars($document['JournalNo']) : "";
            $pageNumberDisplay = !empty($document['PageNumber']) && $document['PageNumber'] != 0 ? "pp." . htmlspecialchars($document['PageNumber']) : "";
        ?>
        <div id="content" class="content-wrapper">
            <div class="container-fluid">
                <h2 class="mt-3">檢視文獻</h2>
                <p>文獻代號：<?php echo htmlspecialchars($document['DocumentCode']); ?></p>
                <p>文獻分類：<?php echo htmlspecialchars($document['CategoryName']); ?></p>
                <p>作 者：<?php echo htmlspecialchars($document['Authors']); ?></p>
                <p>論文名稱：<?php echo htmlspecialchars($document['Title']); ?></p>
                <p>論文出處：<i><?php echo htmlspecialchars($document['PublicationSource']); ?></i></p>
                <?php if ($isJournal): ?>
                    <p>期刊卷號和期號：<?php echo $volNoDisplay; ?></p>
                <?php endif; ?>
                <?php if ($pageNumberDisplay): ?>
                    <p>頁 數：<?php echo $pageNumberDisplay; ?></p>
                <?php endif; ?>
                <p>出版時間：<?php echo htmlspecialchars($document['PublicationYear']); ?></p>
                <p>文獻描述：<?php echo htmlspecialchars($document['DocumentDescription']); ?></p>
                <p>論文檔案：
                    <?php if (!empty($document['File'])): ?>
                        <a href="<?php echo htmlspecialchars($document['File']); ?>"><?php echo htmlspecialchars(basename($document['File'])); ?></a>
                    <?php endif; ?>
                </p>
                <p>是否公開：<?php echo $document['IsPublic'] ? '是' : '否'; ?></p>
                <p>上傳者：<?php echo htmlspecialchars($document['UserName']); ?></p>
                <?php if ($userCanEdit): ?>
                    <a href="editDocument.php?documentID=<?php echo $documentID; ?>" class="btn btn-primary">編輯</a>
                    <a href="deleteDocument.php?documentID=<?php echo $documentID; ?>" class="btn btn-danger" onclick="return confirm('確定要刪除這篇文獻嗎？');">刪除</a>
                <?php endif; ?>
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

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "dbconfig.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["documentID"])) {
    $documentID = $_GET["documentID"];
    $userID = $_SESSION['UserID'];

    // 確認文獻是否屬於當前使用者
    $query = "SELECT * FROM Documents WHERE DocumentID = ? AND UserID = ?";
    if ($stmt = mysqli_prepare($link, $query)) {
        mysqli_stmt_bind_param($stmt, "ii", $documentID, $userID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            // 刪除所有 userdocuments 表中相關的記錄
            $deleteUserDocuments = "DELETE FROM userdocuments WHERE DocumentID = ?";
            if ($deleteStmt = mysqli_prepare($link, $deleteUserDocuments)) {
                mysqli_stmt_bind_param($deleteStmt, "i", $documentID);
                mysqli_stmt_execute($deleteStmt);
                mysqli_stmt_close($deleteStmt);

                // 繼續刪除 Documents 表中的記錄
                $deleteQuery = "DELETE FROM Documents WHERE DocumentID = ? AND UserID = ?";
                if ($deleteStmt = mysqli_prepare($link, $deleteQuery)) {
                    mysqli_stmt_bind_param($deleteStmt, "ii", $documentID, $userID);
                    mysqli_stmt_execute($deleteStmt);

                    if (mysqli_stmt_affected_rows($deleteStmt) > 0) {
                        $success = "文獻已成功刪除。";
                    } else {
                        $error = "刪除文獻失敗。";
                    }
                    mysqli_stmt_close($deleteStmt);
                }
            }
        } else {
            $error = "找不到指定的文獻或無權刪除。";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "資料庫查詢失敗。";
    }
    mysqli_close($link);

    // 根據操作結果，重新導向或顯示錯誤資訊
    if (empty($error)) {
        // Save success message in session to show on the redirected page
        session_start();
        $_SESSION['success_message'] = $success;
        header("Location: successPage.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>刪除文獻 - 文獻管理系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="wrapper">
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
                    <a class="nav-link text-white" href="/myDocuments.php"><i class="bi bi-collection-fill"></i> 我的文獻</a>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Function to show/hide fields based on the publication type
        function handlePublicationTypeChange() {
            var publicationType = document.getElementById('publicationType').value;
            var journalFields = document.getElementById('journalFields');
            var conferenceFields = document.getElementById('conferenceFields');
            var journalName = document.getElementById('journalName');
            var journalVol = document.getElementById('journalVol');
            var journalNo = document.getElementById('journalNo');
            var conferenceName = document.getElementById('conferenceName');

            journalFields.style.display = 'none';
            conferenceFields.style.display = 'none';
            journalName.required = false;
            journalVol.required = false;
            journalNo.required = false;
            conferenceName.required = false;

            if (publicationType === 'journal') {
                journalFields.style.display = 'block';
                journalName.required = true;
                journalVol.required = true;
                journalNo.required = true;
            } else if (publicationType === 'conference') {
                conferenceFields.style.display = 'block';
                conferenceName.required = true;
                // 清空期刊相關欄位
                journalVol.value = '';
                journalNo.value = '';
            }
        }

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
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
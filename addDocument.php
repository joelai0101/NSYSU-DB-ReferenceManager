<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "dbconfig.php"; // This assumes you have a dbconfig.php file to handle the database connection

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
// Variables for error messages and success messages
$error = $success = "";

// Function to fetch categories
function fetchCategories($link, $userID) {
    $categories = '<option value="">未指定分類</option>'; // Default option
    $sql = "SELECT CategoryID, CategoryName FROM Categories WHERE UserID = ? OR IsPublic = 1";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $userID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $categoryID, $categoryName);
        while (mysqli_stmt_fetch($stmt)) {
            $categories .= '<option value="' . $categoryID . '">' . htmlspecialchars($categoryName) . '</option>';
        }
        mysqli_stmt_close($stmt);
    } else {
        $categories .= '<option value="">無可用分類</option>'; // No categories found
    }
    return $categories;
}

function generateDocumentCode() {
    return uniqid('doc_');
}

$categoryOptions = fetchCategories($link, $_SESSION['UserID']); // Assume a 'UserID' is stored in session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Extracting form data
    // 檢查使用者是否已經提供了文獻代號
    $documentCode = !empty($_POST["documentCode"]) ? trim($_POST["documentCode"]) : generateDocumentCode();
    $title = trim($_POST["title"]);
    $authors = trim($_POST["authors"]);
    $publicationType = $_POST["publicationType"];
    $publicationSource = $publicationType == 'journal' ? trim($_POST["journalName"]) : trim($_POST["conferenceName"]);
    $journalVol = trim($_POST["journalVol"]);
    $journalNo = trim($_POST["journalNo"]);
    $pageNumber = trim($_POST["pageNumber"]);
    $publicationYear = trim($_POST["publicationYear"]);
    $documentDescription = trim($_POST["documentDescription"]);
    $isPublic = isset($_POST["isPublic"]) ? 1 : 0;
    $categoryID = !empty($_POST["categoryID"]) ? $_POST["categoryID"] : null;  // 使用 null 作為預設值
    $userID = $_SESSION['UserID']; // 獲取登入使用者的 ID

    // File upload handling
    if (isset($_FILES["documentFile"]) && $_FILES["documentFile"]["error"] == 0) {
        $targetDirectory = "./uploads/";
        $documentFile = $targetDirectory . basename($_FILES["documentFile"]["name"]);
        if (!move_uploaded_file($_FILES["documentFile"]["tmp_name"], $documentFile)) {
            $error = "上傳失敗，代碼：" . $_FILES["documentFile"]["error"];
        }
    } else {
        $documentFile = "";
        if ($_FILES["documentFile"]["error"] !== UPLOAD_ERR_OK) {
            // Output an error message based on the error code
            switch ($_FILES["documentFile"]["error"]) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "檔案大小超過限制。";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "沒有檔案被上傳。";
                    break;
                default:
                    $error = "上傳錯誤，代碼：" . $_FILES["documentFile"]["error"];
            }
        }
    }

    // 如果 categoryID 是空字串，將其轉換為 null
    if (empty($categoryID)) {
        $categoryID = null;
    }

    // 確認頁碼格式
    if (!empty($pageNumber) && !preg_match('/^\d+\s*-\s*\d+$/', $pageNumber)) {
        $error = "頁碼格式不正確。請輸入如 '46-50' 的範圍格式。";
    }

    // 資料庫插入程式碼
    // Validate required fields
    if (empty($title) || empty($authors) || empty($publicationSource)) {
        $error = "標題、作者和論文出處為必填項。";
    }
    if (empty($error)) {
        $sql = "INSERT INTO Documents (DocumentCode, Title, Authors, PublicationSource, JournalVol, JournalNo, PageNumber, PublicationYear, DocumentDescription, File, IsPublic, CategoryID, UserID) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssssssiis", $documentCode, $title, $authors, $publicationSource, $journalVol, $journalNo, $pageNumber, $publicationYear, $documentDescription, $documentFile, $isPublic, $categoryID, $userID);
            if (mysqli_stmt_execute($stmt)) {
                // Save success message in session to show on the redirected page
                session_start();
                $_SESSION['success_message'] = "文獻已成功新增。";
                
                // Redirect to a new page or back to the form page
                header("Location: successPage.php");
                exit;  // Important to prevent further execution of the script
            } else {
                $error = "發生錯誤，請再試一次。";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增文獻 - 文獻管理系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

                        <!-- Form to add a new document -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">新增文獻</h3>
                            </div>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                                <div class="card-body">
                                    <!-- Form fields for document details -->
                                    <div class="form-group">
                                        <label for="documentCode">自訂文獻代號 (如果留空，將自動生成)</label>
                                        <input type="text" class="form-control" id="documentCode" name="documentCode" placeholder="可選，留空以自動生成">
                                    </div>
                                    <div class="form-group">
                                        <label for="title">論文標題</label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="authors">作者</label>
                                        <input type="text" class="form-control" id="authors" name="authors" placeholder="多位作者請用英文逗號分隔" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="publicationType">出版類型</label>
                                        <select class="form-control" id="publicationType" name="publicationType" onchange="handlePublicationTypeChange()">
                                            <option value="">請選擇...</option>
                                            <option value="journal">期刊論文</option>
                                            <option value="conference">會議論文</option>
                                        </select>
                                    </div>
                                    <!-- Journal-specific fields -->
                                    <div id="journalFields" style="display: none;">
                                        <div class="form-group">
                                            <label for="journalName">期刊名稱</label>
                                            <input type="text" class="form-control" id="journalName" name="journalName">
                                        </div>
                                        <div class="form-group">
                                            <label for="journalVol">期刊卷號</label>
                                            <input type="text" class="form-control" id="journalVol" name="journalVol">
                                        </div>
                                        <div class="form-group">
                                            <label for="journalNo">期刊號</label>
                                            <input type="text" class="form-control" id="journalNo" name="journalNo">
                                        </div>
                                    </div>
                                    <!-- Conference-specific fields -->
                                    <div id="conferenceFields" style="display: none;">
                                        <div class="form-group">
                                            <label for="conferenceName">會議名稱</label>
                                            <input type="text" class="form-control" id="conferenceName" name="conferenceName">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="pageNumber">頁碼</label>
                                        <input type="text" class="form-control" id="pageNumber" name="pageNumber">
                                    </div>
                                    <div class="form-group">
                                        <label for="publicationYear">出版年份</label>
                                        <input type="number" class="form-control" id="publicationYear" name="publicationYear" placeholder="例如: 2022">
                                    </div>
                                    <div class="form-group">
                                        <label for="documentDescription">文獻描述</label>
                                        <textarea class="form-control" id="documentDescription" name="documentDescription" placeholder="文獻描述" rows="4"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="documentFile">論文檔案</label>
                                        <input type="file" class="form-control" id="documentFile" name="documentFile">
                                    </div>
                                    <div class="form-group">
                                        <label for="isPublic">是否公開</label>
                                        <input type="checkbox" id="isPublic" name="isPublic" value="1">
                                    </div>
                                    <div class="form-group">
                                        <label for="categoryID">文獻分類</label>
                                        <select class="form-control" id="categoryID" name="categoryID">
                                            <?php echo $categoryOptions; ?>
                                            <!-- Category options should be dynamically generated -->
                                        </select>
                                        <a href="addCategory.php" class="btn btn-link">新增分類</a> <!-- Link to add new categories -->
                                    </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">提交</button>
                                </div>
                            </form>
                        </div>
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

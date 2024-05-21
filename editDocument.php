<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "dbconfig.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$documentID = $_POST['documentID'] ?? $_GET['documentID'] ?? null;
$documentDetails = [];

if ($documentID) {
    $query = "SELECT * FROM Documents WHERE DocumentID = ?";
    if ($stmt = mysqli_prepare($link, $query)) {
        mysqli_stmt_bind_param($stmt, "i", $documentID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $documentDetails = mysqli_fetch_assoc($result); // documentDetails 的屬性是大寫
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
    }
}

// Check if the journal volume and number are set, otherwise assume 'conference'
$publicationType = (!empty($documentDetails['JournalVol']) && !empty($documentDetails['JournalNo'])) ? "journal" : "conference";

// Function to fetch categories
function fetchCategories($link, $userID, $documentDetails) {
    $categories = '<option value="">未指定分類</option>'; // 默認選項，用於未指定分類的情況
    $selectedCategory = ''; // 用於儲存選中的分類選項
    $sql = "SELECT CategoryID, CategoryName FROM Categories WHERE UserID = ? OR IsPublic = 1";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $userID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $categoryID, $categoryName);
        while (mysqli_stmt_fetch($stmt)) {
            if ($categoryID == ($documentDetails['CategoryID'] ?? null)) {
                $selected = 'selected';
                // 將當前選中的分類作為選項列表的第一項
                $selectedCategory = "<option value='$categoryID' $selected>" . htmlspecialchars($categoryName) . "</option>";
            } else {
                $categories .= "<option value='$categoryID'>" . htmlspecialchars($categoryName) . "</option>";
            }
        }
        mysqli_stmt_close($stmt);
    }
    // 確保選中的分類顯示在首位
    return $selectedCategory . $categories;
}

function generateDocumentCode() {
    return uniqid('doc_');
}

$categoryOptions = fetchCategories($link, $_SESSION['UserID'], $documentDetails);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $documentID = $_POST['documentID'] ?? $_GET['documentID'];
    $documentCode = !empty($_POST["documentCode"]) ? trim($_POST["documentCode"]) : generateDocumentCode();
    $title = trim($_POST["title"]);
    $authors = trim($_POST["authors"]);
    $publicationType = $_POST["publicationType"];
    $publicationSource = $publicationType == 'journal' ? trim($_POST["journalName"]) : trim($_POST["conferenceName"]);
    $journalVol = trim($_POST["journalVol"]);
    $journalNo = trim($_POST["journalNo"]);
    $pageNumber = trim($_POST["pageNumber"]);
    $publicationYear = trim($_POST["publicationYear"]);
    $documentDescription = trim($_POST["documentDescription"]) ?: '';
    $isPublic = isset($_POST["isPublic"]) ? 1 : 0;
    $categoryID = $_POST["categoryID"] ?? null;  // 使用 null 作為預設值
    $userID = $_SESSION['UserID']; // 獲取登入使用者的 ID

    // Debug output to check the values
    // echo "Document ID: $documentID<br>";
    // echo "Document Code: $documentCode<br>";
    // echo "Title: $title<br>";
    // echo "Authors: $authors<br>";
    // echo "Publication Type: $publicationType<br>";
    // echo "Publication Source: $publicationSource<br>";
    // echo "Journal Volume: $journalVol<br>";
    // echo "Journal Number: $journalNo<br>";
    // echo "Page Number: $pageNumber<br>";
    // echo "Publication Year: $publicationYear<br>";
    // echo "Document Description: $documentDescription<br>";
    // echo "Is Public: $isPublic<br>";
    // echo "Category ID: $categoryID<br>";
    // echo "User ID: $userID<br>";

    // File upload handling
    if (isset($_FILES["documentFile"]) && $_FILES["documentFile"]["error"] == 0) {
        $targetDirectory = "./uploads/";
        $documentFile = $targetDirectory . basename($_FILES["documentFile"]["name"]);
        if (!move_uploaded_file($_FILES["documentFile"]["tmp_name"], $documentFile)) {
            $error = "上傳失敗，程式碼：" . $_FILES["documentFile"]["error"];
        }
    } else {
        $documentFile = $documentDetails['File'] ?? "";  // 如果沒有新檔案上傳，則使用資料庫中的現有檔案路徑
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

    $error = '';
    if (empty($title) || empty($authors) || empty($publicationSource)) {
        $error = "標題、作者和論文出處為必填項。";
    } else {
        $sql = "UPDATE Documents SET DocumentCode = ?, Title = ?, Authors = ?, PublicationSource = ?, JournalVol = ?, JournalNo = ?, PageNumber = ?, PublicationYear = ?, DocumentDescription = ?, File = ?, IsPublic = ?, CategoryID = ? WHERE DocumentID = ? AND UserID = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssssssiisi", $documentCode, $title, $authors, $publicationSource, $journalVol, $journalNo, $pageNumber, $publicationYear, $documentDescription, $documentFile, $isPublic, $categoryID, $documentID, $userID);
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $_SESSION['success_message'] = "文獻已成功更新。";
                    header("Location: successPage.php");
                    exit;
                } else {
                    $error = "沒有資料被更新，請檢查輸入資料。";
                }
            } else {
                $error = "更新失敗，錯誤：". mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "準備語句失敗，錯誤：". mysqli_error($link);
        }
    }
    mysqli_close($link);

    // Output error if exists
    // if (!empty($error)) {
    //     echo "<p>Error: $error</p>";
    // }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯文獻 - 文獻管理系統</title>
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

                        <!-- Form to edit a document -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">編輯文獻</h3>
                            </div>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?documentID=<?php echo $documentID; ?>" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="documentID" value="<?php echo htmlspecialchars($documentID); ?>">
                                <div class="card-body">
                                    <!-- Form fields for document details -->
                                    <div class="form-group">
                                        <label for="documentCode">文獻代號</label>
                                        <input type="text" class="form-control" id="documentCode" name="documentCode" placeholder="自訂文獻代號" value="<?php echo htmlspecialchars($documentDetails['DocumentCode'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="title">論文標題</label>
                                        <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($documentDetails['Title'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="authors">作者</label>
                                        <input type="text" class="form-control" id="authors" name="authors" placeholder="多位作者請用英文逗號分隔" required value="<?php echo htmlspecialchars($documentDetails['Authors'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="publicationType">出版類型</label>
                                        <select class="form-control" id="publicationType" name="publicationType" onchange="handlePublicationTypeChange()">
                                            <option value="journal" <?php echo ($publicationType == 'journal' ? 'selected' : ''); ?>>期刊論文</option>
                                            <option value="conference" <?php echo ($publicationType == 'conference' ? 'selected' : ''); ?>>會議論文</option>
                                        </select>
                                    </div>
                                    <!-- Journal-specific fields -->
                                    <div id="journalFields" style="<?php echo ($publicationType == 'journal' ? '' : 'display: none;'); ?>">
                                        <div class="form-group">
                                            <label for="journalName">期刊名稱</label>
                                            <input type="text" class="form-control" id="journalName" name="journalName" value="<?php echo htmlspecialchars($documentDetails['PublicationSource'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="journalVol">卷號</label>
                                            <input type="text" class="form-control" id="journalVol" name="journalVol" value="<?php echo htmlspecialchars($documentDetails['JournalVol'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="journalNo">期號</label>
                                            <input type="text" class="form-control" id="journalNo" name="journalNo" value="<?php echo htmlspecialchars($documentDetails['JournalNo'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <!-- Conference-specific fields -->
                                    <div id="conferenceFields" style="<?php echo ($publicationType == 'conference' ? '' : 'display: none;'); ?>">
                                        <div class="form-group">
                                            <label for="conferenceName">會議名稱</label>
                                            <input type="text" class="form-control" id="conferenceName" name="conferenceName" value="<?php echo htmlspecialchars($documentDetails['PublicationSource'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="pageNumber">頁碼</label>
                                        <input type="text" class="form-control" id="pageNumber" name="pageNumber" value="<?php echo htmlspecialchars($documentDetails['PageNumber'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="publicationYear">出版年份</label>
                                        <input type="number" class="form-control" id="publicationYear" name="publicationYear" placeholder="例如: 2022" value="<?php echo htmlspecialchars($documentDetails['PublicationYear'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="documentDescription">描述</label>
                                        <textarea class="form-control" id="documentDescription" name="documentDescription" placeholder="文獻描述" rows="4"><?php echo htmlspecialchars($documentDetails['DocumentDescription'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="documentFile">檔案</label>
                                        <input type="file" class="form-control" id="documentFile" name="documentFile">
                                        <?php if (!empty($documentDetails['File'])): ?>
                                            <p>當前檔案：<a href="<?php echo htmlspecialchars($documentDetails['File']); ?>"><?php echo htmlspecialchars(basename($documentDetails['File'])); ?></a></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label for="isPublic">是否公開</label>
                                        <input type="checkbox" id="isPublic" name="isPublic" value="1" <?php echo (!empty($documentDetails['IsPublic']) ? 'checked' : ''); ?>>
                                    </div>
                                    <div class="form-group">
                                        <label for="categoryID">分類</label>
                                        <select class="form-control" id="categoryID" name="categoryID">
                                            <?php
                                            echo $categoryOptions;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">更新</button>
                                        <a href="deleteDocument.php?documentID=<?php echo $documentID; ?>" class="btn btn-danger" onclick="return confirm('確定要刪除這篇文檔嗎？');">刪除</a>
                                    </div>
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

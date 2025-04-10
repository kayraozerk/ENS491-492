<?php
session_start();

// Include authentication middleware
require_once 'api/authMiddleware.php';

// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/database/dbConnection.php';

$user = $_SESSION['user'];

// ----------------------------------------------------------------
// API SECTION: Process AJAX POST requests for template update
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['templateID']) && isset($_POST['templateContent'])) {
    // Set the response header for JSON
    header('Content-Type: application/json; charset=utf-8');
    
    // Clear any buffered output
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Retrieve POST data and trim if necessary
    $templateID      = trim($_POST['templateID']);
    $templateContent = $_POST['templateContent'];

    // Basic input validation
    if (empty($templateID) || empty($templateContent)) {
        echo json_encode([
            'success' => false,
            'error'   => 'Missing templateID or templateContent.'
        ]);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE MailTemplate_Table
            SET TemplateBody = :content
            WHERE TemplateID = :templateID
            LIMIT 1
        ");
        $stmt->execute([
            ':content'    => $templateContent,
            ':templateID' => $templateID
        ]);
        
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error'   => $e->getMessage()
        ]);
        exit;
    }
}
// ----------------------------------------------------------------
// END API SECTION
// ----------------------------------------------------------------

// -------------------------
// BEGIN: Admin Access Check
// -------------------------
try {
    $adminQuery = "SELECT 1 
                     FROM Admin_Table 
                    WHERE AdminSuUsername = :username 
                      AND checkRole <> 'Removed'
                      AND Role IN ('IT_Admin', 'Admin')
                    LIMIT 1";
    $adminStmt = $pdo->prepare($adminQuery);
    $adminStmt->execute([':username' => $user]);
    
    if (!$adminStmt->fetch()) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Admin check failed: " . $e->getMessage());
}
// -------------------------
// END: Admin Access Check
// -------------------------

// Fetch mail templates with associated category details
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.CategoryID, 
            c.CategoryCode, 
            c.CategoryDescription, 
            m.TemplateID, 
            m.TemplateBody
        FROM Category_Table c
        JOIN MailTemplate_Table m ON c.CategoryID = m.CategoryID
        ORDER BY c.CategoryID ASC
    ");
    $stmt->execute();
    $mailTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching mail templates: " . $e->getMessage());
}

// -------------------------
// Fetch Mail Log Data
// -------------------------
try {
    $stmtMailLogs = $pdo->prepare("
        SELECT LogID, Sender, StudentEmail, StudentName, MailContent, SentTime
        FROM MailLog_Table
        ORDER BY SentTime DESC
    ");
    $stmtMailLogs->execute();
    $mailLogs = $stmtMailLogs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If fetching logs fails, we use an empty array.
    $mailLogs = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mail Templates</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap and Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
    <link href="assets/css/components.min.css" rel="stylesheet">
    <link href="assets/css/layout.min.css" rel="stylesheet">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables & Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    
    <style>
        body {
            background-color: #f9f9f9;
            overflow: auto;
            padding-top: 70px;
        }
        .title {
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
            color: black;
        }
        .table-container {
            margin: 20px auto;
            max-width: 90%;
        }
        /* Button styling for .btn-custom, .return-button, and our new view log button */
        .btn-custom,
        .return-button {
            background-color: #45748a !important;
            color: white !important;
            border: none !important;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            width: 200px;
            text-align: center;
            transition: 0.3s ease;
        }
        .btn-custom:hover,
        .return-button:hover {
            background-color: #365a6b !important;
        }
        /* Toolbar button styles for the custom editor */
        #editorToolbar button {
            margin-right: 5px;
        }
        /* Editor styling */
        #templateContentEditor {
            border: 1px solid #ced4da;
            min-height: 200px;
            padding: 10px;
            background-color: #fff;
        }
        /* Action Container for the bottom-right buttons */
        .action-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }
        /* Custom close button for the modal (red "×") */
        .close-modal-btn {
            color: red;
            background: none;
            border: none;
            font-size: 24px;
            font-weight: bold;
            line-height: 1;
            cursor: pointer;
        }
        .close-modal-btn:hover {
            color: darkred;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h2 class="title">Mail Templates for Categories</h2>
    <div class="table-container">
        <table id="mailTemplatesTable" class="table table-bordered table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>Category Code</th>
                    <th>Category Description</th>
                    <th>Mail Template</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mailTemplates as $template): ?>
                    <tr id="row-<?= $template['TemplateID'] ?>">
                        <td><?= htmlspecialchars($template['CategoryCode']) ?></td>
                        <td><?= htmlspecialchars($template['CategoryDescription']) ?></td>
                        <td class="template-content"><?= htmlspecialchars($template['TemplateBody']) ?></td>
                        <td>
                            <button type="button" class="btn btn-custom edit-template-btn" 
                                    data-templateid="<?= $template['TemplateID'] ?>" 
                                    data-templatebody="<?= htmlspecialchars($template['TemplateBody'], ENT_QUOTES) ?>">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Editing Template -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTemplateModalLabel">Edit Mail Template</h5>
        <!-- Custom red close button -->
        <button type="button" class="close-modal-btn" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editTemplateForm">
          <input type="hidden" name="templateID" id="templateID">
          <!-- Toolbar for formatting -->
          <div id="editorToolbar" class="mb-2">
              <button type="button" class="btn btn-sm btn-secondary" onclick="execCmd('bold')" title="Bold">
                <i class="fa fa-bold"></i>
              </button>
              <button type="button" class="btn btn-sm btn-secondary" onclick="execCmd('italic')" title="Italic">
                <i class="fa fa-italic"></i>
              </button>
              <button type="button" class="btn btn-sm btn-secondary" onclick="execCmd('underline')" title="Underline">
                <i class="fa fa-underline"></i>
              </button>
          </div>
          <!-- Contenteditable editor area -->
          <div id="templateContentEditor" contenteditable="true"></div>
        </form>
      </div>
      <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         <button type="button" id="saveTemplateBtn" class="btn btn-custom">Save changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Viewing Mail Log -->
<div class="modal fade" id="mailLogModal" tabindex="-1" aria-labelledby="mailLogModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mailLogModalLabel">Mail Log</h5>
        <button type="button" class="close-modal-btn" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <table id="mailLogTable" class="table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Sender</th>
                    <th>Student Email</th>
                    <th>Student Name</th>
                    <th>Mail Content</th>
                    <th>Sent Time</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded via DataTable -->
            </tbody>
        </table>
      </div>
      <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Action Container: Buttons for Return and View Mail Log -->
<div class="action-container">
    <button class="return-button" onclick="window.location.href='adminDashboard.php'">
        <i class="fa fa-arrow-left"></i> Return to Admin Dashboard
    </button>
    <button class="btn btn-custom" id="viewMailLogBtn">
        <i class="fa fa-eye"></i> View Mail Log
    </button>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables & Buttons -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
// Basic editor command function
function execCmd(command) {
    document.execCommand(command, false, null);
}

$(document).ready(function() {
    // Initialize DataTable for Mail Templates
    var table = $('#mailTemplatesTable').DataTable({
        dom: '<"datatable-header d-flex justify-content-between align-items-center mb-2"fB>t<"datatable-footer"ip>',
        buttons: [
            {
                extend: 'excelHtml5',
                title: 'Mail Templates',
                text: 'Export to Excel',
                className: 'btn btn-custom'
            }
        ],
        pageLength: 10
    });

    // Edit Template Button Click: load template into editor modal
    $('.edit-template-btn').on('click', function() {
        var templateID = $(this).data('templateid');
        var templateBody = $(this).data('templatebody');

        $('#templateID').val(templateID);
        $('#templateContentEditor').html(templateBody);

        var modalEl = new bootstrap.Modal(document.getElementById('editTemplateModal'));
        modalEl.show();
    });

    // Save changes button in template editor modal
    $('#saveTemplateBtn').on('click', function() {
        var templateID = $('#templateID').val();
        var updatedContent = $('#templateContentEditor').html();

        $.ajax({
            url: 'mailPage.php', // API call to same file for update
            type: 'POST',
            dataType: 'json',
            data: { templateID: templateID, templateContent: updatedContent },
            success: function(data) {
                if (data.success) {
                    $('#row-' + templateID).find('.template-content').html(updatedContent);
                    alert("Template updated successfully!");
                    bootstrap.Modal.getInstance(document.getElementById('editTemplateModal')).hide();
                } else {
                    alert("Error updating the template: " + (data.error || "Unknown error."));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert("There was an error updating the template.");
            }
        });
    });

    // When "View Mail Log" button is clicked, open mail log modal and initialize DataTable
    $('#viewMailLogBtn').on('click', function() {
        // Initialize DataTable for Mail Log with pre-fetched data from PHP (JSON encoded)
        // We output the PHP variable as JSON in a JS variable
        var mailLogs = <?php echo json_encode($mailLogs); ?>;
        // Check if DataTable is already initialized. If so, destroy and reinitialize.
        if ($.fn.DataTable.isDataTable('#mailLogTable')) {
            $('#mailLogTable').DataTable().clear().destroy();
        }
        $('#mailLogTable').DataTable({
            data: mailLogs,
            columns: [
                { title: "Log ID", data: "LogID" },
                { title: "Sender", data: "Sender" },
                { title: "Student Email", data: "StudentEmail" },
                { title: "Student Name", data: "StudentName" },
                { title: "Mail Content", data: "MailContent" },
                { title: "Sent Time", data: "SentTime" }
            ],
            dom: '<"datatable-header d-flex justify-content-between align-items-center mb-2"fB>t<"datatable-footer"ip>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    title: 'Mail Log',
                    text: 'Export to Excel',
                    className: 'btn btn-custom'
                }
            ],
            pageLength: 10
        });

        // Show the modal
        var logModal = new bootstrap.Modal(document.getElementById('mailLogModal'));
        logModal.show();
    });
});
</script>
</body>
</html>

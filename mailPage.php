<?php
session_start();
require_once 'api/authMiddleware.php';
require_once __DIR__ . '/database/dbConnection.php';

// -------------
//  API: Update a template via AJAX
// -------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['templateID'], $_POST['MailType'], $_POST['MailHeader'], $_POST['MailBody'])
    && isset($_GET['action']) && $_GET['action']==='saveTemplate'
) {
    header('Content-Type: application/json; charset=utf-8');
    $id     = (int)$_POST['templateID'];
    $type   = trim($_POST['MailType']);
    $header = trim($_POST['MailHeader']);
    $body   = $_POST['MailBody'];

    if (!$id || $type==='' || $header==='') {
        echo json_encode(['success'=>false,'error'=>'Missing fields.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("
          UPDATE MailTemplate_Table
             SET MailType   = :type,
                 MailHeader = :hdr,
                 MailBody   = :body
           WHERE TemplateID = :id
        ");
        $stmt->execute([
            ':type' => $type,
            ':hdr'  => $header,
            ':body' => $body,
            ':id'   => $id
        ]);
        echo json_encode(['success'=>true]);
    } catch(PDOException $e){
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// -------------
//  Access control
// -------------
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];
try {
    $adm = $pdo->prepare("
      SELECT 1 
        FROM Admin_Table 
       WHERE AdminSuUsername = :u 
         AND checkRole <> 'Removed' 
         AND Role IN ('IT_Admin','Admin')
       LIMIT 1
    ");
    $adm->execute([':u'=>$user]);
    if (!$adm->fetch()) {
        header("Location: index.php");
        exit;
    }
} catch(PDOException $e){
    die("Admin check failed: ".$e->getMessage());
}

// -------------
//  Fetch templates
// -------------
$mailTemplates = $pdo
  ->query("SELECT TemplateID, MailType, MailHeader, MailBody FROM MailTemplate_Table ORDER BY TemplateID")
  ->fetchAll(PDO::FETCH_ASSOC);

// -------------
//  Fetch mail‐log joined to template
// -------------
$mailLogs = $pdo
  ->query(<<<'SQL'
    SELECT 
      l.LogID,
      l.Sender,
      l.StudentEmail,
      l.StudentName,
      t.MailType,
      t.MailHeader,
      l.MailContent AS MailBody,
      l.SentTime
    FROM MailLog_Table AS l
    LEFT JOIN MailTemplate_Table AS t 
      ON l.TemplateID = t.TemplateID
    ORDER BY l.SentTime DESC
  SQL
  )
  ->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mail Templates &amp; Log</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="assets/css/bootstrap.min.css"           rel="stylesheet">
  <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
  <link href="assets/css/components.min.css"          rel="stylesheet">
  <link href="assets/css/layout.min.css"              rel="stylesheet">
  <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" rel="stylesheet">
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

  <style>
    body { background:#f9f9f9; padding-top:70px; overflow:auto;}
    .container { max-width:90%; margin:auto;}
    .title { text-align:center; font-size:1.5rem; margin-bottom:1rem;}
    .btn-custom {
      background:#45748a!important; color:#fff!important; border:none!important;
      padding:.5rem 1rem; border-radius:4px; cursor:pointer;
    }
    .btn-custom:hover { background:#365a6b!important; }
    .action-container {
      position:fixed; bottom:20px; right:20px; display:flex; flex-direction:column; gap:8px;
    }
    .close-modal-btn {
      color:red; background:none; border:none; font-size:1.5rem; line-height:1; cursor:pointer;
    }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
  <h2 class="title">Mail Templates</h2>
  <table id="templatesTable" class="table table-striped">
    <thead>
      <tr>
        <th>Type</th>
        <th>Header</th>
        <th>Body</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($mailTemplates as $t): ?>
      <tr data-id="<?= $t['TemplateID'] ?>"
          data-type="<?= htmlspecialchars($t['MailType'],ENT_QUOTES) ?>"
          data-header="<?= htmlspecialchars($t['MailHeader'],ENT_QUOTES) ?>"
          data-body="<?= htmlspecialchars($t['MailBody'],ENT_QUOTES) ?>">
        <td><?= htmlspecialchars($t['MailType']) ?></td>
        <td><?= htmlspecialchars($t['MailHeader']) ?></td>
        <td class="body-cell"><?= htmlspecialchars(substr($t['MailBody'],0,60)) ?>…</td>
        <td>
          <button class="btn btn-custom edit-btn">
            <i class="fa fa-edit"></i> Edit
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Mail Template</h5>
        <button type="button" class="close-modal-btn" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editForm">
          <input type="hidden" id="TemplateID" name="templateID">
          <div class="mb-3">
            <label for="MailType" class="form-label">Mail Type</label>
            <input type="text" id="MailType" name="MailType" class="form-control">
          </div>
          <div class="mb-3">
            <label for="MailHeader" class="form-label">Mail Header</label>
            <input type="text" id="MailHeader" name="MailHeader" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Mail Body</label>
            <div id="MailBodyEditor" style="height:200px; background:#fff;"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="saveBtn" class="btn btn-custom">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Action Buttons -->
<div class="action-container">
  <button class="btn btn-custom" id="viewLogBtn">
    <i class="fa fa-list"></i> View Mail Log
  </button>
  <button class="btn btn-custom" onclick="location.href='adminDashboard.php'">
    <i class="fa fa-arrow-left"></i> Return to Dashboard
  </button>
</div>

<!-- Mail Log Modal -->
<div class="modal fade" id="logModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Mail Log</h5>
        <button class="close-modal-btn" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <table id="logTable" class="table table-striped" style="width:100%">
          <thead>
            <tr>
              <th>LogID</th>
              <th>Sender</th>
              <th>StudentEmail</th>
              <th>StudentName</th>
              <th>MailType</th>
              <th>MailHeader</th>
              <th>MailBody</th>
              <th>SentTime</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
let quill = new Quill('#MailBodyEditor',{ theme:'snow' });

// Templates grid
$('#templatesTable').DataTable({
  dom:'Bfrtip',
  buttons:[{
    extend:'excelHtml5',
    title:'MailTemplates',
    className:'btn btn-custom'
  }]
});

// When “Edit” clicked
$('.edit-btn').on('click',function(){
  let $tr = $(this).closest('tr');
  $('#TemplateID').val( $tr.data('id') );
  $('#MailType').val( $tr.data('type') );
  $('#MailHeader').val( $tr.data('header') );
  quill.root.innerHTML = $tr.data('body');
  new bootstrap.Modal($('#editModal')).show();
});

// Save AJAX
$('#saveBtn').on('click',()=>{
  let data = {
    templateID: $('#TemplateID').val(),
    MailType:   $('#MailType').val(),
    MailHeader: $('#MailHeader').val(),
    MailBody:   quill.root.innerHTML
  };
  $.post('?action=saveTemplate', data, function(res){
    if(res.success){
      alert('Saved!');
      location.reload();
    } else {
      alert('Error: '+res.error);
    }
  },'json');
});

// Mail Log
const logs = <?= json_encode($mailLogs) ?>;
$('#viewLogBtn').on('click',()=>{
  let dt = $('#logTable').DataTable({
    data: logs,
    destroy:true,
    dom:'Bfrtip',
    buttons:[{
      extend:'excelHtml5',
      title:'MailLog',
      className:'btn btn-custom'
    }],
    columns:[
      {data:'LogID'},
      {data:'Sender'},
      {data:'StudentEmail'},
      {data:'StudentName'},
      {data:'MailType'},
      {data:'MailHeader'},
      {data:'MailBody'},
      {data:'SentTime'}
    ]
  });
  new bootstrap.Modal($('#logModal')).show();
});
</script>
</body>
</html>

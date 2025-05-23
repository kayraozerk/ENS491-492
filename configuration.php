<?php
 require_once 'api/authMiddleware.php';  
 require_once 'database/dbConnection.php';
 require_once 'api/commonFunc.php';

 init_session();

 if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidateID'], $_POST['action'])) {
  handleCandidateExclusion($pdo); 
 }
 $username = $_SESSION['user'];  // Current user
 $user = $_SESSION['user'];


 try {
  $currentYear = fetchCurrentAcademicYear($pdo);
  $currentYearID = $currentYear['YearID'];
  $currentAcademicYear = $currentYear['Academic_year'];
  $startDate = $currentYear['Start_date_time'];
  $endDate = $currentYear['End_date_time'];

  $candidates = getCandidatesForYear($pdo, $currentYearID);
  $students = getStudentsForYear($pdo, $currentYearID);

  //admin access check, if not valid, user will be logged as unauthorized log in
  if (!checkIfUserIsAdmin($pdo, $user)) {
    logUnauthorizedAccess($pdo, $user, basename(__FILE__));
    header("Location: accessDenied.php");
    exit();
  }


} catch (Exception $e) {
  die("Error: " . $e->getMessage());
}

 
 ?>
 <script>
 // Pass PHP data directly to JavaScript
 let allCandidates = <?php echo json_encode($candidates); ?>;
 let currentAcademicYear = <?php echo json_encode($currentAcademicYear); ?>;
 let allStudents = <?php echo json_encode($students); ?>;
 </script>
 
 <!DOCTYPE html>
 <html lang="en">
 <head>
   <meta charset="UTF-8">
   <title>Teaching Awards - Excluded Candidates</title>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
 
   <!-- Bootstrap CSS (CDN) -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
 
   <!-- Optional CSS -->
   <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
   <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
   <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
   <link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
   <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
 
   <!-- FontAwesome (CDN) -->
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
 
   <!-- jQuery (CDN) -->
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <!-- Bootstrap Bundle (CDN) -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 
   <!-- DataTables + Buttons CSS -->
   <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
   <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
 
 
   <style>
     /* Make the entire page scrollable */
 
     body, .nav-tabs .nav-link, .table, .table th, .table td {
       font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
       font-size: 14px;
     }

     .btn-secondary {
      background: #45748a !important;
      color: #fff !important;
      border: none !important;
      padding: 10px 20px !important;
      font-size: 14px;
      border-radius: 5px;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .btn-secondary:hover {
      background: #365a6b !important;
    }

 
     html, body {
       margin: 0;
       padding: 0;
       height: 100%;
       overflow-y: auto;
     }
     body {
       background-color: #f9f9f9;
       padding-top: 20px; /* Adjust if you have a fixed navbar */
     }
     .card-header {
       background-color: #45748a;
       color: #fff;
     }
     .search-box {
       width: 100%;
       padding: 10px;
       max-width: 400px;
       margin-bottom: 15px;
     }
     .pagination {
       margin-top: 1rem;
     }
     .pagination .page-item .page-link {
       color: #45748a;
     }
     .pagination .page-item.active .page-link {
       background-color: #45748a;
       border-color: #45748a;
       color: #fff;
     }
     th.sortable {
       cursor: pointer;
       position: relative;
       user-select: none;
     }
     th.sortable.asc::after {
       content: ' \25B2'; /* Up arrow */
       position: absolute;
       right: 8px;
       color: #ccc;
     }
     th.sortable.desc::after {
       content: ' \25BC'; /* Down arrow */
       position: absolute;
       right: 8px;
       color: #ccc;
     }
 
     th.sortable::after {
       content: ' \25B2'; /* Default to up arrow */
       position: absolute;
       right: 8px;
       color: #ccc;
       opacity: 0.3; /* Make it light when not active */
     }
 
     /* When active (ascending) */
     th.sortable.asc::after {
       content: ' \25B2'; /* Up arrow */
       color: #000;
       opacity: 1;
     }
 
     /* When active (descending) */
     th.sortable.desc::after {
       content: ' \25BC'; /* Down arrow */
       color: #000;
       opacity: 1;
     }
 
     .sticky-sync-container {
       position: fixed;
       bottom: 40px;
       right: 30px;
       z-index: 1050;
       display: flex !important;
       flex-direction: column;
       align-items: flex-end;
       gap: 10px;
     }
 
 
     .container{
       max-width: 95%;
       width: 95%;
       margin-left: auto;
       margin-right: auto;
     }
 
     /* Make table use full width */
     .table {
       width: 100%;
       table-layout: fixed;
     }
 
     /* Enable horizontal scrolling for smaller screens */
     .table-responsive {
       width: 100%;
       overflow-x: auto;
     }
 
     /* Column width specifications */
     .table th:nth-child(1) { width: 15%; } /* Name */
     .table th:nth-child(2) { width: 25%; } /* Email */
     .table th:nth-child(3) { width: 10%; }  /* SuID */
     .table th:nth-child(4) { width: 8%; }  /* Role */
     .table th:nth-child(5) { width: 12%; } /* Categories */
     .table th:nth-child(6) { width: 22%; } /* Courses */
     .table th:nth-child(7) { width: 10%; } /* Last Synced */
     .table th:nth-child(8) { width: 8%; }  /* Status */
 
     /* Prevent text overflow in table cells */
     .table td {
       word-break: break-word;
       overflow: hidden;
       text-overflow: ellipsis;
     }
 
     /* Set minimum widths for critical columns */
     .table th:nth-child(1),
     .table td:nth-child(1) {
       min-width: 150px; /* Name */
     }
 
     .table th:nth-child(2),
     .table td:nth-child(2) {
       min-width: 180px; /* Email */
     }
 
     .table th:nth-child(4),
     .table td:nth-child(4) {
       white-space: nowrap;
       text-align: center;
     }
 
     .table th:nth-child(6),
     .table td:nth-child(6) {
       min-width: 200px; /* Courses */
     }
 
     /* Format date column */
     .table th:nth-child(7),
     .table td:nth-child(7) {
       white-space: nowrap;
     }
 
 
 
 </style>
 </head>
 <body>
   <!-- Example navbar (adjust path if needed) -->
   <?php $backLink = "adminDashboard.php"; include 'navbar.php'; ?>
 
 
   <div class="sticky-sync-container">
 
     <button id="viewLogsBtn" class="btn btn-secondary ms-2">
       <i class="fa-solid fa-file-alt"></i> View Logs
     </button>
     <button id="syncButton" class="btn ms-2" style="background-color: #ff9800; color: white;">
       <i class="fa-solid fa-sync fa-lg"></i>
       <span> Data Sync </span>
     </button>
   </div>
 
 
   <div class="container mt-4">
     <div class="card-body">
       <!-- Current Academic Year -->
       <?php if ($currentAcademicYear): ?>
           <?php 
           // +1 logic for current academic year
           $displayCurrentYear = $currentAcademicYear . '-' . ($currentAcademicYear + 1);
           ?>
           <div class="alert alert-success" style="margin-top: 15px;">
               <h5>Current Academic Year: <strong><?= $displayCurrentYear; ?></strong></h5>
           </div>
 
       <?php else: ?>
           <div class="alert alert-danger">No Academic Year Found</div>
       <?php endif; ?>
  
       <ul class="nav nav-tabs" id="configTabs" role="tablist">
         <li class="nav-item">
           <button class="nav-link active" id="candidates-tab" data-bs-toggle="tab" data-bs-target="#candidatesTab">Candidates</button>
         </li>
         <li class="nav-item">
           <button class="nav-link" id="students-tab" data-bs-toggle="tab" data-bs-target="#studentsTab">Students</button>
         </li>
       </ul>
 
 
       <div class="tab-content mt-3">
         <!-- CANDIDATES TAB -->
         <div class="tab-pane fade show active" id="candidatesTab">
           <div class="card-body">
             <!-- Search box -->
             <div class="d-flex justify-content-between align-items-center mb-3">
              <input type="text" id="searchBox" class="form-control search-box" placeholder="Search for a candidate..." style="max-width: 300px;">
              <div id="exportCandidatesButtonContainer" class="text-end mb-2"></div>
            </div>
             <div class="table-responsive mt-3">
               <table class="table table-striped table-bordered">
                 <thead>
                   <tr>
                     <th class="sortable" data-column="Name">Name</th>
                     <th class="sortable" data-column="Mail">Email</th>
                     <th class="sortable" data-column="SU_ID">SuID</th>
                     <th class="sortable" data-column="Role">Role</th>
                     <th class="sortable" data-column="Categories">Categories</th>
                     <th class="sortable" data-column="Courses">Courses</th>
                     <th class="sortable" data-column="Sync_Date">Last Synced</th>
                     <th class="sortable" data-column="Status">Status</th>
                   </tr>
                 </thead>
                 <tbody id="candidatesTable">
                   <?php foreach ($candidates as $candidate): ?>
                     <tr>
                       <td><?= htmlspecialchars($candidate['Name']) ?></td>
                       <td><?= htmlspecialchars($candidate['Mail']) ?></td>
                       <td><?= htmlspecialchars($candidate['SU_ID']) ?></td>
                       <td><?= htmlspecialchars($candidate['Role']) ?></td>
                       <td><?= htmlspecialchars($candidate['Categories'] ?: '-') ?></td>
                       <td><?= htmlspecialchars($candidate['Courses'] ?: '-') ?></td>
                       <td><?= htmlspecialchars($candidate['Sync_Date']) ?></td>
                       <td>
                         <button class="btn <?= ($candidate['Status'] === 'Etkin') ? 'btn-success' : 'btn-danger'; ?>">
                           <?= ($candidate['Status'] === 'Etkin') ? 'On' : 'Off'; ?>
                         </button>
                       </td>
                     </tr>
                   <?php endforeach; ?>
                 </tbody>
               </table>
             </div>

            <!-- Invisible Export Table -->
            <table id="candidatesExportTable" class="table table-bordered d-none">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>SuID</th>
                  <th>Role</th>
                  <th>Categories</th>
                  <th>Courses</th>
                  <th>Last Synced</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($candidates as $candidate): ?>
                  <tr>
                    <td><?= htmlspecialchars($candidate['Name']) ?></td>
                    <td><?= htmlspecialchars($candidate['Mail']) ?></td>
                    <td><?= htmlspecialchars($candidate['SU_ID']) ?></td>
                    <td><?= htmlspecialchars($candidate['Role']) ?></td>
                    <td><?= htmlspecialchars($candidate['Categories'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($candidate['Courses'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($candidate['Sync_Date']) ?></td>
                    <td><?= ($candidate['Status'] === 'Etkin') ? 'On' : 'Off'; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <!--  End Export Table -->
            <nav>
            <ul class="pagination" id="candidatePaginationControls"></ul>
            </nav>
          </div>
        </div>
 
         <!-- STUDENTS TAB -->
         <div class="tab-pane fade" id="studentsTab">
           <div class="card-body">
             <!-- Search box -->
             <div class="d-flex justify-content-between align-items-center mb-3">
               <input type="text" id="studentSearchBox" class="form-control search-box" placeholder="Search for a student..." style="max-width: 300px;">
               <div id="exportStudentButtonContainer" class="text-end mb-2"></div>

             </div>
             <div class="table-responsive">
             <table id="studentsExportTable" class="table table-striped table-bordered">
               <thead>
                 <tr>
                   <th class="sortable" data-column="StudentID">Student ID</th>
                   <th class="sortable" data-column="StudentFullName">Name</th>
                   <th class="sortable" data-column="Mail">Email</th>
                   <th class="sortable" data-column="SuNET_Username">Username</th>
                   <th class="sortable" data-column="CGPA">GPA</th>
                   <th class="sortable" data-column="Categories">Categories</th>
                 </tr>
               </thead>
 
                 <tbody>
                   <?php foreach ($students as $student): ?>
                     <tr>
                       <td><?= htmlspecialchars($student['StudentID']) ?></td>
                       <td>
                         <a href="impersonate.php?student_id=<?= $student['id'] ?>" class="text-decoration-none text-primary">
                           <?= htmlspecialchars($student['StudentFullName']) ?>
                         </a>
                       </td>
                       <td><?= htmlspecialchars($student['Mail']) ?></td>
                       <td><?= htmlspecialchars($student['SuNET_Username']) ?></td>
                       <td><?= isset($student['CGPA']) && $student['CGPA'] !== null ? htmlspecialchars($student['CGPA']) : '-' ?></td>
                     </tr>
                   <?php endforeach; ?>
                 </tbody>
           </table>
         </div>
         <!--  Invisible Export Table for Students -->
         <table id="studentsExportHiddenTable" class="table table-bordered d-none">
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Username</th>
              <th>GPA</th>
              <th>Categories</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $student): ?>
              <tr>
                <td><?= htmlspecialchars($student['StudentID']) ?></td>
                <td><?= htmlspecialchars($student['StudentFullName']) ?></td>
                <td><?= htmlspecialchars($student['Mail']) ?></td>
                <td><?= htmlspecialchars($student['SuNET_Username']) ?></td>
                <td><?= isset($student['CGPA']) && $student['CGPA'] !== null ? htmlspecialchars($student['CGPA']) : '-' ?></td>
                <td><?= htmlspecialchars($student['Categories'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <!-- End of Export Table -->

         <!-- Pagination controls -->
         <nav>
           <ul class="pagination" id="studentPaginationControls"></ul>
         </nav>
       </div>
     </div>
   </div>
 
   <script>
     let currentCandidatePage = 1;
     let currentStudentPage = 1;
     const rowsPerPage = 7;
 
     // Sort tracking
     let currentSortColumn = null;
     let currentSortDirection = 'asc';
 
    $(document).ready(function () {
      // Initialize invisible export table for candidates
      $('#studentsExportHiddenTable').DataTable({
        dom: '<"d-flex justify-content-end align-items-center mb-3"<"export-students-buttons"B>>ti',
        buttons: [
          {
            extend: 'excelHtml5',
            title: 'Students Table',
            text: 'Export Students to Excel',
            className: 'btn btn-secondary'
          }
        ],
        paging: false,
        searching: false,
        ordering: false,
        info: false,
        initComplete: function () {
          const exportButtons = $('.export-students-buttons').detach();
          $('#exportStudentButtonContainer').append(exportButtons);
        }
      });

      let candidateExportInitialized = false;

      $('#candidatesExportTable').DataTable({
        dom: '<"d-flex justify-content-end align-items-center mb-3"<"export-candidates-buttons"B>>ti',
        buttons: [
          {
            extend: 'excelHtml5',
            title: 'Candidates Table',
            text: 'Export Candidates to Excel',
            className: 'btn btn-secondary'
          }
        ],
        paging: false,
        searching: false,
        ordering: false,
        info: false,
        initComplete: function () {
          const exportButtons = $('.export-candidates-buttons').detach();
          $('#exportCandidatesButtonContainer').append(exportButtons);
        }
      });
      
    // Candidate table sorting
      $("#candidatesTab th.sortable").on("click", function () {
        const column = $(this).data("column");

        $("#candidatesTab th.sortable").removeClass("asc desc");

        if (currentSortColumn === column) {
          currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
          currentSortDirection = 'asc';
        }

        currentSortColumn = column;
        $(this).addClass(currentSortDirection);

        sortData(column);
      });
 
      let studentsTableInitialized = false;
 
       $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
         if (e.target.id === 'students-tab' && !studentsTableInitialized) {
           studentsTableInitialized = true;
         }
       });
 
 
       // Student table sorting
       $("#studentsTab th.sortable").on("click", function () {
         const column = $(this).data("column");
 
         $("#studentsTab th.sortable").removeClass("asc desc");
 
         if (studentSortColumn === column) {
           studentSortDirection = studentSortDirection === 'asc' ? 'desc' : 'asc';
         } else {
           studentSortDirection = 'asc';
         }
 
         studentSortColumn = column;
         $(this).addClass(studentSortDirection);
 
         sortStudentData(column);
       });
 
       // Reset sort icons when switching tabs
       $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
         $("th.sortable").removeClass("asc desc");
       });
 
       // Use allCandidates directly instead of loading via AJAX
       renderTable(allCandidates, 1);
       renderPaginationControls(allCandidates);
 
 
       // Student rendering
       renderStudentTable(allStudents, 1);
       renderStudentPaginationControls(allStudents);
 
 
       // Candidate search
       $("#searchBox").on("keyup", function() {
          const searchTerm = $(this).val().toLowerCase();
          const filteredCandidates = allCandidates.filter(candidate => {
            const nameMatch = candidate.Name && candidate.Name.toString().toLowerCase().includes(searchTerm);
            const emailMatch = candidate.Mail && candidate.Mail.toString().toLowerCase().includes(searchTerm);
            const idMatch = candidate.SU_ID && candidate.SU_ID.toString().toLowerCase().includes(searchTerm);
            const roleMatch = candidate.Role && candidate.Role.toString().toLowerCase().includes(searchTerm);
            const coursesMatch = candidate.Courses && candidate.Courses.toString().toLowerCase().includes(searchTerm);

          return nameMatch || emailMatch || idMatch || roleMatch || coursesMatch;
        });

        renderTable(filteredCandidates, 1);
        renderPaginationControls(filteredCandidates);
       });
 
       $("#studentSearchBox").on("keyup", function() {
         const searchTerm = $(this).val().toLowerCase();
         const filteredStudents = allStudents.filter(student => 
           student.StudentFullName.toLowerCase().includes(searchTerm) || 
           student.Mail.toLowerCase().includes(searchTerm) || 
           student.SuNET_Username.toLowerCase().includes(searchTerm) ||
           student.StudentID.toString().includes(searchTerm)
         );
         renderStudentTable(filteredStudents, 1);
         renderStudentPaginationControls(filteredStudents);
       });
     });
 
     // Update your renderTable function to work with the data structure from PHP
     function renderTable(dataArray, pageNum) {
         $("#candidatesTable").empty();
 
         if (!dataArray || dataArray.length === 0) {
             $("#candidatesTable").append('<tr><td colspan="7" class="text-center">No candidates found</td></tr>');
             return;
         }
 
         currentPage = pageNum;
         const startIndex = (pageNum - 1) * rowsPerPage;
         const endIndex = startIndex + rowsPerPage;
         const pageData = dataArray.slice(startIndex, endIndex);
 
 
         pageData.forEach(candidate => {
             const statusClass = candidate.Status === "Etkin" ? "btn-success" : "btn-danger";
             const statusText = candidate.Status === "Etkin" ? "On" : "Off";
             const categoryDisplay = candidate.Categories ||"-";
             const coursesDisplay = candidate.Courses ||"-";
 
             $("#candidatesTable").append(`
               <tr>
                 <td>${candidate.Name}</td>
                 <td>${candidate.Mail}</td>
                 <td>${candidate.SU_ID}</td>
                 <td>${candidate.Role}</td>
                 <td>${categoryDisplay}</td>
                 <td>${coursesDisplay}</td>
                 <td>${candidate.Sync_Date || ""}</td>
                 <td>
                   <button class="btn ${statusClass} toggle-status" data-id="${candidate.id}" data-status="${candidate.Status}">
                     ${statusText}
                   </button>
                 </td>
               </tr>
             `);
         });
 
         $(".toggle-status").on("click", function () {
           const button = $(this);
           const candidateID = button.data("id");           // Get candidate ID
           const currentStatus = button.data("status");     // Get current status 
 
           const newStatus = currentStatus === "Etkin" ? "İşten ayrıldı" : "Etkin";  // Toggle status
           const confirmationMessage = `Are you sure you want change the status to ${newStatus === "Etkin" ? "ON" : "OFF"} for this candidate?`;

          if (!confirm(confirmationMessage)) {
              return; // Stop if the user cancels
          }
           const newClass = newStatus === "Etkin" ? "btn-success" : "btn-danger";   // Change button color
           const newText = newStatus === "Etkin" ? "On" : "Off";                    // Change button text
 
           // Immediately update button UI
           button.removeClass("btn-success btn-danger").addClass(newClass).text(newText);
           button.data("status", newStatus);  // Update data-status
 
           // Send updated status to server
           $.ajax({
               url: "api/updateCandidateStatus.php",  // Your backend script
               method: "POST",
               data: { candidateID: candidateID, status: newStatus },
               dataType: "json",
               success: function (response) {
                   if (!response.success) {
                       alert("Error updating status: " + response.message);
 
                       // Revert UI if update fails
                       button.removeClass(newClass).addClass(currentStatus === "Etkin" ? "btn-success" : "btn-danger");
                       button.text(currentStatus === "Etkin" ? "On" : "Off");
                       button.data("status", currentStatus);
                   }
               },
               error: function (xhr, status, error) {
                   console.error("Error updating status:", error);
                   alert("An error occurred while updating the status.");
               }
           });
           $.ajax({
            url: "configuration.php",  // Point to the same file
            method: "POST",
            data: {
                candidateID: candidateID,
                action: newStatus === "İşten ayrıldı" ? "exclude" : "unexclude"
            },
            dataType: "json",
            success: function (response) {
                if (!response.success) {
                    alert("Error updating candidate exclusion: " + response.error);
                    // Revert UI if error occurs
                    button.removeClass(newClass).addClass(currentStatus === "Etkin" ? "btn-success" : "btn-danger");
                    button.text(currentStatus === "Etkin" ? "On" : "Off");
                    button.data("status", currentStatus);
                }
            },
            error: function () {
                alert("An error occurred while processing the candidate exclusion.");
                // Revert UI
                button.removeClass(newClass).addClass(currentStatus === "Etkin" ? "btn-success" : "btn-danger");
                button.text(currentStatus === "Etkin" ? "On" : "Off");
                button.data("status", currentStatus);
            }
           });
         });
     }
 
     function renderPaginationControls(dataArray) {
         const totalRows = dataArray.length;
         const totalPages = Math.ceil(totalRows / rowsPerPage);
         const paginationContainer = $("#candidatePaginationControls");
         paginationContainer.empty();
 
         if (totalPages <= 1) return; // No pagination needed
 
         let pageItems = [];
 
         // "First" and "Prev" buttons
         if (currentPage > 1) {
             pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="1">« First</a></li>`);
             pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">‹ Prev</a></li>`);
         }
 
         let maxVisiblePages = 5; // Adjust for better appearance
 
         if (totalPages <= maxVisiblePages) {
             // Show all pages if small number
             for (let i = 1; i <= totalPages; i++) {
                 pageItems.push(`<li class="page-item ${i === currentPage ? 'active' : ''}">
                                   <a class="page-link" href="#" data-page="${i}">${i}</a>
                                 </li>`);
             }
         } else {
             // Show first few pages, ellipsis, middle, ellipsis, last few pages
             if (currentPage > 3) {
                 pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
                 if (currentPage > 4) pageItems.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
             }
 
             let startPage = Math.max(2, currentPage - 2);
             let endPage = Math.min(totalPages - 1, currentPage + 2);
 
             for (let i = startPage; i <= endPage; i++) {
                 pageItems.push(`<li class="page-item ${i === currentPage ? 'active' : ''}">
                                   <a class="page-link" href="#" data-page="${i}">${i}</a>
                                 </li>`);
             }
 
             if (currentPage < totalPages - 3) {
                 if (currentPage < totalPages - 4) pageItems.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
                 pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
             }
         }
 
         // "Next" and "Last" buttons
         if (currentPage < totalPages) {
             pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next ›</a></li>`);
             pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">Last »</a></li>`);
         }
 
         paginationContainer.html(pageItems.join(""));
 
         // Click event for pagination
         $(".page-link").on("click", function (e) {
             e.preventDefault();
             const selectedPage = parseInt($(this).attr("data-page"));
             if (!isNaN(selectedPage)) {
                 renderTable(dataArray, selectedPage);
                 renderPaginationControls(dataArray);
             }
         });
     }
 
     function sortData(column) {
       allCandidates.sort((a, b) => {
           const valA = a[column] ?? "";
           const valB = b[column] ?? "";
 
           const numA = parseFloat(valA);
           const numB = parseFloat(valB);
 
           if (!isNaN(numA) && !isNaN(numB)) {
               return (numA - numB) * (currentSortDirection === 'asc' ? 1 : -1);
           }
 
           const dateA = new Date(valA);
           const dateB = new Date(valB);
           if (!isNaN(dateA.getTime()) && !isNaN(dateB.getTime())) {
               return (dateA - dateB) * (currentSortDirection === 'asc' ? 1 : -1);
           }
 
           return valA.toString().localeCompare(valB.toString()) * (currentSortDirection === 'asc' ? 1 : -1);
       });
 
       renderTable(allCandidates, 1);
       renderPaginationControls(allCandidates);
     }
 
 
     let studentSortColumn = null;
     let studentSortDirection = 'asc';
 
     function renderStudentTable(dataArray, pageNum) {
         const tbody = $("#studentsTab tbody");
         tbody.empty();
 
 
         if (!dataArray || dataArray.length === 0) {
             tbody.append('<tr><td colspan="5" class="text-center">No students found</td></tr>');
             return;
         }
         currentStudentPage = pageNum;
 
         const startIndex = (pageNum - 1) * rowsPerPage;
         const endIndex = startIndex + rowsPerPage;
         const pageData = dataArray.slice(startIndex, endIndex);
 
         pageData.forEach(student => {
           tbody.append(`
               <tr>
                 <td>${student.StudentID}</td>
                 <td>
                   <a href="impersonate.php?student_id=${student.StudentID}" class="text-decoration-none text-primary">
                     ${student.StudentFullName}
                   </a>
                 </td>
                 <td>${student.Mail}</td>
                 <td>${student.SuNET_Username}</td>
                 <td>${student.CGPA !== null ? student.CGPA : '-'}</td>
                 <td>${student.Categories || '-'}</td>
 
               </tr>
           `);
         });
     }
     function renderStudentPaginationControls(dataArray) {
       const totalRows = dataArray.length;
       const totalPages = Math.ceil(totalRows / rowsPerPage);
       const paginationContainer = $("#studentPaginationControls");
       paginationContainer.empty();
 
       if (totalPages <= 1) return; // No pagination needed
 
       let pageItems = [];
 
       // "First" and "Prev" buttons
       if (currentStudentPage > 1) {
           pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="1">« First</a></li>`);
           pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${currentStudentPage - 1}">‹ Prev</a></li>`);
       }
 
       let maxVisiblePages = 5;
 
       if (totalPages <= maxVisiblePages) {
           for (let i = 1; i <= totalPages; i++) {
               pageItems.push(`<li class="page-item ${i === currentStudentPage ? 'active' : ''}">
                                 <a class="page-link" href="#" data-page="${i}">${i}</a>
                               </li>`);
           }
       } else {
           if (currentStudentPage > 3) {
               pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
               if (currentStudentPage > 4) {
                   pageItems.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
               }
           }
 
           let startPage = Math.max(2, currentStudentPage - 2);
           let endPage = Math.min(totalPages - 1, currentStudentPage + 2);
 
           for (let i = startPage; i <= endPage; i++) {
               pageItems.push(`<li class="page-item ${i === currentStudentPage ? 'active' : ''}">
                                 <a class="page-link" href="#" data-page="${i}">${i}</a>
                               </li>`);
           }
 
           if (currentStudentPage < totalPages - 3) {
               if (currentStudentPage < totalPages - 4) {
                   pageItems.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
               }
               pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
           }
       }
 
       // "Next" and "Last" buttons
       if (currentStudentPage < totalPages) {
           pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${currentStudentPage + 1}">Next ›</a></li>`);
           pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">Last »</a></li>`);
       }
 
       paginationContainer.html(pageItems.join(""));
 
       // Click event for pagination
       paginationContainer.find(".page-link").on("click", function (e) {
           e.preventDefault();
           const selectedPage = parseInt($(this).attr("data-page"));
           if (!isNaN(selectedPage)) {
               currentStudentPage = selectedPage;
               renderStudentTable(allStudents, currentStudentPage);
               renderStudentPaginationControls(allStudents);
           }
       });
   }
 
 
 
  function sortStudentData(column) {
        allStudents.sort((a, b) => {
            const valA = a[column] ?? "";
            const valB = b[column] ?? "";

            // Try numeric comparison
            const numA = parseFloat(valA);
            const numB = parseFloat(valB);

            if (!isNaN(numA) && !isNaN(numB)) {
                return (numA - numB) * (studentSortDirection === 'asc' ? 1 : -1);
            }

            return valA.toString().localeCompare(valB.toString()) * (studentSortDirection === 'asc' ? 1 : -1);
        });

        renderStudentTable(allStudents, 1);
        renderStudentPaginationControls(allStudents);
    }
 
 
     // Pass the start and end dates from PHP to JavaScript
    const startDate = new Date("<?php echo $startDate; ?>");
    const endDate = new Date("<?php echo $endDate; ?>");
    const currentDate = new Date();  // Current date
 
 
    $(document).ready(function () {     
      const syncButton = $("#syncButton");
      if (currentDate >= startDate && currentDate <= endDate) {
            syncButton.prop("disabled", true);  // Disable button if within the range
            syncButton.html('<i class="fa-solid fa-sync"></i> Sync Disabled');
      } else {
            syncButton.prop("disabled", false); // Enable button if outside the range
            syncButton.html('<i class="fa-solid fa-sync"></i> Data Sync');
      }
      
      syncButton.on("click", function () {
        syncButton.prop("disabled", true);
        syncButton.html('<i class="fa-solid fa-sync fa-spin"></i> Synchronizing...');

        $.post("api/synchronizeFunc.php", { syncAll: "true" }, function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert("Error: " + response.message);
            }
        }).fail(function (xhr) {
            alert("AJAX error: " + xhr.responseText);
        }).always(function () {
            syncButton.prop("disabled", false);
            syncButton.html('<i class="fa-solid fa-sync"></i> Data Sync');
        });
      });
    });
 
 
  function loadLogList() {
    $("#syncLogsContent").html("<p>Loading logs...</p>");
    $("#backToLogListBtn").hide(); // Hide back button
 
    fetch("api/listSyncLogs.php")
      .then(res => res.json())
      .then(data => {
        if (data.success && data.logs.length > 0) {
          let logList = '<ul class="list-group">';
          data.logs.forEach(log => {
            logList += `
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <strong>${log.filename}</strong> <small class="text-muted">(${log.academicYear}, ${log.sync_date})</small>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="showLogDetails('${log.academicYear}', '${log.filename}')">
                  View
                </button>
              </li>
            `;
          });
          logList += '</ul>';
          $("#syncLogsContent").html(logList);
        } else {
          $("#syncLogsContent").html("<p>No logs found.</p>");
        }
      })
      .catch(error => {
        console.error("Error fetching logs:", error);
        $("#syncLogsContent").html("<p>Error loading logs.</p>");
      });
  }
 
  $("#viewLogsBtn").on("click", function () {
     loadLogList();
     new bootstrap.Modal(document.getElementById("syncLogsModal")).show();
 
     // Back button click
     $("#backToLogListBtn").off("click").on("click", function () {
           loadLogList();
    });
  });
 
  const appBaseUrl = <?php echo json_encode($config['app_base_url']); ?>; //get the base url pro2-dev ... from config
 
  function showLogDetails(academicYear, filename) {
     const path = `${appBaseUrl}odul/logs/${academicYear}/${filename}`;
     fetch(path)
       .then(res => res.json())
       .then(json => {
         const pre = document.createElement("pre");
         pre.textContent = JSON.stringify(json, null, 2);
         $("#syncLogsContent").html(pre);
 
         // Show back button
         $("#backToLogListBtn").show();
       })
       .catch(err => {
         console.error("Failed to load log:", err);
         $("#syncLogsContent").html("<p>Unable to load log file.</p>");
         $("#backToLogListBtn").show();
       });
  }
 
 
 
  </script>
 
 
 <div class="modal fade" id="syncLogsModal" tabindex="-1" aria-labelledby="syncLogsModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-xl modal-dialog-scrollable">
       <div class="modal-content">
         <div class="modal-header">
           <h5 class="modal-title" id="syncLogsModalLabel">Sync Logs</h5>
           <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
           <!-- Back button -->
           <button id="backToLogListBtn" class="btn btn-sm btn-secondary mb-3" style="display:none;">
             ← Back to Log List
           </button>
 
           <!-- Log content container -->
           <div id="syncLogsContent">
             <p>Loading logs...</p>
           </div>
         </div>
       </div>
     </div>
   </div>
 
   <!-- DataTables + Buttons JS -->
 <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
 <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
 <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>


 </body>
<?php
session_start();
require_once 'api/authMiddleware.php';
// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/database/dbConnection.php';

// Fetch available academic years from DB
try {
    $stmtYears = $pdo->prepare("SELECT YearID, Academic_year FROM AcademicYear_Table ORDER BY YearID DESC");
    $stmtYears->execute();
    $academicYears = $stmtYears->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching academic years: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voting Participation by Year</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Limitless Theme Styles -->
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
            overflow: auto;
            background-color: #f9f9f9;
        }

        /* Title Styling */
        .title {
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
            color: black;
        }

        .form-section {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .table-container {
            margin: 20px auto;
            max-width: 90%;
        }

        .error-message {
            display: none;
            text-align: center;
            color: #dc3545;
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
        }

        .action-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        /* Match button colors */
        .return-button, 
        .btn-custom {
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

        .return-button:hover, 
        .btn-custom:hover {
            background-color: #365a6b !important;
        }

        /* Custom Year Dropdown */
        .year-dropdown .btn {
            width: 200px;
            text-align: left;
        }

        .year-dropdown .dropdown-menu {
            width: 100%;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="title">Voting Participation by Year</h2>

    <!-- Year Selection -->
    <div class="form-section">
        <div class="btn-group year-dropdown">
            <button id="yearSelectBtn" class="btn btn-custom dropdown-toggle" data-bs-toggle="dropdown">
                Select Year
            </button>
            <div class="dropdown-menu">
                <?php foreach ($academicYears as $y): ?>
                    <a href="#" class="dropdown-item year-option" data-value="<?= $y['YearID'] ?>">
                        <?= htmlspecialchars($y['Academic_year']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <button id="viewReportBtn" class="btn btn-custom">
            <i class="fa fa-eye"></i> View Report
        </button>
    </div>

    <!-- Error Message -->
    <div id="error-message" class="error-message"></div>

    <!-- Table -->
    <!-- Table (initially hidden) -->
    <div class="table-container" id="table-section" style="display: none;">
        <table id="participationTable" class="table datatable-excel-background table-bordered table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Students Voted</th>
                    <th>Total Students</th>
                    <th>Participation Percentage</th>
                </tr>
            </thead>
            <tbody id="participation-body">
                <!-- Rows will be inserted dynamically -->
            </tbody>
        </table>
    </div>

</div>

<!-- Action Container -->
<div class="action-container">
    <button class="return-button" onclick="window.location.href='reportPage.php'">
        <i class="fa fa-arrow-left"></i> Return to Reports Page
    </button>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables + Buttons -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let gridInstance;
    let currentData = [];
    let selectedYear = null;

    const yearSelectBtn = document.getElementById("yearSelectBtn");
    const yearOptions = document.querySelectorAll(".year-option");
    const viewReportBtn = document.getElementById("viewReportBtn");
    const participationGrid = document.getElementById("participation-grid");
    const errorMessage = document.getElementById("error-message");

    // Handle Year Selection from Dropdown
    yearOptions.forEach(option => {
        option.addEventListener("click", function() {
            selectedYear = this.getAttribute("data-value");
            yearSelectBtn.textContent = this.textContent;

            if (gridInstance) gridInstance.destroy();
            errorMessage.style.display = "none";
            document.getElementById("table-section").style.display = "block";
        });
    });

    viewReportBtn.addEventListener("click", async () => {
        if (!selectedYear) {
            alert("Please select a year.");
            return;
        }

        try {
            const apiUrl = `api/getVotingParticipation.php?yearID=${selectedYear}`;
            const response = await fetch(apiUrl);
            const data = await response.json();

            if (data.error) {
                errorMessage.textContent = data.error;
                errorMessage.style.display = "block";
                return;
            }

            errorMessage.style.display = "none";

            // Clear existing table body
            const tbody = document.getElementById("participation-body");
            tbody.innerHTML = "";

            data.forEach(row => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${row.CategoryName}</td>
                    <td>${row.Students_Voted}</td>
                    <td>${row.Total_Students}</td>
                    <td>${row.Participation_Percentage}%</td>
                `;
                tbody.appendChild(tr);
            });

            // Re-initialize DataTable
            if ($.fn.DataTable.isDataTable('#participationTable')) {
                $('#participationTable').DataTable().clear().destroy();
            }

            $('#participationTable').DataTable({
                dom: '<"datatable-header d-flex justify-content-between align-items-center mb-2"fB>t<"datatable-footer"ip>',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        title: 'Voting Participation Report',
                        text: 'Export to Excel',
                        className: 'btn btn-custom'
                    }
                ],
                pageLength: 8
            });

        } catch (error) {
            console.error("Fetch Error:", error);
        }
    });
});
</script>

</body>
</html>

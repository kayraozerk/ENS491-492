<?php
session_start();
require_once 'api/authMiddleware.php';
if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nominate - Teaching Awards</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Limitless Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>

    <!-- Custom Styles -->
    <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
            margin: 0;
            padding-top: 70px;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
        }

        .card {
            background-color: #f9f9f9 ;
            color: white;
            border: 1px solid #45748a;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
        }

        .col{
            background-color: #f9f9f9 ;
            color: white;
            border: 1px solid #45748a;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
        }

        .card-body {
            font-size: 14px;
            color: #000;
        }

        .card-header {
            font-size: 1.3rem;
            font-weight: bold; 
            text-align: center; 
            padding: 15px; 
        }


        .form-body {
            padding: 20px;
        }

        .form-control {
            color: var(--bs-secondary);
        }


        .file-input {
            position: relative;
            margin-top: 15px;
            color: #45748a;
        }

        .file-preview {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            background: #fff;
        }

        .file-drop-zone {
            padding: 10px;
            text-align: center;
            border: 2px dashed #ddd;
            border-radius: 5px;
            background-color: #f8f9fa;
            cursor: pointer;
            color: #45748a; /* Custom text color */
        }

        .file-drop-zone p {
            color: #45748a; /* Set the text color */
            font-weight: bold; 
        }

        .file-drop-zone.dragging {
            border-color: #45748a;
            background-color: #e6f4f9;
        }
        .file-preview-thumbnails {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .file-preview-frame {
            position: relative;
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            text-align: center;
            line-height: 100px;
        }

        .file-preview-frame img {
            max-width: 100%;
            max-height: 100%;
        }

        .btn-close {
            position: absolute;
            top: 5px;
            right: 5px;
            z-index: 10;
        }

        .file-caption-name {
            border: none;
            background: #45748a;
            padding: 10px;
        }

        .btn-file input[type="file"] {
            display: none;
        }

    </style>
</head>
<body>
    
    <?php include 'navbar.php'; ?>

    <!-- Nomination Form Card -->
    <div class="container mt-5">
        <div class="row">
            <!-- Rules & Guidelines (Left) -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <strong>📜 Rules & Guidelines</strong>
                    </div>
                    <div class="card-body" style="max-height: 460px; overflow-y: auto; font-size: 14px;">
                    <p><strong>Purpose:</strong> The Teaching Assistant Award was created to acknowledge Teaching Assistants who excel in their activities.</p>

                    <p><strong>Eligibility:</strong></p>
                    <ul>
                        <li>The nominee must be a current graduate student.</li>
                        <li>The nominee must be a TA in at least one course during the 2023-2024 academic year.</li>
                    </ul>

                    <p><strong>Criteria:</strong></p>
                    <ul>
                        <li>(a) Being nominated by more than one person</li>
                        <li>(b) Being nominated both by faculty and by students</li>
                        <li>(c) Course evaluation results</li>
                        <li>(d) Data/Feedback about their work in more than one course (from course instructors)</li>
                        <li>(e) Whether nomination letters have been provided individually if nominated by a group of students</li>
                        <li>(f) GPA of the nominee</li>
                    </ul>

                    <p><strong>For questions about the nomination guidelines and process, please contact:</strong></p>
                    <p>Deniz İnan - <a href="mailto:deniz.inan@sabanciuniv.edu">deniz.inan@sabanciuniv.edu</a></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-secondary text-white text-center">Nomination Form</div>
                <!-- Form Body -->
                <div class="form-body">
                <form id="nominationForm" action="submitNomination.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="year_id" id="year_id">
        
                    <!-- Your Username -->
                        <div class="mb-3">
                            <label class="form-label text-secondary">Your Username</label>
                            <input type="text" name="SUnetUsername" class="form-control border-secondary text-secondary" value="<?php echo htmlspecialchars($_SESSION['user']); ?>" readonly>
                        </div>
                        <!-- Nominee's Name -->
                        <div class="mb-3">
                            <label class="form-label text-secondary">Nominee's Name</label>
                            <input type="text" name="NomineeName" class="form-control text-secondary border-secondary" placeholder="Enter nominee's name" required>
                        </div>
                        <!-- Nominee's Surname -->
                        <div class="mb-3">
                            <label class="form-label text-secondary">Nominee's Surname</label>
                            <input type="text" name="NomineeSurname" class="form-control text-secondary border-secondary" placeholder="Enter nominee's surname" required>
                        </div>
                        <!-- Upload References -->
                        <div class="file-input">
                            <div class="file-preview">
                                <button type="button" class="btn-close fileinput-remove" aria-label="Close" onclick="clearAllFiles()"></button>
                                <div class="file-drop-zone clearfix" 
                                    id="fileDropZone"
                                    ondragover="handleDragOver(event)" 
                                    ondrop="handleFileDrop(event)">
                                    <p>Drag and drop files here, or click to select files</p>
                                    <div class="file-preview-thumbnails clearfix" id="fileThumbnails"></div>
                                </div>
                            </div>
                            <input type="file" id="fileInput" name="ReferenceLetterFiles[]" multiple style="display: none;">
                            <div class="file-caption">
                                <input type="text" class="file-caption-name form-control" id="fileCaption" readonly placeholder="No file selected">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-secondary bg-secondary text-white">
                            Submit <i class="icon-paperplane"></i>
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>
    // Array to store all selected files
    let selectedFiles = [];

    // Handle manual file selection via input
    document.getElementById("fileDropZone").addEventListener("click", function(event) {
        event.stopPropagation();  // Prevent click event bubbling
        document.getElementById("fileInput").click();
    });


    // Handle file selection from input element
    document.getElementById("fileInput").addEventListener("change", function(event) {
        addFilesToSelection(event.target.files);
    });
    
    // Handle file drag over effect
    function handleDragOver(event) {
        event.preventDefault();
        event.stopPropagation();
        document.getElementById("fileDropZone").classList.add("dragging");
    }

    // Handle file drop event
    function handleFileDrop(event) {
        event.preventDefault();
        event.stopPropagation();
        document.getElementById("fileDropZone").classList.remove("dragging");
        addFilesToSelection(event.dataTransfer.files);
    }

    function addFilesToSelection(files) {
        // Add new files to the array, avoiding duplicates
        Array.from(files).forEach((file) => {
            if (!selectedFiles.some((f) => f.name === file.name && f.size === file.size)) {
                selectedFiles.push(file);
            }
        });

        updateFilePreview();
    }

    function updateFilePreview() {
        const fileThumbnails = document.getElementById("fileThumbnails");
        const fileCaption = document.getElementById("fileCaption");
        fileThumbnails.innerHTML = ""; // Clear existing thumbnails
        fileCaption.value = selectedFiles.length + " file(s) selected";

        selectedFiles.forEach((file, index) => {
            const frame = document.createElement("div");
            frame.className = "file-preview-frame";

            const img = document.createElement("img");
            const reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);

            const removeButton = document.createElement("button");
            removeButton.type = "button";
            removeButton.className = "btn-close btn-remove-file";
            removeButton.ariaLabel = "Remove";

            // Stop propagation to prevent triggering click event on the drop zone
            removeButton.onclick = function (event) {
                event.stopPropagation();  // Prevents triggering file input click
                removeFile(index);
            };

            frame.appendChild(img);
            frame.appendChild(removeButton);
            fileThumbnails.appendChild(frame);
        });
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1); // Remove file at the given index
        updateFilePreview(); // Update the preview
    }

    function clearAllFiles() {
        selectedFiles = []; // Clear all files
        updateFilePreview(); // Update the preview
    }

    // Remove dragging effect when drag leaves the zone
    document.getElementById("fileDropZone").addEventListener("dragleave", () => {
        document.getElementById("fileDropZone").classList.remove("dragging");
    });

    document.getElementById("nominationForm").addEventListener("submit", function(event) {
        event.preventDefault();

        if (selectedFiles.length === 0) {
            alert("Please upload at least one file.");
            return;
        }

        let formData = new FormData(this);

        selectedFiles.forEach((file, index) => {
            formData.append("ReferenceLetterFiles[]", file);
        });

        fetch(this.action, {
            method: this.method,
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log("Server response:", data);
            if (data.includes("Error")) {
                alert("Submission failed: " + data);
            } else {
                window.location.href = `thankYou.php?context=nominate`; // Redirect after successful submission
            }
        })
        .catch(error => {
            console.error("Error submitting the form:", error);
            alert("Error submitting the form. Please try again.");
        });

    });


    document.getElementById('nominationForm').addEventListener('submit', function () {
        document.querySelector('button[type="submit"]').disabled = false;
    });



    document.addEventListener("DOMContentLoaded", function () {
        fetch('api/getAcademicYear.php')
            .then(response => response.json())
            .then(data => {
                if (data.yearID) {
                    document.getElementById("year_id").value = data.yearID;
                } else {
                    console.error('Year not available');
                }
            })
            .catch(error => {
                console.error('Error fetching academic year:', error);
            });
    });
    </script>

    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

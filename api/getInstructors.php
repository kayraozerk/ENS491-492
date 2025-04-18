<?php
session_start();

require_once '../database/dbConnection.php';  // Include database connection

header('Content-Type: application/json');

// Ensure user is logged in and has a username
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$suNetUsername = $_SESSION['user'];
$categoryCode = isset($_GET['category']) ? $_GET['category'] : null;
$term = isset($_GET['term']) ? $_GET['term'] : null;

if (!$categoryCode) {
    echo json_encode(['status' => 'error', 'message' => 'Category code is required']);
    exit();
}

try {
    // Fetch academic year using getAcademicYear API
    $academicYearResponse = file_get_contents('http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/getAcademicYear.php');
    $academicYearData = json_decode($academicYearResponse, true);

    if (!$academicYearData || $academicYearData['status'] !== 'success') {
        echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve academic year']);
        exit();
    }

    // Allow only 'YYYY01' and 'YYYY02'
    $currentAcademicYears = [
        $academicYearData['academicYear'] . '01',
        $academicYearData['academicYear'] . '02'
    ];

    // DEBUG: Output academic year and term values
    $debugData = [
        'academicYearData' => $academicYearData,
        'allowedTerms' => $currentAcademicYears
    ];

    // Fetch StudentID of the logged-in user
    $stmtStudent = $pdo->prepare("SELECT id FROM Student_Table WHERE SuNET_Username = :suNetUsername");
    $stmtStudent->execute(['suNetUsername' => $suNetUsername]);
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['status' => 'error', 'message' => 'Student not found']);
        exit();
    }

    $studentID = $student['id'];

    // Fetch CourseIDs that the student is enrolled in
    $stmtCourses = $pdo->prepare("SELECT CourseID FROM Student_Course_Relation WHERE `student.id` = :studentID AND EnrollmentStatus = 'enrolled'");
    $stmtCourses->execute(['studentID' => $studentID]);
    $courses = $stmtCourses->fetchAll(PDO::FETCH_COLUMN);

    if (!$courses) {
        echo json_encode(['status' => 'error', 'message' => 'No enrolled courses found']);
        exit();
    }

    // DEBUG: Output enrolled courses
    $debugData['enrolledCourses'] = $courses;

    // Fetch CategoryID from Category Code 
    $stmtCategory = $pdo->prepare("SELECT CategoryID FROM Category_Table WHERE CategoryCode = :categoryCode");
    $stmtCategory->execute(['categoryCode' => $categoryCode]);
    $category = $stmtCategory->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid category code']);
        exit();
    }

    $categoryID = $category['CategoryID'];

    // DEBUG: Output CategoryID
    $debugData['categoryID'] = $categoryID;

    // Fetch Instructors for the student's courses
    $placeholders = implode(',', array_fill(0, count($courses), '?'));

    $query = "
        SELECT 
            i.id AS InstructorID,
            i.Name AS InstructorName,
            i.Mail AS InstructorEmail,
            i.Status,
            c.CourseName,
            c.Subject_Code,
            c.Course_Number,
            r.Term
        FROM Candidate_Table i
        INNER JOIN Candidate_Course_Relation r ON i.id = r.CandidateID
        INNER JOIN Courses_Table c ON r.CourseID = c.CourseID
        WHERE i.Role = 'Instructor' 
        AND i.Status = 'Etkin' 
        AND r.CategoryID = ?
        AND r.Term IN (?, ?)
        AND r.CourseID IN ($placeholders)
        AND NOT EXISTS (
            SELECT 1 FROM Exception_Table e WHERE e.CandidateID = i.id
        )
    ";

    $stmt = $pdo->prepare($query);

    // Bind parameters dynamically: first CategoryID, then two term values, then the course IDs
    $params = array_merge([$categoryID], $currentAcademicYears, $courses);
    // DEBUG: Output parameters to be bound
    $debugData['queryParams'] = $params;

    $stmt->execute($params);

    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // DEBUG: Include instructor results in debug output
    $debugData['instructorsResult'] = $instructors;

    if ($instructors) {
        echo json_encode(['status' => 'success', 'data' => $instructors]);
    } else {
        // Optionally include debug data in the error message for troubleshooting
        echo json_encode(['status' => 'error', 'message' => 'No instructors found for the given courses and category', 'debugData' => $debugData]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
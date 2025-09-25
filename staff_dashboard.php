<?php
session_start();
include('db.php'); // PDO connection

// ================= TIMEZONE =================
date_default_timezone_set("Asia/Manila");

// ================= CHECK LOGIN =================
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ================= USER INFO =================
$full_name = "Guest";
$staff_departments = [];

// Fetch user with role
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Restrict to staff only
if (!$user || $user['role'] !== 'staff') {
    header("Location: index.php");
    exit();
}

$full_name = htmlspecialchars($user['first_name'] . " " . $user['last_name']);

// ================= FETCH STAFF DEPARTMENTS =================
$stmt = $pdo->prepare("SELECT department_id FROM staff_departments WHERE staff_id = ?");
$stmt->execute([$user_id]);
$staff_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($staff_departments)) {
    $staff_departments = [0]; // fallback
}

// ================= SUMMARY COUNTS =================
$todayDate = date("Y-m-d");
$placeholders = str_repeat('?,', count($staff_departments) - 1) . '?';

// Total Requests Today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE DATE(created_at) = ? AND department IN ($placeholders)");
$stmt->execute(array_merge([$todayDate], $staff_departments));
$totalToday = $stmt->fetchColumn();

// Pending Requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = 'Pending' AND department IN ($placeholders)");
$stmt->execute($staff_departments);
$pendingCount = $stmt->fetchColumn();

// Processing Requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = 'Processing' AND department IN ($placeholders)");
$stmt->execute($staff_departments);
$processingCount = $stmt->fetchColumn();

// Declined Requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = 'Declined' AND department IN ($placeholders)");
$stmt->execute($staff_departments);
$declinedCount = $stmt->fetchColumn();

// Completed Transactions
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = 'Completed' AND department IN ($placeholders)");
$stmt->execute($staff_departments);
$completedCount = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="staff_dashboard.css">
<title>Staff Dashboard</title>
<style>
#archiveDatePicker {
    float: right;
    padding: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 14px;
}
</style>
</head>
<body>

<!-- ================= SIDEBAR ================= -->
<nav class="sidebar">
    <header>
        <div class="image-text">
            <span class="image"><img src="assets/fatimalogo.jpg" alt="logo"></span>
            <div class="text header-text">
                <span class="profession">Staff Dashboard</span>
                <span class="name"><?php echo htmlspecialchars($full_name); ?></span>
            </div>
        </div>
        <hr>
    </header>
    <div class="menu-bar">
        <div class="menu">
            <ul class="menu-links">
                <li class="nav-link"><button class="tablinks"><a href="staff_dashboard.php" class="tablinks">Dashboard</a></button></li>
                <li class="nav-link"><button class="tablinks"><a href="staff_requests.php" class="tablinks">Requests</a></button></li>
                <li class="nav-link"><button class="tablinks"><a href="now_serving.php" class="tablinks">Now Serving</a></button></li>
                <li class="nav-link"><button class="tablinks"><a href="archive.php" class="tablinks">Archive</a></button></li>
            </ul>
        </div>
        <div class="bottom-content">
            <li class="nav-link"><button class="tablinks"><a href="logout_staff.php" class="tablinks">Logout</a></button></li>
        </div>
    </div>
</nav>

<!-- ================= SUMMARY BOXES ================= -->
<section class="landing-page">
    <div class="service-box summary"><p>Total Requests Today</p><h3><?= $totalToday; ?></h3></div>
    <div class="service-box summary"><p>Pending Requests</p><h3><?= $pendingCount; ?></h3></div>
    <div class="service-box summary"><p>Processing Requests</p><h3><?= $processingCount; ?></h3></div>
    <div class="service-box summary"><p>Declined Requests</p><h3><?= $declinedCount; ?></h3></div>
    <div class="service-box summary"><p>Completed Transactions</p><h3><?= $completedCount; ?></h3></div>
</section>

<!-- ================= TABLES ================= -->
<section class="section" id="queue-monitoring">
    <div class="top-header">
        <h1>Queue <span>Monitoring</span></h1>
        <p>See who's in line.</p>
    </div>

    <div class="tables-container">

        <!-- ===== TODAY'S REQUESTS ===== -->
        <div class="service-box todays-requests-box">
            <h3>Today's Requests (<?= date("m/d/Y"); ?>)</h3>
            <div class="table-scroll">
                <table class="approve-table" id="requests-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Student No.</th>
                            <th>Section</th>
                            <th>Last SY</th>
                            <th>Last Semester</th>
                            <th>Documents</th>
                            <th>Notes</th>
                            <th>Department</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT r.*, d.name AS department_name 
                            FROM requests r
                            LEFT JOIN departments d ON r.department = d.id
                            WHERE DATE(r.created_at) = ? AND r.department IN ($placeholders)
                            ORDER BY r.created_at DESC
                        ");
                        $stmt->execute(array_merge([$todayDate], $staff_departments));
                        $i = 1;
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $attachments = json_encode(array_map('trim', explode(',', $row['attachment'])));
                            echo "<tr>";
                            echo "<td>" . $i++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['section']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['last_school_year']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['last_semester']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['documents']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['notes']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['department_name']) . "</td>";
                            echo "<td>
                                <button class='viewDetails'
                                    data-request-id='".htmlspecialchars($row['id'])."'
                                    data-request-first-name='".htmlspecialchars($row['first_name'])."'
                                    data-request-last-name='".htmlspecialchars($row['last_name'])."'
                                    data-request-student-number='".htmlspecialchars($row['student_number'])."'
                                    data-request-section='".htmlspecialchars($row['section'])."'
                                    data-request-last-school-year='".htmlspecialchars($row['last_school_year'])."'
                                    data-request-last-semester='".htmlspecialchars($row['last_semester'])."'
                                    data-request-documents='".htmlspecialchars($row['documents'])."'
                                    data-request-notes='".htmlspecialchars($row['notes'])."'
                                    data-request-attachments='".htmlspecialchars($attachments)."'>
                                    View
                                </button>
                            </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== ARCHIVE REQUESTS ===== -->
        <div class="table-box archive-box">
            <h3>
                Archived Requests
                <input type="date" id="archiveDatePicker">
            </h3>

            <div style="margin: 10px 0; text-align: right;">
                <form id="generateReportForm" action="generate_report.php" method="POST" target="_blank">
                    <input type="hidden" id="reportDateHidden" name="reportDate">
                    <button type="submit">Generate PDF Report</button>
                </form>
            </div>

            <div class="table-scroll" id="archiveTableContainer">
                <table class="approve-table" id="archiveTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Student No.</th>
                            <th>Section</th>
                            <th>Last SY</th>
                            <th>Last Semester</th>
                            <th>Documents</th>
                            <th>Notes</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- AJAX will populate archive rows -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<!-- ================= MODAL ================= -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Request Details</h2>
        <p>ID: <span id="requestID"></span></p>
        <p>Name: <span id="firstName"></span> <span id="lastName"></span></p>
        <p>Student No.: <span id="studentNumber"></span></p>
        <p>Section: <span id="section"></span></p>
        <p>Last SY: <span id="lastSchoolYear"></span></p>
        <p>Last Semester: <span id="lastSemesterAttended"></span></p>
        <p>Documents: <span id="documents"></span></p>
        <p>Notes: <span id="notes"></span></p>
        <p>Attachments:</p>
        <div id="attachmentContainer"></div>
    </div>
</div>

<script src="staff_dashboard.js"></script>
<script>
// ================= VIEW DETAILS MODAL =================
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("detailsModal");
    const closeModal = modal.querySelector(".close");
    const attachmentContainer = document.getElementById("attachmentContainer");

    document.querySelectorAll(".viewDetails").forEach(button => {
        button.addEventListener("click", function () {
            document.getElementById("requestID").textContent = button.dataset.requestId;
            document.getElementById("firstName").textContent = button.dataset.requestFirstName;
            document.getElementById("lastName").textContent = button.dataset.requestLastName;
            document.getElementById("studentNumber").textContent = button.dataset.requestStudentNumber;
            document.getElementById("section").textContent = button.dataset.requestSection;
            document.getElementById("lastSchoolYear").textContent = button.dataset.requestLastSchoolYear;
            document.getElementById("lastSemesterAttended").textContent = button.dataset.requestLastSemester;
            document.getElementById("documents").textContent = button.dataset.requestDocuments;
            document.getElementById("notes").textContent = button.dataset.requestNotes;

            attachmentContainer.innerHTML = '';
            let attachments = [];
            try { attachments = JSON.parse(button.dataset.requestAttachments); } 
            catch (err) { attachments = []; }

            if (attachments.length > 0 && attachments[0] !== "") {
                attachments.forEach(file => {
                    const a = document.createElement("a");
                    a.href = "uploads/" + file;
                    a.target = "_blank";
                    a.textContent = file;
                    a.style.display = "block";
                    attachmentContainer.appendChild(a);
                });
            } else {
                attachmentContainer.textContent = "No attachments.";
            }

            modal.style.display = "block";
        });
    });

    closeModal.onclick = () => modal.style.display = "none";
    window.onclick = (e) => { if (e.target === modal) modal.style.display = "none"; };

    document.getElementById("archiveDatePicker").addEventListener("change", function() {
        const selectedDate = this.value;
        const archiveTableBody = document.querySelector("#archiveTable tbody");
        if (!selectedDate) return;

        fetch("fetch_archives.php?date=" + selectedDate)
            .then(response => response.json())
            .then(data => {
                archiveTableBody.innerHTML = "";
                if (data.length === 0) {
                    archiveTableBody.innerHTML = "<tr><td colspan='9' style='text-align:center;'>No requests found for this date</td></tr>";
                    return;
                }
                let i = 1;
                data.forEach(row => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${i++}</td>
                        <td>${row.first_name} ${row.last_name}</td>
                        <td>${row.student_number}</td>
                        <td>${row.section}</td>
                        <td>${row.last_school_year}</td>
                        <td>${row.last_semester}</td>
                        <td>${row.documents}</td>
                        <td>${row.notes}</td>
                        <td>${row.status}</td>
                    `;
                    archiveTableBody.appendChild(tr);
                });
            })
            .catch(err => console.error(err));
    });

    document.getElementById("generateReportForm").addEventListener("submit", function (e) {
        const selectedDate = document.getElementById("archiveDatePicker").value;
        if (!selectedDate) {
            e.preventDefault();
            alert("Please select a date in the archive first.");
            return;
        }
        document.getElementById("reportDateHidden").value = selectedDate;
    });
});
</script>
</body>
</html>

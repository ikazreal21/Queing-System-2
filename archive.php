<?php
session_start();
include('db.php'); // PDO connection

date_default_timezone_set("Asia/Manila");

// ================= USER INFO =================
$full_name = "Guest";
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $full_name = htmlspecialchars($row['first_name'] . " " . $row['last_name']);
    }
}

// ================= FETCH ALL COMPLETED REQUESTS =================
$stmt = $pdo->query("SELECT * FROM requests WHERE status='Completed' ORDER BY created_at DESC");
$completedRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Archive - Completed Requests</title>
<link rel="stylesheet" href="archive.css">
<style>
#archiveDatePicker { float: right; padding:5px; border-radius:5px; border:1px solid #ccc; font-size:14px; }
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
                <span class="name"><?= $full_name ?></span>
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
                <li class="nav-link active"><button class="tablinks"><a href="archive.php" class="tablinks">Archive</a></button></li>
            </ul>
        </div>
        <div class="bottom-content">
            <li class="nav-link"><button class="tablinks"><a href="logout_staff.php" class="tablinks">Logout</a></button></li>
        </div>
    </div>
</nav>

<!-- ================= ARCHIVE TABLE ================= -->
<section class="section" id="archive-section">
    <div class="top-header">
        <h1>Archive <span>Completed Requests</span></h1>
        <p>All completed walk-ins and online requests.</p>
    </div>

    <div class="service-box archive-box">
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

        <div class="table-scroll">
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
                        <th>Attachments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    foreach ($completedRequests as $row) {
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
                        echo "<td><button class='viewAttachments' data-request-attachments='".htmlspecialchars($attachments)."'>View</button></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ================= ATTACHMENTS MODAL ================= -->
<div id="attachmentsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Attachments</h2>
        <div id="attachmentContainer"></div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("attachmentsModal");
    const closeModal = modal.querySelector(".close");
    const attachmentContainer = document.getElementById("attachmentContainer");

    function attachViewListeners() {
        document.querySelectorAll(".viewAttachments").forEach(button => {
            button.onclick = function () {
                attachmentContainer.innerHTML = '';
                let attachments = [];
                try { attachments = JSON.parse(button.dataset.requestAttachments); } catch(e){}
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
            };
        });
    }

    attachViewListeners(); // Initial attach

    closeModal.onclick = () => modal.style.display = "none";
    window.onclick = e => { if(e.target === modal) modal.style.display = "none"; };

    // ================= REPORT GENERATION =================
    document.getElementById("generateReportForm").addEventListener("submit", function(e){
        const date = document.getElementById("archiveDatePicker").value;
        if(!date) { e.preventDefault(); alert("Select a date first!"); return; }
        document.getElementById("reportDateHidden").value = date;
    });

    // ================= DATE FILTER =================
    document.getElementById("archiveDatePicker").addEventListener("change", function() {
        const selectedDate = this.value;
        const archiveTableBody = document.querySelector("#archiveTable tbody");
        if(!selectedDate) return;

        fetch("fetch_archives.php?date=" + selectedDate)
            .then(res => res.json())
            .then(data => {
                archiveTableBody.innerHTML = "";
                if(data.length === 0) {
                    archiveTableBody.innerHTML = "<tr><td colspan='9' style='text-align:center;'>No requests found for this date</td></tr>";
                    return;
                }
                let i = 1;
                data.forEach(row => {
                    const attachments = row.attachment ? row.attachment.split(',').map(f => f.trim()) : [];
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
                        <td><button class='viewAttachments' data-request-attachments='${JSON.stringify(attachments)}'>View</button></td>
                    `;
                    archiveTableBody.appendChild(tr);
                });
                attachViewListeners(); // Reattach modal events to new buttons
            })
            .catch(err => console.error(err));
    });
});
</script>

</body>
</html>

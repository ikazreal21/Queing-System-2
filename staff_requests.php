<?php
session_start();
include('db.php'); // $pdo connection

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


// Filters
$filter_date = $_GET['date'] ?? '';

// Fetch requests
$statuses = ['Pending', 'Processing', 'To Be Claimed', 'Completed', 'Declined'];
$requests = [];

foreach ($statuses as $status) {

    $sql = "SELECT r.*, d.processing_days
            FROM requests r
            LEFT JOIN documents d ON r.documents = d.name
            WHERE r.status = :status";

    $params = [':status' => $status];

    // Filter by staff departments (department names)
    if (!empty($staff_departments)) {
        $ph = [];
        foreach ($staff_departments as $i => $dept) {
            $key = ":dept$i";
            $ph[] = $key;
            $params[$key] = $dept;
        }
        $sql .= " AND r.department IN (" . implode(",", $ph) . ")";
    }

    if ($filter_date) {
        $sql .= " AND DATE(r.created_at) = :created_date";
        $params[':created_date'] = $filter_date;
    }

    $sql .= " ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $requests[$status] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}




// Fetch departments for walk-in modal
$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch documents for walk-in modal
$stmt = $pdo->query("SELECT name, processing_days FROM documents ORDER BY name ASC");
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure variables exist
$departments = $departments ?? [];
$documents = $documents ?? [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Staff Requests</title>
    <link rel="stylesheet" href="staff_requests.css?v=<?= time() ?>">
</head>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const refreshInterval = 5000; // 5 seconds
        const walkinModal = document.getElementById("walkin-modal-unique");
        const confirmModal = document.getElementById("walkin-confirm-modal");
        const lightboxOverlay = document.getElementById("lightboxOverlay");
        const declineModal = document.getElementById("decline-modal"); // <- add decline modal

        setInterval(() => {
            const isWalkinOpen = walkinModal && getComputedStyle(walkinModal).display !== "none";
            const isConfirmOpen = confirmModal && getComputedStyle(confirmModal).display !== "none";
            const isLightboxOpen = lightboxOverlay && getComputedStyle(lightboxOverlay).display !== "none";
            const isDeclineOpen = declineModal && getComputedStyle(declineModal).display !== "none"; // <- check decline modal

            // Only refresh if NONE are visible
            if (!isWalkinOpen && !isConfirmOpen && !isLightboxOpen && !isDeclineOpen) {
                window.location.reload();
            }
        }, refreshInterval);
    });

</script>

<body>
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
                <li class="nav-link"><button class="tablinks"><a href="now_serving.php" class="tablinks">Serving</a></button></li>
                <li class="nav-link"><button class="tablinks"><a href="archive.php" class="tablinks">Archive</a></button></li>
            </ul>
        </div>
        <div class="bottom-content">
            <li class="nav-link"><button class="tablinks"><a href="logout_user.php" class="tablinks">Logout</a></button></li>
        </div>
    </div>
</nav>

    <div class="home">
        <div class="top-header">
            <h1>Manage Requests</h1>
        </div>
        <div class="tables-container">

            <!-- PENDING -->
            <div class="todays-requests-box" id="pending-box">
                <h3>Pending Requests</h3>
                <div class="table-scroll">
                    <table class="approve-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Document</th>
                                <th>Attachment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests['Pending'] as $req): ?>
                                <tr data-request-id="<?= $req['id'] ?>"
                                    data-documents="<?= htmlspecialchars($req['documents']) ?>">
                                    <td><?= $req['id'] ?></td>
                                    <td><?= htmlspecialchars($req['first_name'] . " " . $req['last_name']) ?></td>
                                    <td><?= htmlspecialchars($req['documents']) ?></td>
                                    <td>
                                        <?php if ($req['attachment']): ?>
                                            <button class="action-btn view-btn"
                                                data-attachment="<?= htmlspecialchars($req['attachment']) ?>">View</button>
                                        <?php else: ?>No attachment<?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="update_request.php" class="approve-form"
                                            style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="button" class="action-btn approve-btn"
                                                data-request="<?= $req['id'] ?>">✓</button>
                                        </form>
                                        <form method="post" action="update_request.php" class="decline-form"
                                            style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <input type="hidden" name="reason" value="">
                                            <button type="button" class="action-btn decline-btn">✗</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>

            <!-- PROCESSING -->
            <div class="todays-requests-box" id="processing-box">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h3>Processing Requests</h3>
                    <button type="button" class="action-btn walkin-btn" id="walkin-all-btn">Add Walk-In</button>
                </div>
                <div class="table-scroll">
                    <table class="approve-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Document</th>
                                <th>Attachment</th>
                                <th class="scheduled-date">Scheduled Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests['Processing'] as $req): ?>
                                <tr data-request-id="<?= $req['id'] ?>"
                                    data-processing-start="<?= htmlspecialchars($req['processing_start'] ?? '') ?>"
                                    data-processing-end="<?= htmlspecialchars($req['processing_end'] ?? '') ?>">
                                    <td><?= $req['id'] ?></td>
                                    <td><?= htmlspecialchars($req['first_name'] . " " . $req['last_name']) ?></td>
                                    <td><?= htmlspecialchars($req['documents']) ?></td>
                                    <td>
                                        <?php if ($req['attachment']): ?>
                                            <button class="action-btn view-btn"
                                                data-attachment="<?= htmlspecialchars($req['attachment']) ?>">View</button>
                                        <?php else: ?>No attachment<?php endif; ?>
                                    </td>
                                    <td class="scheduled-date">
                                        <?= isset($req['processing_end']) ? date('Y-m-d', strtotime($req['processing_end'])) : '--' ?>
                                    </td>
                                    <td>
                                        <!-- Proceed to Claim -->
                                        <form method="post" action="update_request.php" class="finish-form"
                                            style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="action" value="finish">
                                            <button type="button" class="action-btn finish-btn">Proceed to Claim</button>
                                        </form>

                                        <!-- Back to Pending (only if not walk-in) -->
                                        <?php if (empty($req['walk_in']) || $req['walk_in'] != 1): ?>
                                            <form method="post" action="update_request.php" class="pending-form"
                                                style="display:inline;">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <input type="hidden" name="action" value="pending">
                                                <button type="button" class="action-btn pending-btn">Back to Pending</button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Decline -->
                                        <form method="post" action="update_request.php" class="decline-form"
                                            style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <input type="hidden" name="reason" value="">
                                            <button type="button" class="action-btn decline-btn">✗</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TO BE CLAIMED -->
            <div class="archives-box" style="display:flex; flex-direction:column;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h3 id="to-be-claimed-title">To Be Claimed</h3>
                </div>
                <div class="table-scroll">
                    <table class="approve-table" id="claimed-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Document</th>
                                <th>Attachment</th>
                                <th>Claim Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests['To Be Claimed'] as $req): ?>
                                <tr data-request-id="<?= $req['id'] ?>">
                                    <td><?= $req['id'] ?></td>
                                    <td><?= htmlspecialchars($req['first_name'] . " " . $req['last_name']) ?></td>
                                    <td><?= htmlspecialchars($req['documents']) ?></td>
                                    <td>
                                        <?php if ($req['attachment']): ?>
                                            <button class="action-btn view-btn"
                                                data-attachment="<?= htmlspecialchars($req['attachment']) ?>">View</button>
                                        <?php else: ?>
                                            No attachment
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="date" class="claim-date" data-request="<?= $req['id'] ?>" value="<?= $req['claim_date'] && $req['claim_date'] !== '0000-00-00'
                                              ? htmlspecialchars($req['claim_date'])
                                              : '' ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>





            <!-- COMPLETED -->
            <div class="archives-box" style="display:flex; flex-direction:column;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin-left:595px;">Completed Requests</h3>
                    <input type="date" id="completed-date-picker" style="padding:5px; width:200px;">
                </div>
                <div class="table-scroll">
                    <table class="approve-table" id="completed-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Document</th>
                                <th>Attachment</th>
                                <th>Completed Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JS will populate dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DECLINED -->
            <div class="archives-box">
                <h3>Declined Requests</h3>
                <div class="table-scroll">
                    <table class="approve-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Document</th>
                                <th>Attachment</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests['Declined'] as $req): ?>
                                <tr>
                                    <td><?= $req['id'] ?></td>
                                    <td><?= htmlspecialchars($req['first_name'] . " " . $req['last_name']) ?></td>
                                    <td><?= htmlspecialchars($req['documents']) ?></td>
                                    <td>
                                        <?php if ($req['attachment']): ?>
                                            <button class="action-btn view-btn"
                                                data-attachment="<?= htmlspecialchars($req['attachment']) ?>">View</button>
                                        <?php else: ?>No attachment<?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($req['decline_reason']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Lightbox -->
    <div id="lightboxOverlay" style="display:none;">
        <span id="closeLightbox" style="cursor:pointer;">&times;</span>
        <select id="attachmentSelector"></select>
        <div id="attachmentContainer">
            <img id="lightboxImage" src="" alt="Attachment" style="display:none; max-width:100%; max-height:80vh;">
            <iframe id="lightboxPDF" src="" style="display:none; width:100%; height:80vh;" frameborder="0"></iframe>
        </div>
    </div>

    <script>
        function confirmProceed() { return confirm("Are you sure you want to proceed this request to claim?"); }
        function confirmPending() { return confirm("Are you sure you want to send this request back to pending?"); }
    </script>

    <script src="staff_requests.js?v=<?= time() ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const countdownCells = document.querySelectorAll("#processing-box .countdown");

            function updateCountdowns() {
                countdownCells.forEach(cell => {
                    const tr = cell.closest("tr");
                    const scheduled = tr.dataset.scheduledDate;
                    if (!scheduled) return;

                    const scheduledDate = new Date(scheduled);
                    const now = new Date();
                    let diff = scheduledDate - now;

                    if (diff <= 0) {
                        cell.textContent = "Ready!";
                    } else {
                        const hrs = Math.floor(diff / (1000 * 60 * 60));
                        const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        const secs = Math.floor((diff % (1000 * 60)) / 1000);
                        cell.textContent = `${hrs.toString().padStart(2, '0')} : ${mins.toString().padStart(2, '0')} : ${secs.toString().padStart(2, '0')}`;
                    }
                });
            }

            setInterval(updateCountdowns, 1000); // update every second
            updateCountdowns(); // initial call
        });
    </script>
    <!-- WALK-IN MODAL - place at the very end of <body> -->
    <div id="walkin-modal-unique" class="modal-unique">
        <div class="modal-content-unique">
            <span class="close-unique" id="walkin-close-unique">&times;</span>
            <h3>Add Walk-In Request</h3>
            <form id="walkin-form-unique" enctype="multipart/form-data" method="POST" action="process_walkin.php">
                <div class="info-columns-unique">
                    <div class="info-left-unique">
                        <label>First Name:</label>
                        <input type="text" name="first_name" placeholder="Enter First Name" required>
                        <label>Student Number:</label>
                        <input type="text" name="student_number" placeholder="Enter Student Number" required>
                        <label>Last School Year Attended:</label>
                        <select name="last_school_year" required>
                            <option value="">-- Select School Year --</option>
                            <option value="2020-2021">2020-2021</option>
                            <option value="2021-2022">2021-2022</option>
                            <option value="2022-2023">2022-2023</option>
                            <option value="2023-2024">2023-2024</option>
                            <option value="2024-2025">2024-2025</option>
                        </select>
                        <label>Last Semester Attended:</label>
                        <select name="last_semester" required>
                            <option value="">-- Select Semester --</option>
                            <option value="First Semester">First Semester</option>
                            <option value="Second Semester">Second Semester</option>
                            <option value="Third Semester">Third Semester</option>
                        </select>
                    </div>
                    <div class="info-right-unique">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" placeholder="Enter Last Name" required>
                        <label>Section:</label>
                        <input type="text" name="section" placeholder="e.g., BSIT 1-Y1-2" required>
                        <label>Department:</label>
                        <select name="department" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['id']); ?>">
                                    <?= htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h3>Documents for Request</h3>
                <div class="document-columns-unique">
                    <div class="document-left-unique">
                        <?php foreach (array_slice($documents, 0, ceil(count($documents) / 2)) as $doc): ?>
                            <?php $days = (int) $doc['processing_days'];
                            $claim_date = date('F j, Y', strtotime("+$days days")); ?>
                            <label class="doc-checkbox-unique"
                                title="Processing: <?= $days; ?> day(s) • Claim on <?= $claim_date; ?>">
                                <input type="checkbox" name="documents[]" value="<?= htmlspecialchars($doc['name']); ?>">
                                <span><?= htmlspecialchars($doc['name']); ?> (<?= $days; ?>d)</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="document-right-unique">
                        <?php foreach (array_slice($documents, ceil(count($documents) / 2)) as $doc): ?>
                            <?php $days = (int) $doc['processing_days'];
                            $claim_date = date('F j, Y', strtotime("+$days days")); ?>
                            <label class="doc-checkbox-unique"
                                title="Processing: <?= $days; ?> day(s) • Claim on <?= $claim_date; ?>">
                                <input type="checkbox" name="documents[]" value="<?= htmlspecialchars($doc['name']); ?>">
                                <span><?= htmlspecialchars($doc['name']); ?> (<?= $days; ?>d)</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="upload-section-unique">
                    <label for="attachment-unique">Upload Attachments (Images/PDFs):</label>
                    <input type="file" name="attachment[]" id="attachment-unique" accept=".jpg,.jpeg,.png,.pdf"
                        multiple>
                </div>

                <div class="notes-section-unique">
                    <label>Notes / Other Concerns:</label>
                    <textarea name="notes" rows="4" placeholder="Write any additional concerns here..."></textarea>
                </div>

                <button type="submit" id="submit-form-unique">Submit</button>
            </form>
        </div>
    </div>
    <!-- Confirmation Modal -->
    <div id="walkin-confirm-modal" class="modal-unique">
        <div class="modal-content-unique">
            <span class="close-unique" id="walkin-confirm-close">&times;</span>
            <h3>Confirm Walk-In Request</h3>
            <div id="walkin-confirm-details" style="margin-bottom:20px;"></div>
            <button type="button" id="walkin-confirm-submit">Confirm & Submit</button>
            <button type="button" id="walkin-confirm-cancel" style="margin-left:10px;">Cancel</button>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModalOverlay" class="modal-overlay">
        <div class="modal">
            <h2>Confirm Action</h2>
            <p id="confirmModalMessage">Are you sure?</p>
            <div class="modal-buttons">
                <button class="btn-no" id="confirmModalCancel">X</button>
                <button class="btn-yes" id="confirmModalYes"></button>
            </div>
        </div>
    </div>
    <!-- Decline Modal -->
    <div id="decline-modal" class="custom-modal">
        <div class="custom-modal-content">
            <h3>Reason for Declining</h3>
            <textarea id="decline-reason" placeholder="Enter reason..." rows="4"></textarea>
            <div class="modal-buttons">
                <button id="decline-submit">Submit</button>
                <button id="decline-cancel">Cancel</button>
            </div>
        </div>
    </div>

</body>

</html>
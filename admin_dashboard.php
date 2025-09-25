<?php
// Include the database connection
include('db.php');

// Start the session
session_start();

// Check if the user_email session variable is set
if (isset($_SESSION['user_email'])) {
    $user_email = $_SESSION['user_email'];

    // Fetch user details from the database
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, role FROM users WHERE email = :email");
    $stmt->execute(['email' => $user_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $first_name = $user['first_name'];
        $last_name  = $user['last_name'];
        $role       = $user['role'];
        $user_name  = $first_name . ' ' . $last_name;

        // ✅ Restrict to admins only
        if ($role !== 'admin') {
            header("Location: index.php");
            exit();
        }
    } else {
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}

// System statistics
$totalRequests = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
$pendingRequests = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'")->fetchColumn();
$approvedRequests = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'approved'")->fetchColumn();
$declinedRequests = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'declined'")->fetchColumn();
$servingRequests = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'serving'")->fetchColumn();

// ✅ Fetch all requests directly (no join needed)
$stmt = $pdo->query("
    SELECT * 
    FROM requests 
    ORDER BY id DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin_dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Admin Dashboard</title>
</head>
<body>
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="assets/fatimalogo.jpg" alt="logo">
                </span>
                <div class="text header-text">
                    <span class="profession">Admin Dashboard</span>
                    <span class="name"><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>
            <hr>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <li class="nav-link"><a href="admin_dashboard.php" class="tablinks">Dashboard</a></li>
                    <li class="nav-link"><a href="admin_manage.php" class="tablinks">Manage Staff</a></li>
                    <li class="nav-link"><a href="admin_documents.php" class="tablinks">Add Documents</a></li>
                </ul>
            </div>
            <div class="bottom-content">
                <li class="nav-link"><a href="logout_admin.php" class="tablinks">Logout</a></li>
            </div>
        </div>
    </nav>

    <section class="home" id="home-section">
        <!-- System Statistics -->
        <div class="stats-container">
            <div class="stat"><div class="stat-content"><h1><?php echo $totalRequests; ?></h1><h3>Total Requests</h3></div></div>
            <div class="stat"><div class="stat-content"><h1><?php echo $servingRequests; ?></h1><h3>Serving</h3></div></div>
            <div class="stat"><div class="stat-content"><h1><?php echo $pendingRequests; ?></h1><h3>Pending</h3></div></div>
            <div class="stat"><div class="stat-content"><h1><?php echo $approvedRequests; ?></h1><h3>Approved</h3></div></div>
            <div class="stat"><div class="stat-content"><h1><?php echo $declinedRequests; ?></h1><h3>Declined</h3></div></div>
        </div>

        <div class="table-container">
            <div class="table_responsive">
                <h1>LIST OF ALL REQUESTS</h1>
                <br>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Request ID</th>
                            <th style="width: 20%;">Name</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 25%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $row): 
                            $requestId = 'REQ' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
                            $status = ucfirst($row['status']);
                            $fullName = $row['first_name'] . ' ' . $row['last_name'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($requestId); ?></td>
                            <td><?php echo htmlspecialchars($fullName); ?></td>
                            <td>
                                <span class="status <?php echo strtolower($status); ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td>
                                <button 
                                    type="button" 
                                    class="view-details-btn"
                                    data-request-id="<?php echo htmlspecialchars($requestId); ?>"
                                    data-name="<?php echo htmlspecialchars($fullName); ?>"
                                    data-student-number="<?php echo htmlspecialchars($row['student_number']); ?>"
                                    data-section="<?php echo htmlspecialchars($row['section']); ?>"
                                    data-school-year="<?php echo htmlspecialchars($row['last_school_year']); ?>"
                                    data-semester="<?php echo htmlspecialchars($row['last_semester']); ?>"
                                    data-documents="<?php echo htmlspecialchars($row['documents']); ?>"
                                    data-notes="<?php echo htmlspecialchars($row['notes'] ?? ''); ?>"
                                    data-submitted="<?php echo htmlspecialchars($row['created_at']); ?>"
                                    data-status="<?php echo htmlspecialchars($status); ?>"
                                    data-reasons="<?php echo htmlspecialchars($row['decline_reason'] ?? ''); ?>"
                                >
                                    View Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Modal -->
                <div id="detailsModal" class="modal">
                    <div class="modal-content">
                        <span class="close-btn">&times;</span>
                        <h2>Request Details</h2>
                        <hr>
                        <p><strong>Request ID:</strong> <span id="modalRequestId"></span></p>
                        <p><strong>Name:</strong> <span id="modalName"></span></p>
                        <p><strong>Student Number:</strong> <span id="modalStudentNumber"></span></p>
                        <p><strong>Section:</strong> <span id="modalSection"></span></p>
                        <p><strong>Last School Year:</strong> <span id="modalSchoolYear"></span></p>
                        <p><strong>Last Semester:</strong> <span id="modalSemester"></span></p>
                        <p><strong>Documents Requested:</strong> <span id="modalDocuments"></span></p>
                        <p><strong>Additional Notes:</strong> <span id="modalNotes"></span></p>
                        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                        <p><strong>Reason (if any):</strong> <span id="modalReasons"></span></p>
                        <p><strong>Submitted At:</strong> <span id="modalSubmitted"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<script src="admin_dashboard.js"></script>
</body>
</html>

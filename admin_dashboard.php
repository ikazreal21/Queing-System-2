<?php
// Include the database connection
include('db.php');

// Start the session
session_start();

// Check if the user_email session variable is set
if (isset($_SESSION['user_email'])) {
    $user_email = $_SESSION['user_email'];

    // Fetch user details
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

// ================= SYSTEM STATISTICS =================
$totalRequests      = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
$pendingRequests    = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'")->fetchColumn();
$processingRequests = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Processing'")->fetchColumn();
$servingRequests    = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Serving'")->fetchColumn();
$completedRequests  = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Completed'")->fetchColumn();
$declinedRequests   = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Declined'")->fetchColumn();

// ================= FILTER HANDLING =================
$filter = $_GET['filter'] ?? 'overall'; // default: overall

$whereDate = '';
if ($filter === 'daily') {
    $whereDate = "AND DATE(r.completed_date) = CURDATE()";
} elseif ($filter === 'weekly') {
    $whereDate = "AND YEARWEEK(r.completed_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'monthly') {
    $whereDate = "AND YEAR(r.completed_date) = YEAR(CURDATE()) 
                  AND MONTH(r.completed_date) = MONTH(CURDATE())";
}

// ================= STAFF COMPLETED REQUESTS (with filter) =================
$stmt = $pdo->prepare("
    SELECT 
        u.id AS staff_id,
        CONCAT(u.first_name, ' ', u.last_name) AS staff_name,
        u.counter_no,
        d.name AS department_name,
        COUNT(r.id) AS completed_requests
    FROM users u
    LEFT JOIN requests r 
           ON r.served_by = u.id 
          AND r.status = 'Completed' 
          $whereDate
    LEFT JOIN departments d 
           ON u.department_id = d.id
    WHERE u.role = 'staff'
    GROUP BY u.id, u.first_name, u.last_name, u.counter_no, d.name
    ORDER BY completed_requests DESC
");
$stmt->execute();
$staff_completed = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ================= ALL REQUESTS =================
$stmt = $pdo->query("SELECT * FROM requests ORDER BY id DESC");
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
                <li class="nav-link"><a href="logout_user.php" class="tablinks">Logout</a></li>
            </div>
        </div>
    </nav>

    <section class="home" id="home-section">
        <!-- ================== System Statistics ================== -->
        <div class="stats-container">
            <div class="stat"><div class="stat-content"><h1><?php echo $totalRequests; ?></h1><h3>Total Requests</h3></div></div>
            <div class="stat"><div class="stat-content"><h1><?php echo $servingRequests; ?></h1><h3>Serving</h3></div></div>
            <div class="stat"><div class="stat-content"><h1><?php echo $pendingRequests; ?></h1><h3>Pending</h3></div></div>
            <div class="stat"><div class="stat-content"><h1><?php echo $processingRequests; ?></h1><h3>Processing</h3></div></div>
            <div class="stat"><div class="stat-content"><h1><?php echo $completedRequests; ?></h1><h3>Completed</h3></div></div>
            <div class="stat"><div class="stat-content"><h1><?php echo $declinedRequests; ?></h1><h3>Declined</h3></div></div>
        </div>

        <div style="text-align:center; margin-bottom:20px;">
    <form method="GET" action="admin_dashboard.php" style="display:inline-block;">
        <label for="filter">Filter by:</label>
        <select name="filter" id="filter" onchange="this.form.submit()">
            <option value="overall" <?php if($filter==='overall') echo 'selected'; ?>>Overall</option>
            <option value="daily" <?php if($filter==='daily') echo 'selected'; ?>>Today</option>
            <option value="weekly" <?php if($filter==='weekly') echo 'selected'; ?>>This Week</option>
            <option value="monthly" <?php if($filter==='monthly') echo 'selected'; ?>>This Month</option>
        </select>
    </form>
</div>


        <!-- ================== Staff Completed Requests ================== -->
<div class="table-container">
    <div class="table_responsive">
        <h1 style="margin-bottom:15px; font-size:22px; color:#333; text-align:center;">
            Staff Completed Requests
        </h1>
        <table class="styled-table">
    <thead>
        <tr>
            <th>Staff Name</th>
            <th>Counter No.</th>
            <th>Department</th>
            <th>Completed Requests</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($staff_completed)): ?>
            <?php foreach ($staff_completed as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['counter_no'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                    <td class="center"><?php echo $row['completed_requests']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="center">No completed requests yet.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

    </div>
</div>

<!-- ================== Chart.js Bar Chart ================== -->
<div style="max-width: 800px; margin: 30px auto;">
    <canvas id="staffChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const staffNames = <?php echo json_encode(array_column($staff_completed, 'staff_name')); ?>;
    const completedCounts = <?php echo json_encode(array_column($staff_completed, 'completed_requests')); ?>;

    const ctx = document.getElementById('staffChart').getContext('2d');

    // Create gradient color for bars
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(54, 162, 235, 0.9)');
    gradient.addColorStop(1, 'rgba(54, 162, 235, 0.3)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: staffNames,
            datasets: [{
                label: 'Completed Requests',
                data: completedCounts,
                backgroundColor: gradient,
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 8,
                hoverBackgroundColor: 'rgba(75, 192, 192, 0.9)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Completed Requests per Staff',
                    font: { size: 20, weight: 'bold' },
                    padding: { top: 10, bottom: 20 }
                },
                tooltip: {
                    backgroundColor: '#333',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#999',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return ` ${context.raw} request(s) completed`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        font: { size: 14 }
                    },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 14 }
                    }
                }
            }
        }
    });
</script>




<script src="admin_dashboard.js"></script>
</body>
</html>

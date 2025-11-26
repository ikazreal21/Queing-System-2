<?php
include('db.php');
session_start();

if (isset($_SESSION['user_email'])) {
    $user_email = $_SESSION['user_email'];

    $stmt = $pdo->prepare("SELECT first_name, last_name, email, role FROM users WHERE email = :email");
    $stmt->execute(['email' => $user_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $first_name = $user['first_name'];
        $last_name  = $user['last_name'];
        $role       = $user['role'];
        $user_name  = $first_name . ' ' . $last_name;

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

$totalRequests      = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
$pendingRequests    = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'")->fetchColumn();
$processingRequests = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Processing'")->fetchColumn();
$servingRequests    = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Serving'")->fetchColumn();
$completedRequests  = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Completed'")->fetchColumn();
$declinedRequests   = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Declined'")->fetchColumn();

$filter = $_GET['filter'] ?? 'overall';

$whereDate = '';
if ($filter === 'daily') {
    $whereDate = "AND DATE(r.completed_date) = CURDATE()";
} elseif ($filter === 'weekly') {
    $whereDate = "AND YEARWEEK(r.completed_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'monthly') {
    $whereDate = "AND YEAR(r.completed_date) = YEAR(CURDATE()) AND MONTH(r.completed_date) = MONTH(CURDATE())";
}

$stmt = $pdo->prepare("
    SELECT 
        u.id AS staff_id,
        CONCAT(u.first_name, ' ', u.last_name) AS staff_name,
        u.counter_no,
        d.name AS department_name,
        COUNT(r.id) AS completed_requests
    FROM users u
    LEFT JOIN staff_departments sd ON sd.staff_id = u.id
    LEFT JOIN departments d ON d.id = sd.department_id
    LEFT JOIN requests r 
           ON r.served_by = u.id 
          AND r.status = 'Completed' 
          $whereDate
    WHERE u.role = 'staff'
    GROUP BY u.id, u.first_name, u.last_name, u.counter_no, d.name
    ORDER BY completed_requests DESC
");

$stmt->execute();
$staff_completed = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="admin_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<title>Admin Dashboard</title>
</head>
<body>

<!-- ✅ NEW SIDEBAR (Matches admin_documents.css) -->
<nav class="sidebar">
    <div class="brand">
        <img src="assets/fatimalogo.jpg" class="brand-logo" alt="Logo">
        <div>
            <span class="brand-title">Admin Dashboard</span>
            <span class="brand-sub">Welcome <?php echo htmlspecialchars($user_name); ?></span>
        </div>
    </div>

    <ul class="menu-links">
        <li><a href="admin_dashboard.php" class="active"><i class='bx bx-home'></i> Dashboard</a></li>
        <li><a href="admin_manage.php"><i class='bx bx-user'></i> Manage Staff</a></li>
        <li><a href="admin_documents.php"><i class='bx bx-file'></i>Admin Resources</a></li>

        <li class="spacer"></li>

        <li><a href="logout_user.php"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</nav>

<!-- ✅ Change from <section class="home"> to <div class="home"> -->
<div class="home">

<div class="stats-container">
    <div class="stat"><div class="stat-content"><h1><?php echo $totalRequests; ?></h1><h3>Total Requests</h3></div></div>
    <div class="stat"><div class="stat-content"><h1><?php echo $servingRequests; ?></h1><h3>Serving</h3></div></div>
    <div class="stat"><div class="stat-content"><h1><?php echo $pendingRequests; ?></h1><h3>Pending</h3></div></div>
    <div class="stat"><div class="stat-content"><h1><?php echo $processingRequests; ?></h1><h3>Processing</h3></div></div>
    <div class="stat"><div class="stat-content"><h1><?php echo $completedRequests; ?></h1><h3>Completed</h3></div></div>
    <div class="stat"><div class="stat-content"><h1><?php echo $declinedRequests; ?></h1><h3>Declined</h3></div></div>
</div>

<div style="text-align:center; margin-bottom:20px;">
<form method="GET" action="admin_dashboard.php">
    <label for="filter">Filter by:</label>
    <select name="filter" onchange="this.form.submit()">
        <option value="overall" <?php if($filter==='overall') echo 'selected'; ?>>Overall</option>
        <option value="daily" <?php if($filter==='daily') echo 'selected'; ?>>Today</option>
        <option value="weekly" <?php if($filter==='weekly') echo 'selected'; ?>>This Week</option>
        <option value="monthly" <?php if($filter==='monthly') echo 'selected'; ?>>This Month</option>
    </select>
</form>
</div>

<div class="table-container">
    <h1 style="text-align:center;">Staff Completed Requests</h1>
    <table class="styled-table">
        <thead>
            <tr>
                <th>Staff Name</th>
                <th>Counter</th>
                <th>Department</th>
                <th>Completed</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($staff_completed as $row): ?>
            <tr>
                <td><?php echo $row['staff_name']; ?></td>
                <td><?php echo $row['counter_no'] ?: 'N/A'; ?></td>
                <td><?php echo $row['department_name'] ?: 'N/A'; ?></td>
                <td class="center"><?php echo $row['completed_requests']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div style="max-width:800px; margin:30px auto;">
    <canvas id="staffChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
 const staffNames = <?php echo json_encode(array_column($staff_completed, 'staff_name')); ?>;
 const completedCounts = <?php echo json_encode(array_column($staff_completed, 'completed_requests')); ?>;
 new Chart(document.getElementById('staffChart'), {
     type: 'bar',
     data: { labels: staffNames, datasets: [{ data: completedCounts, borderWidth: 1 }] }
 });
</script>

</div>
</body>
</html>

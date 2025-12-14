<?php
session_start();
include('db.php'); // PDO connection

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // redirect to login if not logged in
    exit();
}

// Fetch user info
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // If no user found, force logout
    session_destroy();
    header("Location: index.php");
    exit();
}

// ✅ Allow only staff accounts
if ($user['role'] !== 'staff') {
    header("Location: index.php"); // redirect if not staff
    exit();
}

$full_name = $user['first_name'] . ' ' . $user['last_name'];
$staff_departments = [];

// Fetch staff's assigned departments (department_id)
$stmt = $pdo->prepare("SELECT department_id FROM staff_departments WHERE staff_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$staff_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

// If staff has no departments, set a dummy value so no requests are fetched
if (empty($staff_departments)) {
    $staff_departments = [-1]; // impossible department
}

$inQuery = implode(',', array_fill(0, count($staff_departments), '?'));

// ✅ Queueing / Only show if claim_date is today
$stmt = $pdo->prepare("
    SELECT * FROM requests 
    WHERE status = 'In Queue Now'
      AND (claim_date IS NULL OR DATE(claim_date) = CURDATE())
      AND department IN ($inQuery)
    ORDER BY id ASC
");
$stmt->execute($staff_departments);
$queueing = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ✅ Serving: status 'Serving'
$stmt = $pdo->prepare("
    SELECT * FROM requests 
    WHERE status='Serving' 
    AND department IN ($inQuery)
    ORDER BY serving_position ASC
");
$stmt->execute($staff_departments);
$serving = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Completed: status 'Completed'
$stmt = $pdo->prepare("
    SELECT * FROM requests 
    WHERE status='Completed' 
    AND department IN ($inQuery)
    ORDER BY approved_date DESC
");
$stmt->execute($staff_departments);
$completed = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Serving</title>
<link rel="stylesheet" href="staff_requests.css">
<link rel="stylesheet" href="now_serving.css">
</head>
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

<div class="container" data-department="<?php echo htmlspecialchars($staff_departments[0] ?? 0); ?>">
    <!-- Queueing Column -->
    <div class="column" id="queueing-column">
        <h2>Queueing</h2>
        <?php foreach($queueing as $req): ?>
        <div class="card" id="req-<?php echo $req['id']; ?>">
            <span><strong>ID:</strong> <span class="value"><?php echo $req['id']; ?></span></span>
            <span><strong>Name:</strong> <span class="value"><?php echo htmlspecialchars($req['first_name'].' '.$req['last_name']); ?></span></span>
            <span><strong>Documents:</strong> <span class="value"><?php echo htmlspecialchars($req['documents']); ?></span></span>
            <span><strong>Notes:</strong> <span class="value"><?php echo htmlspecialchars($req['notes']); ?></span></span>
            <span><strong>Status:</strong> <span class="value"><?php echo htmlspecialchars($req['status']); ?></span></span>
            <div class="actions">
                <button class="btn-serve" data-id="<?php echo $req['id']; ?>">Serve</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Serving Column -->
    <div class="column" id="serving-column">
        <h2>Serving</h2>
        <?php foreach($serving as $req): ?>
        <div class="card" id="req-<?php echo $req['id']; ?>">
            <span><strong>ID:</strong> <span class="value"><?php echo $req['id']; ?></span></span>
            <span><strong>Name:</strong> <span class="value"><?php echo htmlspecialchars($req['first_name'].' '.$req['last_name']); ?></span></span>
            <span><strong>Documents:</strong> <span class="value"><?php echo htmlspecialchars($req['documents']); ?></span></span>
            <span><strong>Notes:</strong> <span class="value"><?php echo htmlspecialchars($req['notes']); ?></span></span>
            <span><strong>Status:</strong> <span class="value"><?php echo htmlspecialchars($req['status']); ?></span></span>

            <?php if(!empty($req['queueing_num'])): ?>
                <span class="queue-number"><strong>Queue #:</strong> <?php echo $req['queueing_num']; ?></span>
            <?php endif; ?>

            <?php if(!empty($req['serving_position'])): ?>
                <span class="position"><strong>Position:</strong> <?php echo $req['serving_position']; ?></span>
            <?php endif; ?>

            <div class="actions">
                <button class="btn-back" data-id="<?php echo $req['id']; ?>">Back</button>
                <button class="btn-claim" data-id="<?php echo $req['id']; ?>">Claim</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Completed Column -->
    <div class="column" id="completed-column">
        <div class="completed-header">
    <h2 id="completed-title">Completed</h2>
    <input type="date" id="completed-date-picker" style="margin-left: 20px;">
</div>
        <div id="completed-list">
        <?php foreach($completed as $req): ?>
            <div class="card" id="req-<?php echo $req['id']; ?>">
                <span><strong>ID:</strong> <span class="value"><?php echo $req['id']; ?></span></span>
                <span><strong>Name:</strong> <span class="value"><?php echo htmlspecialchars($req['first_name'].' '.$req['last_name']); ?></span></span>
                <span><strong>Documents:</strong> <span class="value"><?php echo htmlspecialchars($req['documents']); ?></span></span>
                <span><strong>Notes:</strong> <span class="value"><?php echo htmlspecialchars($req['notes']); ?></span></span>
                <span><strong>Status:</strong> <span class="value"><?php echo htmlspecialchars($req['status']); ?></span></span>
                <span>Claimed / Completed</span>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="now_serving.js"></script>
<script>
  setInterval(() => location.reload(), 2000); // reload every 1.5 seconds
</script>

</body>
</html> 
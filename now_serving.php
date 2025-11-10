<?php
session_start();
include('db.php'); // PDO connection

// -------------------- AUTHENTICATION --------------------
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch user info
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'staff') {
    session_destroy();
    header("Location: index.php");
    exit();
}

$full_name = $user['first_name'] . ' ' . $user['last_name'];

// -------------------- STAFF DEPARTMENTS --------------------
$stmt = $pdo->prepare("SELECT department_id FROM staff_departments WHERE staff_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$staff_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($staff_departments))
    $staff_departments = [-1];
$inQuery = implode(',', array_fill(0, count($staff_departments), '?'));

// -------------------- SERVING --------------------
$stmt = $pdo->prepare("
    SELECT * FROM requests
    WHERE status='Serving'
      AND department IN ($inQuery)
    ORDER BY processing_start ASC
");
$stmt->execute($staff_departments);
$serving = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------- QUEUEING --------------------
$queueing = [];

foreach ($staff_departments as $dept) {
    // Check if someone is serving in this department
    $isServing = false;
    foreach ($serving as $s) {
        if ($s['department'] == $dept) {
            $isServing = true;
            break;
        }
    }

    // Fetch all queue items for today
    $stmt = $pdo->prepare("
        SELECT * FROM requests
        WHERE status='In Queue Now'
          AND claim_date IS NOT NULL
          AND DATE(claim_date) = CURDATE()
          AND department=?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$dept]);
    $deptQueue = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Auto-serve first queue item if no one is serving
    if (!$isServing && !empty($deptQueue)) {
        $first = array_shift($deptQueue); // remove first from queue
        $stmtUpdate = $pdo->prepare("
            UPDATE requests
            SET status='Serving', processing_start=NOW(), served_by=:staff_id, updated_at=NOW()
            WHERE id=:id
        ");
        $stmtUpdate->execute([
            ':staff_id' => $_SESSION['user_id'],
            ':id' => $first['id']
        ]);
        $first['status'] = 'Serving';
        $first['processing_start'] = date('Y-m-d H:i:s');
        $first['served_by'] = $_SESSION['user_id'];
        $serving[] = $first;
    }

    // Assign sequential queueing numbers and update DB
    $queueNum = 1;
    foreach ($deptQueue as &$q) {
        if (!isset($q['queueing_num']) || $q['queueing_num'] != $queueNum) {
            $q['queueing_num'] = $queueNum;
            $stmtUpdateQueue = $pdo->prepare("
                UPDATE requests
                SET queueing_num=:queueNum, updated_at=NOW()
                WHERE id=:id
            ");
            $stmtUpdateQueue->execute([
                ':queueNum' => $queueNum,
                ':id' => $q['id']
            ]);
        }
        $queueNum++;
    }

    $queueing = array_merge($queueing, $deptQueue);
}

// -------------------- COMPLETED --------------------
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
    <title>Now Serving</title>
    <link rel="stylesheet" href="staff_requests.css">
    <link rel="stylesheet" href="now_serving.css">
</head>

<body>
    <nav class="sidebar">
        <header>
            <div class="image-text"> <span class="image"><img src="assets/fatimalogo.jpg" alt="logo"></span>
                <div class="text header-text"> <span class="profession">Staff Dashboard</span> <span
                        class="name"><?php echo htmlspecialchars($full_name); ?></span> </div>
            </div>
            <hr>
        </header>
        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <li class="nav-link"><button class="tablinks"><a href="staff_dashboard.php"
                                class="tablinks">Dashboard</a></button></li>
                    <li class="nav-link"><button class="tablinks"><a href="staff_requests.php"
                                class="tablinks">Requests</a></button></li>
                    <li class="nav-link"><button class="tablinks"><a href="now_serving.php" class="tablinks">Now
                                Serving</a></button></li>
                    <li class="nav-link"><button class="tablinks"><a href="archive.php"
                                class="tablinks">Archive</a></button></li>
                </ul>
            </div>
            <div class="bottom-content">
                <li class="nav-link"><button class="tablinks"><a href="logout_user.php"
                            class="tablinks">Logout</a></button></li>
            </div>
        </div>
    </nav>

    <div class="container" data-department="<?php echo htmlspecialchars($staff_departments[0] ?? 0); ?>">

        <!-- Queueing Column -->
        <div class="column" id="queueing-column">
            <h2>Queueing</h2>
            <?php foreach ($queueing as $req): ?>
                <div class="card" id="req-<?php echo $req['id']; ?>">
                    <span><strong>ID:</strong> <?php echo $req['id']; ?></span>
                    <span><strong>Name:</strong>
                        <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></span>
                    <span><strong>Documents:</strong> <?php echo htmlspecialchars($req['documents']); ?></span>
                    <span><strong>Notes:</strong> <?php echo htmlspecialchars($req['notes']); ?></span>
                    <span><strong>Status:</strong> <?php echo htmlspecialchars($req['status']); ?></span>
                    <span><strong>Queue #:</strong> <?php echo $req['queueing_num']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Serving Column -->
<div class="column" id="serving-column">
    <h2>Serving</h2>
    <?php foreach($serving as $req): ?>
    <div class="card" id="req-<?php echo $req['id']; ?>">
        <span><strong>ID:</strong> <?php echo $req['id']; ?></span>
        <span><strong>Name:</strong> <?php echo htmlspecialchars($req['first_name'].' '.$req['last_name']); ?></span>
        <span><strong>Documents:</strong> <?php echo htmlspecialchars($req['documents']); ?></span>
        <span><strong>Notes:</strong> <?php echo htmlspecialchars($req['notes']); ?></span>
        <span><strong>Status:</strong> <?php echo htmlspecialchars($req['status']); ?></span>
        <span><strong>Queue #:</strong> 
            <?php 
                // Serving column Queue # = DB queueing_num + 1
                echo isset($req['queueing_num']) ? ($req['queueing_num'] + 1) : 1; 
            ?>
        </span>
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
                <?php foreach ($completed as $req): ?>
                    <div class="card" id="req-<?php echo $req['id']; ?>">
                        <span><strong>ID:</strong> <?php echo $req['id']; ?></span>
                        <span><strong>Name:</strong>
                            <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></span>
                        <span><strong>Documents:</strong> <?php echo htmlspecialchars($req['documents']); ?></span>
                        <span><strong>Notes:</strong> <?php echo htmlspecialchars($req['notes']); ?></span>
                        <span><strong>Status:</strong> <?php echo htmlspecialchars($req['status']); ?></span>
                        <span>Claimed / Completed</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <script src="now_serving.js"></script>
    <script>
        setInterval(() => location.reload(), 1500);
    </script>
</body>

</html>
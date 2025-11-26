<?php
include 'db.php';

// Fetch staff with counters
$staffList = $pdo->query("
    SELECT id, first_name, last_name, counter_no 
    FROM users
    WHERE role='staff' AND counter_no IS NOT NULL
    ORDER BY counter_no ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare serving data
$servingData = [];
foreach ($staffList as $staff) {
    $stmt = $pdo->prepare("
        SELECT queueing_num, status
        FROM requests
        WHERE served_by = ? AND status='Serving'
        ORDER BY updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([$staff['id']]);
    $servingData[$staff['counter_no']] = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Queue Display</title>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Montserrat', sans-serif;
    background-color: #eef2f5;
    margin: 0;
    padding: 0;
}

/* Header with Logo */
.header {
    background: #eef2f5;
    color: white;
    padding: 20px 0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.header-inner {
    width: 90%;
    margin: auto;
    max-width: 1400px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.school-logo {
    width: 70px;
    height: 70px;
    object-fit: contain;
    position: absolute;
    left: 0;
}

.header h1 {
    margin: 0;
    color: #28a745;
    font-size: 42px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Layout container */
.display-container {
    width: 90%;
    max-width: 1400px;
    margin: 40px auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 25px;
}

/* Counter Card */
.counter-card {
    background: white;
    padding: 25px 30px;
    border-radius: 18px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    text-align: center;
    border-right: 7px solid gray;
    transition: 0.2s ease;
}

.counter-card.serving {
    border-right-color: #28a745;
    border-left-color: #28a745;
    animation: pulseGlow 1.3s infinite;
}

@keyframes pulseGlow {
    0% { box-shadow: 0 0 0 rgba(40,167,69,0.3); }
    50% { box-shadow: 0 0 20px rgba(40,167,69,0.6); }
    100% { box-shadow: 0 0 0 rgba(40,167,69,0.3); }
}

.counter-number {
    font-size: 26px;
    font-weight: 700;
    color: #000000;
}

.staff-name {
    font-size: 22px;
    margin-top: 5px;
    font-weight: 600;
    color: #333;
}

/* Queue Number */
.queue-display {
    margin-top: 18px;
    font-size: 50px;
    font-weight: 800;
    color: #000000;
}   

/* Status Label */
.status {
    margin-top: 15px;
    font-size: 20px;
    padding: 8px 18px;
    display: inline-block;
    border-radius: 14px;
    color: white;
    font-weight: 700;
}

.status.serving {
    background: #28a745;
}

.status.idle {
    background: #6c757d;
}
</style>
</head>

<body>

<div class="header">
    <div class="header-inner">
        <img src="assets/logopng.png" class="school-logo" alt="School Logo">
        <h1>NOW SERVING</h1>
    </div>
</div>

<div class="display-container" id="displayContainer">
    <?php foreach ($staffList as $s): 
        $counterNo = htmlspecialchars($s['counter_no']);
        $staffName = htmlspecialchars($s['first_name'].' '.$s['last_name']);
        $queueNum = isset($servingData[$counterNo]['queueing_num']) ? htmlspecialchars($servingData[$counterNo]['queueing_num']) : '---';
        $status = isset($servingData[$counterNo]['status']) ? htmlspecialchars($servingData[$counterNo]['status']) : 'Idle';
        $isServing = ($status === 'Serving');
    ?>
    <div class="counter-card <?= $isServing ? 'serving' : '' ?>">
        <div class="counter-number">Counter <?= $counterNo ?></div>
        <div class="staff-name"><?= $staffName ?></div>
        <div class="queue-display"><?= $queueNum ?></div>
        <div class="status <?= $isServing ? 'serving' : 'idle' ?>"><?= $status ?></div>
    </div>
    <?php endforeach; ?>
</div>

<script>
// Auto refresh every 3 seconds
setInterval(() => {
    fetch(window.location.href, {cache: "no-cache"})
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, "text/html");
            const newDisplay = doc.querySelector("#displayContainer");
            document.querySelector("#displayContainer").innerHTML = newDisplay.innerHTML;
        });
}, 3000);
</script>

</body>
</html>

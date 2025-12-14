<?php
// Include the database connection safely
try {
    include('db.php');
    if (!isset($pdo)) {
        header("Location: index.php");
        exit();
    }
} catch (Exception $e) {
    header("Location: index.php");
    exit();
}

// Start the session
session_start();

// Check if the user_email session variable is set
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['user_email'];

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $user_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    header("Location: index.php");
    exit();
}

if (!$user) {
    header("Location: index.php");
    exit();
}

// Restrict access only to 'user' role
if ($user['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

// --- AUTO-GENERATE STUDENT NUM for NEW STUDENTS ---
if ((int) ($user['new_student'] ?? 0) === 1 && empty($user['student_num'])) {
    do {
        // Generate a 11-digit student number starting with 0322
        $student_num = '0322' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE student_num = :student_num");
        $stmt_check->execute([':student_num' => $student_num]);
        $exists = (int) $stmt_check->fetchColumn();
    } while ($exists > 0);

    // Save student_num and mark new_student as 0
    $stmt_update = $pdo->prepare("
        UPDATE users 
        SET student_num = :student_num, new_student = 0 
        WHERE email = :email
    ");
    $stmt_update->execute([
        ':student_num' => $student_num,
        ':email' => $user_email
    ]);

    // Update local variables
    $user['student_num'] = $student_num;
    $user['new_student'] = 0;

    // Flag to show the modal
    $show_new_student_modal = true;
}

// Assign user info to variables
$student_num = $user['student_num'];
$first_name = $user['first_name'];
$last_name = $user['last_name'];
$role = $user['role'];

// Fetch all documents and departments
try {
    $stmt = $pdo->query("SELECT * FROM documents ORDER BY name ASC");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM departments ORDER BY name ASC");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    header("Location: index.php");
    exit();
}

// Fetch all requests for "My Requests" table
$stmt = $pdo->prepare("SELECT id, created_at, documents, status, decline_reason, processing_time, claim_date, queueing_num
                        FROM requests 
                        WHERE first_name = :first_name AND last_name = :last_name 
                        ORDER BY created_at DESC");
$stmt->execute([
    ':first_name' => $first_name,
    ':last_name' => $last_name
]);
$my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch student's latest active request ---
$stmt = $pdo->prepare("
    SELECT id, department, queueing_num, serving_position, status, created_at
    FROM requests
    WHERE first_name = :first_name 
      AND last_name  = :last_name
      AND status IN ('In Queue Now','Serving')
    ORDER BY created_at DESC
    LIMIT 1
");

$stmt->execute([
    ':first_name' => $first_name,
    ':last_name' => $last_name
]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize queue variables
$queue_num = null;
$request_status = null;
$serving_position = null;
$position_in_line = null;
$estimated_time = null;
$student_department = null;
$currently_serving = null;
$is_your_turn = false;
$display_status = null;

if ($request && !empty($request['queueing_num'])) {
    $queue_num = (int) $request['queueing_num'];
    $request_status = $request['status'];
    $student_department = $request['department'];
    $display_status = $request_status;

    // Get currently serving student in department
    $stmt3 = $pdo->prepare("
        SELECT 
            r.id,
            r.queueing_num, 
            r.serving_position, 
            CONCAT(r.first_name, ' ', r.last_name) AS student_name,
            u.counter_no,
            CONCAT(u.first_name, ' ', u.last_name) AS staff_name
        FROM requests r
        LEFT JOIN users u ON r.served_by = u.id
        WHERE r.status = 'Serving'
          AND r.department = :dept
        ORDER BY r.serving_position ASC, r.queueing_num ASC
        LIMIT 1
    ");
    $stmt3->execute([':dept' => $student_department]);
    $currently_serving = $stmt3->fetch(PDO::FETCH_ASSOC);

    // Check if it's your turn
    if ($request_status === 'Serving' && !empty($currently_serving)) {
        if (
            ((int) $currently_serving['queueing_num'] === $queue_num) ||
            ((int) $currently_serving['id'] === (int) $request['id'])
        ) {
            $is_your_turn = true;
            $display_status = 'Serving';
        } else {
            $is_your_turn = false;
            $display_status = 'In Queue Now';
        }
    }

    // Calculate position and estimated time
    if ($is_your_turn) {
        $position_in_line = 0;
        $serving_position = (int) ($currently_serving['serving_position'] ?? 1);
        $estimated_time = "00:00:00";
    } else {
        if ($request_status === 'Pending') {
            $position_in_line = null;
            $serving_position = null;
            $estimated_time = null;
            $display_status = 'Pending';
        } else {
            if (!empty($request['serving_position'])) {
                $serving_position = (int) $request['serving_position'];
                $position_in_line = max(0, $serving_position - 1);
            } else {
                $stmt2 = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM requests 
                    WHERE department = :dept
                      AND queueing_num < :queue_num
                      AND status IN ('In Queue Now','Processing')
                ");
                $stmt2->execute([
                    ':dept' => $student_department,
                    ':queue_num' => $queue_num
                ]);
                $ahead = (int) $stmt2->fetchColumn();
                $position_in_line = $ahead;
                $serving_position = $ahead + 1;
            }

            $estimated_minutes = $position_in_line * 5;
            $estimated_time = gmdate("H:i:s", $estimated_minutes * 60);
        }
    }
} else {
    $display_status = 'None';
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!---------- UNICONS ---------->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!---------- CSS ---------->
    <link rel="stylesheet" href="user_dashboard.css">
    <!---------- FAVICON ---------->
    <link rel="icon" href="assets/profile.jpg" type="image/jpg">
    <!---------- TITLE ---------->
    <title>OLFU Queueing System</title>
</head>

<body>
    <?php if (!empty($show_new_student_modal)): ?>
        <div id="newStudentModal" style="position: fixed; top:0; left:0; width:100%; height:100%; 
     background: rgba(0,0,0,0.6); display:flex; justify-content:center; align-items:center; z-index:9999;">
            <div style="background:#fff; padding:30px; border-radius:12px; max-width:400px; width:90%; text-align:center; 
                box-shadow:0 8px 20px rgba(0,0,0,0.3); font-family:sans-serif;">
                <h2 style="font-size:1.75rem; font-weight:bold; color:#333; margin-bottom:12px;">
                    Welcome, <?= htmlspecialchars($user['first_name']) ?>!
                </h2>
                <p style="color:#555; font-size:1rem; margin-bottom:10px;">
                    Your student number has been generated:
                </p>
                <h3 style="color:#008C45; font-size:1.25rem; font-weight:600; margin-bottom:18px;">
                    <?= htmlspecialchars($user['student_num']) ?>
                </h3>
                <button onclick="document.getElementById('newStudentModal').style.display='none'" style="padding:10px 25px; background:#008C45; color:#fff; border:none; border-radius:8px; 
                       cursor:pointer; font-weight:500; transition: background 0.2s;"
                    onmouseover="this.style.background='#006837';" onmouseout="this.style.background='#008C45';">
                    OK
                </button>
            </div>
        </div>
    <?php endif; ?>
    <div class="container">
        <!---------- HEADER ---------->
        <nav id="header">
            <div class="nav-logo" href="#home" onclick="scrollToHome()">
                <p class="nav-name"><span>Welcome</span> <?php echo htmlspecialchars($first_name); ?>!</p>
            </div>
            <div class="nav-menu" id="navMenu">
                <ul class="nav_menu_list">
                    <li class="nav_list">
                        <a class="nav-link active-link home" onclick="scrollToHome()">Home</a>
                    </li>
                    <li class="nav_list">
                        <a class="nav-link about" onclick="scrollToAbout()">Form</a>
                    </li>
                    <li class="nav_list">
                        <a class="nav-link services" onclick="scrollToServices()">Queue</a>
                    </li>
                    <li class="nav_list">
                        <a class="nav-link contact" onclick="scrollToContact()">Contact</a>
                    </li>
                    <li class="nav_list">
                        <a class="nav-link logout" href="logout_user.php">Logout</a>
                    </li>

                </ul>
            </div>
            <div class="nav-menu-btn">
                <i class="uil uil-bars" id="toggleBtn" onclick="myMenuFunction()"></i>
            </div>
        </nav>
        <!---------- MAIN ---------->
        <main class="wrapper">
            <!---------- LANDING PAGE ---------->
            <section class="landing-page" id="home">
                <div class="feature-text">
                    <div class="featured-name">
                        <p>Registrar <span>Hours:</span></p>
                    </div>
                    <div class="featured-text-info">
                        <p id="registrar-status">Registrar is: [Status "closed" or "open"]</p><br>
                        <p id="opening-hours">Opening Hours: </p><br>
                        <p id="lunch-hours">Lunch Break: </p>
                    </div>
                    <div class="featured-text-btn">
                        <button id="queue-btn" class="btn blue-btn" onclick="window.location.href='#about';">Get
                            Queueing Now!</button>
                    </div>
                </div>

                <div class="scroll-btn" onclick="scrollToAbout()">
                    <i class="fa-solid fa-angle-down"></i>
                </div>
            </section>

            <script>
                function updateRegistrarStatus() {
                    const statusEl = document.getElementById("registrar-status");
                    const openingEl = document.getElementById("opening-hours");
                    const lunchEl = document.getElementById("lunch-hours");
                    const queueBtn = document.getElementById("queue-btn");

                    const now = new Date();
                    const hours = now.getHours();
                    let statusText = "Closed";

                    // Determine status
                    if (hours >= 8 && hours < 12) {
                        statusText = "Open";
                        queueBtn.disabled = false;
                    } else if (hours === 12) {
                        statusText = "Lunch Break";
                        queueBtn.disabled = true;
                    } else if (hours >= 13 && hours < 17) {
                        statusText = "Open";
                        queueBtn.disabled = false;
                    } else {
                        statusText = "Closed";
                        queueBtn.disabled = true;
                    }

                    statusEl.textContent = `Registrar is: ${statusText}`;
                    openingEl.textContent = "Opening Hours: 8:00 AM - 12:00 PM, 1:00 PM - 5:00 PM";
                    lunchEl.textContent = "Lunch Break: 12:00 PM - 1:00 PM";
                }

                // Run on page load
                updateRegistrarStatus();
                // Refresh every minute
                setInterval(updateRegistrarStatus, 60000);
            </script>


            <!---------- FORM ---------->
            <section class="section" id="about">
                <div class="top-header">
                    <h1>Submit a <span>Request</span></h1>
                    <p>Please select all that apply. <b>You can only submit one request at a time.</b></p>
                </div>

                <div class="about-info">
                    <!-- ✅ Start the form here -->
                    <form method="POST" action="submit_request.php" enctype="multipart/form-data">

                        <div class="info-columns">
                            <div class="info-left">
                                <label>First Name:</label>
                                <input type="text" name="first_name"
                                    value="<?php echo htmlspecialchars($first_name); ?>" readonly>

                                <label>Student Number:</label>
                                <input type="text" name="student_number"
                                    value="<?php echo htmlspecialchars($student_num); ?>" readonly>


                                <label>Last School Year Attended:</label>
                                <select name="last_school_year">
                                    <option value="">-- Select School Year --</option>
                                    <option value="2020-2021">2020-2021</option>
                                    <option value="2021-2022">2021-2022</option>
                                    <option value="2022-2023">2022-2023</option>
                                    <option value="2023-2024">2023-2024</option>
                                    <option value="2024-2025">2024-2025</option>
                                </select>

                                <label>Last Semester Attended:</label>
                                <select name="last_semester">
                                    <option value="">-- Select Semester --</option>
                                    <option value="First Semester">First Semester</option>
                                    <option value="Second Semester">Second Semester</option>
                                    <option value="Third Semester">Third Semester</option>
                                </select>
                            </div>

                            <div class="info-right">
                                <label>Last Name:</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>"
                                    readonly>

                                <label>Section:</label>
                                <input type="text" name="section" placeholder="e.g., BSIT 1-Y1-2">

                                <!-- ✅ Department Dropdown -->
                                <label>Department:</label>
                                <select name="department" required>
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['id']); ?>">
                                            <?= htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            </div>
                        </div>

                        <h3>Documents for Request</h3>
                        <div class="document-columns">
                            <?php
                            // Split documents into two halves for left and right columns
                            $half = ceil(count($documents) / 2);
                            $columns = [
                                array_slice($documents, 0, $half),       // Left
                                array_slice($documents, $half)           // Right
                            ];
                            ?>

                            <?php foreach ($columns as $index => $docs): ?>
                                <div class="document-<?php echo $index === 0 ? 'left' : 'right'; ?>">
                                    <?php foreach ($docs as $doc):
                                        $days = (int) $doc['processing_days'];
                                        $claim_date = date('F j, Y', strtotime("+$days days"));
                                        ?>
                                        <label class="doc-checkbox"
                                            title="Processing: <?= $days; ?> day(s) • Claim on <?= $claim_date; ?>">
                                            <input type="checkbox" name="documents[]"
                                                value="<?= htmlspecialchars($doc['name']); ?>"
                                                data-name="<?= htmlspecialchars($doc['name']); ?>"
                                                data-fee="<?= htmlspecialchars($doc['fee'] ?? 0); ?>" data-days="<?= $days; ?>">
                                            <span><?= htmlspecialchars($doc['name']); ?> (<?= $days; ?>d)</span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>


                        <!-- ✅ File Upload -->
                        <div class="upload-section">
                            <label for="attachment">Upload Receipt (Images/PDFs):</label>
                            <input type="file" name="attachment[]" id="attachment" accept=".jpg,.jpeg,.png,.pdf"
                                multiple>
                        </div>


                        <div class="notes-section">
                            <label id="notes">Receipt Details:</label>
                            <textarea name="notes" rows="4"
                                placeholder="Write any additional concerns here..."></textarea>
                        </div>

                        <button type="submit" id="submit-form">Submit</button>
                    </form>
                </div>

                <!---------- MODAL POPUP FOR FORM ---------->
                <div id="modal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h3>Confirm Your Details</h3><br>

                        <p><strong>First Name:</strong> <span id="modal_first_name"></span></p>
                        <p><strong>Last Name:</strong> <span id="modal_last_name"></span></p>
                        <p><strong>Student Number:</strong> <span id="modal_student_number"></span></p>
                        <p><strong>Section:</strong> <span id="modal_section"></span></p>
                        <p><strong>Department:</strong> <span id="modal_department"></span></p>
                        <p><strong>Last School Year Attended:</strong> <span id="modal_last_school_year"></span></p>
                        <p><strong>Last Semester Attended:</strong> <span id="modal_last_semester"></span></p>

                        <p><strong>Documents:</strong></p>
                        <ul id="modal_documents"></ul>

                        <p><strong>Notes / Other Concerns:</strong></p>
                        <p id="modal_notes"></p>

                        <p><strong>Uploaded File:</strong> <span id="modal_file"></span></p>

                        <button id="final-submit">Confirm & Submit</button>
                    </div>
                </div>


                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div id="flashModal" style="position: fixed; top:0; left:0; width:100%; height:100%;
     background: rgba(0,0,0,0.6); display:flex; justify-content:center; align-items:center; 
     z-index:9999; font-family:sans-serif;">
                        <div style="background:#fff; padding:30px; border-radius:12px; max-width:400px; width:90%; text-align:center;
                box-shadow:0 8px 20px rgba(0,0,0,0.3); animation: fadeIn 0.3s ease;">
                            <h2 style="font-size:1.5rem; font-weight:bold; color:#333; margin-bottom:12px;">Notification
                            </h2>
                            <p style="color:#555; font-size:1rem; margin-bottom:18px;">
                                <?= htmlspecialchars($_SESSION['flash_message']['text']); ?>
                            </p>
                            <button onclick="document.getElementById('flashModal').remove()" style="padding:10px 25px; background:#008C45; color:#fff; border:none; border-radius:8px; 
                       cursor:pointer; font-weight:500; transition: background 0.2s;"
                                onmouseover="this.style.background='#006837';"
                                onmouseout="this.style.background='#008C45';">
                                OK
                            </button>
                        </div>
                    </div>
                    <style>
                        @keyframes fadeIn {
                            from {
                                opacity: 0;
                                transform: scale(0.95);
                            }

                            to {
                                opacity: 1;
                                transform: scale(1);
                            }
                        }
                    </style>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>


                <section class="section" id="my-requests">
                    <div class="top-header">
                        <h1>My <span>Requests</span></h1>
                        <p>Here are all the requests you have submitted.</p>
                    </div>

                    <div class="table-container">
                        <?php if (!empty($my_requests)): ?>
                            <table class="request-table">
                                <thead>
                                    <tr>
                                        <th>Date Requested</th>
                                        <th>Documents Requested</th>
                                        <th>Status</th>
                                        <th>Remarks / Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_requests as $req): ?>
                                        <tr>
                                            <!-- Date -->
                                            <td><?php echo htmlspecialchars(date("M d, Y h:i A", strtotime($req['created_at']))); ?>
                                            </td>

                                            <!-- Documents -->
                                            <td><?php echo htmlspecialchars($req['documents']); ?></td>

                                            <!-- Status -->
                                            <td>
                                                <?php
                                                switch ($req['status']) {
                                                    case 'Pending':
                                                        echo '<span style="color: orange; font-weight: bold; padding: 4px 8px; border-radius: 5px; background-color: #fff3e0;">Pending</span>';
                                                        break;
                                                    case 'Processing':
                                                        echo '<span style="color: blue; font-weight: bold; padding: 4px 8px; border-radius: 5px; background-color: #e0f0ff;">Processing</span>';
                                                        break;
                                                    case 'To Be Claimed':
                                                        echo '<span style="color: purple; font-weight: bold; padding: 4px 8px; border-radius: 5px; background-color: #f3e6ff;">To Be Claimed</span>';
                                                        break;
                                                    case 'Serving':
                                                        echo '<span style="color: teal; font-weight: bold; padding: 4px 8px; border-radius: 5px; background-color: #e6f9f9;">Serving</span>';
                                                        break;
                                                    case 'Completed':
                                                        echo '<span style="color: green; font-weight: bold; padding: 4px 8px; border-radius: 5px; background-color: #e6f9e6;">Completed</span>';
                                                        break;
                                                    case 'Declined':
                                                        echo '<span style="color: red; font-weight: bold; padding: 4px 8px; border-radius: 5px; background-color: #ffe6e6;">Declined</span>';
                                                        break;
                                                    case 'In Queue Now':
                                                        echo '<span class="queue-status">In Queue Now<span class="dots"></span></span>';
                                                        break;
                                                    default:
                                                        echo htmlspecialchars($req['status']);
                                                }
                                                ?>
                                            </td>


                                            <!-- Remarks / Action -->
                                            <td>
                                                <?php if ($req['status'] === 'Pending'): ?>
                                                    <!-- Blank for pending -->

                                                <?php elseif ($req['status'] === 'Declined'): ?>
                                                    <?php echo htmlspecialchars($req['decline_reason'] ?? 'No attachment/s provided'); ?>

                                                <?php elseif ($req['status'] === 'Processing'): ?>
                                                    <?php if (!empty($req['processing_time'])): ?>
                                                        Estimated ready by:
                                                        <?php echo htmlspecialchars(date("M d, Y h:i A", strtotime($req['processing_time']))); ?>
                                                    <?php else: ?>
                                                        No estimated time set
                                                    <?php endif; ?>

                                                <?php elseif ($req['status'] === 'To Be Claimed'): ?>
                                                    <!-- Claim Now Button -->
                                                    <form method="POST" action="claim_request.php" style="display:inline;">
                                                        <input type="hidden" name="request_id" value="<?= $req['id']; ?>">
                                                        <button type="submit"
                                                            style="margin-left:10px; padding:6px 12px; background-color:#4CAF50; color:white; border:none; border-radius:5px; cursor:pointer;">
                                                            Claim Now
                                                        </button>
                                                    </form>

                                                <?php elseif ($req['status'] === 'Completed'): ?>
                                                    Claimed
                                                <?php endif; ?>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!---------- QUEUE NUMBER ---------->
                <section class="section" id="services">
                    <div class="top-header">
                        <h1>Your Queue Number</h1>
                    </div>

                    <div class="service-container">
                        <!-- Your queue info -->
                        <div class="service-box">
                            <?php
                            // Defensive checks
                            $queue_num = $queue_num ?? null;
                            $request_status = $request_status ?? null;
                            $display_status = $display_status ?? null;
                            $is_your_turn = $is_your_turn ?? false;
                            $position_in_line = $position_in_line ?? null;
                            $student_number = $student_number ?? null; // required for PDF
                            ?>

                            <?php if ($display_status === 'Pending' || $queue_num === null): ?>
                                <label>Your queue number is <b>N/A</b></label>
                                <p style="color: gray; font-weight: bold; margin-top: 10px;">
                                    📌 Your request is still pending approval.
                                </p>

                            <?php elseif ($is_your_turn && $display_status === 'Serving'): ?>
                                <label>Your queue number is</label>
                                <h3><?php echo htmlspecialchars((string) $queue_num); ?></h3>

                                <p style="color: green; font-weight: bold; margin-top: 10px;">
                                    🎉 It’s your turn now! Please proceed to the counter.
                                </p>

                                <!-- PDF DOWNLOAD BUTTON -->
                                <a href="download_stub.php?queue=<?php echo urlencode($queue_num); ?>&student=<?php echo urlencode($student_number); ?>"
                                    style="margin-top: 10px; display: inline-block; background: #008c45; padding: 2px 5px; color: white; 
                          border-radius: 6px; font-weight: bold; text-decoration: none;">
                                    📄 Download Queue Stub (PDF)
                                </a>

                            <?php elseif ($display_status === 'In Queue Now'): ?>
                                <label>Your queue number is</label>
                                <h3><?php echo htmlspecialchars((string) $queue_num); ?></h3>

                                <!-- PDF DOWNLOAD BUTTON -->
                                <a href="download_stub.php?queue=<?php echo urlencode($queue_num); ?>&student=<?php echo urlencode($student_number); ?>"
                                    style="margin-top: 10px; display: inline-block; background: #008c45; padding: 2px 5px; color: white; 
                          border-radius: 6px; font-weight: bold; text-decoration: none;">
                                    📄 Download Queue Stub (PDF)
                                </a>

                                <p style="color: orange; font-weight: bold; margin-top: 10px;">
                                    ⏳ Position in line:
                                    <?php echo ($position_in_line !== null) ? ((int) $position_in_line + 1) : 1; ?>
                                </p>

                            <?php elseif ($display_status === 'To Be Claimed'): ?>
                                <label>Your queue number is <b>N/A</b></label>

                                <!-- PDF DOWNLOAD BUTTON (still allowed) -->
                                <a href="download_stub.php?queue=<?php echo urlencode($queue_num); ?>&student=<?php echo urlencode($student_number); ?>"
                                    style="margin-top: 10px; display: inline-block; background: #008c45; padding: 2px 5px; color: white; 
                          border-radius: 6px; font-weight: bold; text-decoration: none;">
                                    📄 Download Queue Stub (PDF)
                                </a>

                                <p style="color: blue; font-weight: bold; margin-top: 10px;">
                                    📍 Please go to the office to claim your document.
                                </p>

                            <?php elseif ($display_status === 'Completed'): ?>
                                <label>Your queue number is <b>N/A</b></label>

                            <?php elseif ($display_status === 'Declined'): ?>
                                <label>Your queue number is <b>N/A</b></label>

                            <?php else: ?>
                                <label>Your queue number is <b>N/A</b></label>
                            <?php endif; ?>
                        </div>

                        <!-- Currently serving info -->
                        <div class="service-box">
                            <?php if (!empty($currently_serving) && is_array($currently_serving)): ?>
                                <label><b>Now Serving Queue Number:</b></label>
                                <h3><?php echo htmlspecialchars((string) ($currently_serving['queueing_num'] ?? 'N/A')); ?>
                                </h3>
                                <p>
                                    Counter:
                                    <b><?php echo htmlspecialchars((string) ($currently_serving['counter_no'] ?? 'N/A')); ?></b><br>
                                    Staff:
                                    <b><?php echo htmlspecialchars($currently_serving['staff_name'] ?? 'Unknown'); ?></b>
                                </p>
                            <?php else: ?>
                                <label><b>Now Serving:</b></label>
                                <h3>N/A</h3>
                                <p>No one is being served right now.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <audio id="queueNotif" src="assets/notif.mp3" preload="auto"></audio>
                <style>
                    /* Flash message modal */
                    #turnModal {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.6);
                        display: none;
                        /* hidden by default */
                        align-items: center;
                        justify-content: center;
                        z-index: 9999;
                    }

                    #turnModal .box {
                        background: white;
                        padding: 25px;
                        border-radius: 10px;
                        text-align: center;
                        max-width: 300px;
                    }

                    #turnModal button {
                        margin-top: 15px;
                        padding: 10px 20px;
                        border: none;
                        background: #4CAF50;
                        color: white;
                        border-radius: 8px;
                        cursor: pointer;
                    }
                </style>

                <!-- Flash popup -->
                <div id="turnModal">
                    <div class="box">
                        <h3>🎉 It's Your Turn!</h3>
                        <p>Please proceed to the counter.</p>
                        <button id="closeNotif">Okay</button>
                    </div>
                </div>

                <script>
                    const isYourTurn = <?php echo json_encode($is_your_turn); ?>;
                    const status = <?php echo json_encode($display_status); ?>;

                    const audio = document.getElementById("queueNotif");
                    const modal = document.getElementById("turnModal");
                    const closeBtn = document.getElementById("closeNotif");

                    // Hard stop audio (strongest method)
                    function stopSound() {
                        audio.pause();
                        audio.currentTime = 0;
                        audio.loop = false;
                        console.log("Sound STOPPED");
                    }

                    // Show modal + sound
                    if (isYourTurn && status === "Serving") {
                        modal.style.display = "flex";
                        audio.loop = true;

                        audio.play().catch(() => {
                            console.warn("Autoplay blocked. Will wait for click.");
                        });

                        document.addEventListener("click", () => audio.play(), { once: true });
                    }

                    // Auto-refresh queue section only
                    function refreshQueueSection() {
                        fetch(window.location.href, { cache: "no-store" })
                            .then(r => r.text())
                            .then(html => {
                                const doc = new DOMParser().parseFromString(html, "text/html");
                                const newSection = doc.querySelector("#services");
                                const currentSection = document.querySelector("#services");

                                if (newSection && currentSection) {
                                    currentSection.innerHTML = newSection.innerHTML;
                                }

                                // Ensure sound is OFF after refresh
                                stopSound();
                            });
                    }

                    // When clicking OK button
                    closeBtn.addEventListener("click", () => {
                        stopSound();                 // ⬅️ Stop first
                        modal.style.display = "none";

                        // Start auto-refreshing
                        setInterval(refreshQueueSection, 5000);
                    });
                </script>





                <!---------- CONTACT ---------->
                <section class="section" id="contact">
                    <div class="top-header">
                        <h1>Get in touch</h1>
                        <span>Have other concerns? Let's connect.</span>
                    </div>
                    <div class="row">
                        <div class="col contact-info">
                            <h2>Find Us</h2>
                            <p><b>Antipolo Online Concierge</b></p>
                            <p>Meeting ID: 965 9850 1717</p>
                            <p>Password: 557028</p>
                            <div class="contact-social-icons">
                                <a href="https://www.facebook.com/our.lady.of.fatima.university" class="icon"><i
                                        class='uil uil-facebook-f'></i></a>
                                <a href="https://www.instagram.com/fatimauniversity/" class="icon"><i
                                        class='uil uil-instagram'></i></a>
                                <a href="https://www.youtube.com/channel/UC1xRi6L2EBtkWvVdmkNHYEg" class="icon"><i
                                        class='uil uil-youtube'></i></a>
                                <a href="https://www.linkedin.com/school/our-lady-of-fatima-university/" class="icon"><i
                                        class='uil uil-linkedin-alt'></i></a>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form">
                                <div class="form-inputs">
                                    <input type="text" class="input-field" placeholder="Name"
                                        value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>"
                                        readonly>
                                    <input type="email" class="input-field" placeholder="Email">
                                </div>
                                <div class="text-area">
                                    <textarea placeholder="Message"></textarea>
                                </div>
                                <div class="form-button">
                                    <button class="btn">Send<i class="uil uil-message"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
        </main>
        <!---------- FOOTER ---------->
        <footer>
            <div class="top-footer">
                <p>OLFU</p>
            </div>
            <div class="middle-footer">
                <ul class="footer-menu">
                    <li class="footer_menu_list">
                        <a onclick="scrollToHome()">Home</a>
                        <a onclick="scrollToAbout()">Form</a>
                        <a onclick="scrollToServices()">Queue</a>
                        <a onclick="scrollToContact()">Contact</a>
                    </li>
                </ul>
            </div>
            <div class="bottom-footer">
                <p>Copyright &copy; <a href="#home" style="text-decoration: none;">OLFU</a></p>
            </div>
        </footer>
    </div>
    <!---------- SCROLL REVEAL JS LINK ---------->
    <script src="https://unpkg.com/scrollreveal"></script>
    <!---------- MAIN JS ---------->
    <script src="user_dashboard.js"></script>
    <!-- ✅ Document Info Modal & Total Fee -->
    <script>
        const infoModal = document.createElement("div");
        infoModal.id = "docInfoModal";
        infoModal.innerHTML = `
  <div class="modal-overlay"></div>
  <div class="modal-window">
    <h3 id="docTitle" class="doc-title">Document Info</h3>
    <div class="doc-details">
      <p id="docNote" class="doc-line"></p>
      <p id="docFee" class="doc-line"></p>
      <p id="docLost" class="doc-line lost-note"></p>
    </div>
    <button id="closeDocModal">OK</button>
  </div>`;
        document.body.appendChild(infoModal);

        const style = document.createElement("style");
        style.innerHTML = `
  #docInfoModal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    font-family: 'Segoe UI', sans-serif;
  }
  .modal-window {
    background: #fff;
    padding: 25px 30px 20px;
    border-radius: 12px;
    max-width: 420px;
    width: 90%;
    text-align: left;
    box-shadow: 0 6px 18px rgba(0,0,0,0.25);
    line-height: 1.6;
  }
  .doc-title {
    font-size: 1.3rem;
    margin-bottom: 12px;
    color: #222;
  }
  .doc-details p {
    margin: 8px 0;
    padding: 8px 12px;
    background: #f9f9f9;
    border-radius: 6px;
  }
  .doc-line {
    font-size: 0.95rem;
  }
  .lost-note {
    background: #fff8e5;
    border-left: 4px solid #ffcc00;
    color: #7a5b00;
    font-size: 0.9rem;
  }
  .modal-window button {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    float: right;
    margin-top: 10px;
    font-weight: 500;
  }
  .modal-window button:hover {
    background: #45a049;
  }
`;
        document.head.appendChild(style);

        let totalFee = 0;

        document.querySelectorAll('input[name="documents[]"]').forEach(box => {
            box.addEventListener('change', () => {
                const doc = box.dataset.name;
                const fee = parseFloat(box.dataset.fee);
                const days = box.dataset.days || "1";

                if (box.checked) {
                    totalFee += fee;

                    document.getElementById("docTitle").textContent = doc;
                    document.getElementById("docNote").innerHTML = `🕓 <b>Processing time:</b> ${days} day(s)`;

                    if (fee && fee > 0) {
                        document.getElementById("docFee").innerHTML = `💰 <b>Fee:</b> ₱${fee.toFixed(2)} — must be paid before request.`;
                    } else {
                        document.getElementById("docFee").innerHTML = `✅ <b>Can be requested</b> without paying any fee.`;
                    }

                    document.getElementById("docLost").innerHTML =
                        `⚠️ <b>Note:</b> If this document was previously issued and is now lost, 
        an <b>Affidavit of Loss</b> is required for re-request.`;

                    infoModal.style.display = "flex";
                } else {
                    totalFee -= fee;
                }

                document.getElementById("total-fee").textContent = `Total Fee: ₱${totalFee.toFixed(2)}`;
            });
        });

        document.addEventListener("click", e => {
            if (e.target.id === "closeDocModal" || e.target.classList.contains("modal-overlay")) {
                infoModal.style.display = "none";
            }
        });
    </script>
    <style>
#flashModalOverlay {
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.6);
    backdrop-filter: blur(2px);
    justify-content:center;
    align-items:center;
    z-index:999999;
}
#flashModal {
    background:#fff;
    padding:25px;
    width:350px;
    border-radius:10px;
    text-align:center;
    box-shadow:0 0 20px rgba(0,0,0,0.2);
}
#flashModal h2 {
    margin-bottom:10px;
}
#flashModal button {
    margin-top:20px;
    padding:10px 20px;
    border:none;
    background:#008c45;
    color:#fff;
    border-radius:6px;
    cursor:pointer;
}

</style>

<div id="flashModalOverlay">
    <div id="flashModal">
        <h2 id="flashTitle">Notification</h2>
        <p id="flashMessage"></p>
        <button onclick="closeFlash()">OK</button>
    </div>
</div>

<script>
function showFlash(type, msg) {
    document.getElementById("flashTitle").innerText =
        type === "error" ? "Error" :
        type === "success" ? "Success" :
        "Notice";

    document.getElementById("flashMessage").innerText = msg;
    document.getElementById("flashModalOverlay").style.display = "flex";
}

function closeFlash() {
    document.getElementById("flashModalOverlay").style.display = "none";
    window.location.href = "user_dashboard.php";
}
</script>

<?php if (!empty($_SESSION["flash"])): ?>
<script>
    showFlash("<?= $_SESSION['flash']['type'] ?>", "<?= $_SESSION['flash']['msg'] ?>");
</script>
<?php unset($_SESSION["flash"]); endif; ?>


</body>

</html>
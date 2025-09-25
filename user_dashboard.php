    <?php
    // Include the database connection safely
    try {
        include('db.php');
        if (!isset($pdo)) {
            // If $pdo is not set, redirect
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
    if (isset($_SESSION['user_email'])) {
        $user_email = $_SESSION['user_email'];

        try {
            $stmt = $pdo->prepare("SELECT first_name, last_name, email, role FROM users WHERE email = :email");
            $stmt->execute(['email' => $user_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            header("Location: index.php");
            exit();
        }

        if ($user) {
            $first_name = $user['first_name'];
            $last_name  = $user['last_name'];
            $role       = $user['role'];

            // ✅ Restrict access only to 'user' role
            if ($role !== 'user') {
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
        ':last_name'  => $last_name
    ]);
    $my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);



    // ✅ Fetch latest active request for queue display
    $queue_num = null;
    $position_in_line = null;
    $estimated_time = null;

    $stmt = $pdo->prepare("
        SELECT id, queueing_num, status, created_at
        FROM requests
        WHERE first_name = :first_name 
        AND last_name = :last_name
        AND status IN ('Pending','Processing','To Be Claimed','Serving')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':first_name' => $first_name,
        ':last_name'  => $last_name
    ]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        $queue_num = $request['queueing_num'];

        // Find how many are ahead in line
        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) 
            FROM requests 
            WHERE queueing_num < :queue_num
            AND status IN ('Pending','Processing','Serving')
        ");
        $stmt2->execute([':queue_num' => $queue_num]);
        $position_in_line = $stmt2->fetchColumn();

        // Example: assume 5 minutes per request
        $estimated_minutes = $position_in_line * 5;
        $estimated_time = gmdate("H:i:s", $estimated_minutes * 60);
    }

    // === queue / serving info for latest active request ===
    $queue_num = null;
    $serving_position = null;
    $position_in_line = null;
    $estimated_time = null;

    $stmt = $pdo->prepare("
        SELECT id, queueing_num, serving_position, status, processing_start, processing_end, created_at
        FROM requests
        WHERE first_name = :first_name
        AND last_name = :last_name
        AND status IN ('Pending','Processing','To Be Claimed','Serving')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':first_name' => $first_name,
        ':last_name'  => $last_name
    ]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request && !empty($request['queueing_num'])) {
        $queue_num = (int)$request['queueing_num'];

        // Prefer serving_position if stored in DB
        if (!empty($request['serving_position'])) {
            $serving_position = (int)$request['serving_position'];
            $position_in_line = max(0, $serving_position - 1);
        } else {
            // Calculate based on queue number if serving_position is missing
            $stmt2 = $pdo->prepare("
                SELECT COUNT(*) 
                FROM requests 
                WHERE queueing_num < :queue_num
                AND status IN ('Pending','Processing','Serving')
            ");
            $stmt2->execute([':queue_num' => $queue_num]);
            $ahead = (int)$stmt2->fetchColumn();

            $position_in_line = $ahead;
            $serving_position = $ahead + 1;
        }

        // Example: estimate 5 minutes per request
        $estimated_minutes = $position_in_line * 5;
        $estimated_time = gmdate("H:i:s", $estimated_minutes * 60);
    } else {
        // No active request → reset to null
        $queue_num = null;
        $serving_position = null;
        $position_in_line = null;
        $estimated_time = null;
    }


    // Fetch the currently serving request (based on serving_position, not id)
    $stmt = $pdo->prepare("
        SELECT queueing_num, serving_position, CONCAT(first_name, ' ', last_name) AS name
        FROM requests
        WHERE status = 'Serving'
        AND queueing_num IS NOT NULL
        ORDER BY serving_position ASC, queueing_num ASC
        LIMIT 1
    ");
    $stmt->execute();
    $currently_serving = $stmt->fetch(PDO::FETCH_ASSOC);



    // 👇 Add this helper function anywhere after session_start()
    function ordinal($number) {
        if (!is_numeric($number)) return $number;
        $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13))
            return $number. 'th';
        else
            return $number. $ends[$number % 10];
    }
// Fetch the currently serving request
$stmt = $pdo->prepare("
    SELECT 
        r.queueing_num, 
        r.serving_position, 
        CONCAT(r.first_name, ' ', r.last_name) AS student_name,
        u.counter_no,
        CONCAT(u.first_name, ' ', u.last_name) AS staff_name
    FROM requests r
    LEFT JOIN users u ON r.served_by = u.id
    WHERE r.status = 'Serving'
    ORDER BY r.serving_position ASC, r.queueing_num ASC
    LIMIT 1
");
$stmt->execute();
$currently_serving = $stmt->fetch(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!---------- UNICONS ----------> 
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!---------- CSS ----------> 
    <link rel="stylesheet" href="user_dashboard.css">
    <!---------- FAVICON ----------> 
    <link rel="icon" href="assets/profile.jpg" type="image/jpg">
    <!---------- TITLE ----------> 
    <title>OLFU Queueing System</title>
</head>
<body>
     <!-- Background Music -->
    <audio id="loginMusic" autoplay loop hidden>
    <source src="assets/risetothetop.mp3" type="audio/mpeg">
    Your browser does not support the audio element.
</audio>

<!-- 🎵 Music Control Widget -->
<div id="music-control">
    <span id="song-title">🎵 Now Playing: Rise to the Top</span>
    <button id="mute-btn">Mute</button>
    <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.1">
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const music = document.getElementById("loginMusic");
    const muteBtn = document.getElementById("mute-btn");
    const volumeSlider = document.getElementById("volume-slider");

    // ✅ Set default volume to 20%
    music.volume = 0.2;

    // Try autoplay
    music.play().catch(() => {
        document.body.addEventListener("click", () => {
            music.play();
        }, { once: true });
    });

    // Mute / Unmute toggle
    muteBtn.addEventListener("click", () => {
        if (music.muted) {
            music.muted = false;
            muteBtn.textContent = "Mute";
            volumeSlider.value = music.volume;
        } else {
            music.muted = true;
            muteBtn.textContent = "Unmute";
        }
    });

    // Volume control
    volumeSlider.addEventListener("input", () => {
        music.volume = volumeSlider.value;
        if (music.volume === 0) {
            music.muted = true;
            muteBtn.textContent = "Unmute";
        } else {
            music.muted = false;
            muteBtn.textContent = "Mute";
        }
    });
});
</script>


    <style>
        /* 🎵 Music Control Styles */
        #music-control {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 10px 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            z-index: 9999;
        }

        #mute-btn {
            background: #ff5252;
            border: none;
            color: white;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        #mute-btn:hover {
            background: #ff3030;
        }

        /* 🎚️ Hide slider by default */
        #volume-slider {
            width: 100px;
            cursor: pointer;
            display: none;
        }

        /* Show slider when hovering over the music control */
        #music-control:hover #volume-slider {
            display: inline-block;
        }
    </style>


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
            <button id="queue-btn" class="btn blue-btn" onclick="window.location.href='#about';">Get Queueing Now!</button>
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
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" readonly>

                    <label>Student Number:</label>
                    <input type="text" name="student_number">

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
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" readonly>

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
    <div class="document-left">
        <?php foreach (array_slice($documents, 0, ceil(count($documents)/2)) as $doc): ?>
            <?php 
                $days = (int)$doc['processing_days']; 
                $claim_date = date('F j, Y', strtotime("+$days days"));
            ?>
            <label class="doc-checkbox" title="Processing: <?= $days; ?> day(s) • Claim on <?= $claim_date; ?>">
                <input type="checkbox" name="documents[]" value="<?= htmlspecialchars($doc['name']); ?>">
                <span><?= htmlspecialchars($doc['name']); ?> (<?= $days; ?>d)</span>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="document-right">
        <?php foreach (array_slice($documents, ceil(count($documents)/2)) as $doc): ?>
            <?php 
                $days = (int)$doc['processing_days']; 
                $claim_date = date('F j, Y', strtotime("+$days days"));
            ?>
            <label class="doc-checkbox" title="Processing: <?= $days; ?> day(s) • Claim on <?= $claim_date; ?>">
                <input type="checkbox" name="documents[]" value="<?= htmlspecialchars($doc['name']); ?>">
                <span><?= htmlspecialchars($doc['name']); ?> (<?= $days; ?>d)</span>
            </label>
        <?php endforeach; ?>
    </div>
</div>

            <!-- ✅ File Upload -->
                <div class="upload-section">
                    <label for="attachment">Upload Attachments (Images/PDFs):</label>
                    <input type="file" name="attachment[]" id="attachment" accept=".jpg,.jpeg,.png,.pdf" multiple>
                </div>


            <div class="notes-section">
                <label id="notes">Notes / Other Concerns:</label>
                <textarea name="notes" rows="4" placeholder="Write any additional concerns here..."></textarea>
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


<!---------- MY REQUESTS ---------->
<?php if (isset($_SESSION['flash_message'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            alert("<?= addslashes($_SESSION['flash_message']['text']); ?>");
        });
    </script>
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
                            <td><?php echo htmlspecialchars(date("M d, Y h:i A", strtotime($req['created_at']))); ?></td>

                            <!-- Documents -->
                            <td><?php echo htmlspecialchars($req['documents']); ?></td>

                           <!-- Status -->
<td>
    <?php 
    switch($req['status']) {
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
        <?php echo htmlspecialchars($req['decline_reason'] ?? 'No reason provided'); ?>

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
            <?php if ($queue_num === null || $serving_position === null): ?>
                <label>
                    You are <b>not in line</b>.
                </label>
                <h3>N/A</h3>
            <?php else: ?>
                <label>
                    You are <b><?php echo ordinal($serving_position); ?></b> in line.
                </label>
                <h3><?php echo $queue_num; ?></h3>

                <?php if ($serving_position == 1): ?>
                    <p style="color: green; font-weight: bold; margin-top: 10px;">
                        🎉 It's your turn! Please proceed to the counter.
                    </p>
                <?php elseif ($serving_position <= 3 && $serving_position > 1): ?>
                    <p style="color: orange; font-weight: bold; margin-top: 10px;">
                        ⏳ Almost your turn! Get ready.
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Currently serving info -->
        <div class="service-box">
    <?php if (!empty($currently_serving)): ?>
        <label><b>Now Serving:</b></label>
        <h3><?php echo $currently_serving['queueing_num']; ?></h3>
        <p>
            Line: <?php echo ordinal($currently_serving['serving_position']); ?><br>
            Name: <?php echo $currently_serving['student_name']; ?><br>
            Counter: <b><?php echo $currently_serving['counter_no'] ?? 'N/A'; ?></b><br>
            Staff: <b><?php echo $currently_serving['staff_name'] ?? 'Unknown'; ?></b>
        </p>
    <?php else: ?>
        <label><b>Now Serving:</b></label>
        <h3>N/A</h3>
        <p>No one is being served right now.</p>
    <?php endif; ?>
</div>


    </div>
</section>








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
                <a href="https://www.facebook.com/our.lady.of.fatima.university" class="icon"><i class='uil uil-facebook-f'></i></a>
                <a href="https://www.instagram.com/fatimauniversity/" class="icon"><i class='uil uil-instagram'></i></a>
                <a href="https://www.youtube.com/channel/UC1xRi6L2EBtkWvVdmkNHYEg" class="icon"><i class='uil uil-youtube'></i></a>
                <a href="https://www.linkedin.com/school/our-lady-of-fatima-university/" class="icon"><i class='uil uil-linkedin-alt'></i></a>
            </div>
        </div>
        <div class="col">
            <div class="form">
                <div class="form-inputs">
                    <input type="text" class="input-field" placeholder="Name" 
       value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>" readonly>
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
</body>
</html>
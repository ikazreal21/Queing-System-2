<?php
include('db.php');

// If form is submitted with reasons
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['reasons'])) {
    $id = $_POST['id'];
    $reasons = $_POST['reasons'];

    // Combine reasons into a string
    $reason_text = implode("; ", $reasons);

    // Update the database
    $stmt = $pdo->prepare("UPDATE requests SET status = 'declined', reasons = ? WHERE id = ?");
    $stmt->execute([$reason_text, $id]);

    // Redirect
    header("Location: staff_requests.php");
    exit;
}

// If ID is submitted but no reasons yet, show form
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decline Request</title>
    <link rel="stylesheet" href="decline_request.css">
</head>
<body>
    <div class="container">
    <h2>Select Reasons for Declining</h2>
    <hr>
    <br>
    <form method="post" action="decline_request.php">
        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
        <div class="checkbox-columns" style="display:flex; gap: 40px;">
            <div class="checkbox-columns">
            <div class="checkbox-column">
                <?php
                $left = [
                    "Incomplete Requirements",
                    "Invalid Document Request",
                    "Duplicate Request",
                    "Not Present During Verification",
                    "Incorrect Information Provided",
                    "Unpaid Fees",
                    "Pending Clearance Requirements",
                    "Document Not Available at the Moment"
                ];
                foreach ($left as $index => $label) {
                    echo '<input type="checkbox" name="reasons[]" value="'.$label.'" id="reason'.($index+1).'"> <label for="reason'.($index+1).'">'.$label.'</label><br>';
                }
                ?>
            </div>
            <div class="checkbox-column">
                <?php
                $right = [
                    "System Error — Please Re-Submit",
                    "Outside Requesting Hours",
                    "Unauthorized Requestor",
                    "Suspended Account / Hold Status",
                    "Request for Another Student (Without Authorization)",
                    "Reason 14",
                    "Did Not Follow Proper Procedure",
                    "Previously Released / Already Claimed"
                ];
                foreach ($right as $index => $label) {
                    $idnum = $index + 9; // continue from 9
                    echo '<input type="checkbox" name="reasons[]" value="'.$label.'" id="reason'.$idnum.'"> <label for="reason'.$idnum.'">'.$label.'</label><br>';
                }
                ?>
            </div>
        </div>
            </div>
        <br>
        <button type="submit" class="submit-decline-btn">Submit</button>
    </form>
            </div>
            </body>
            <html>
    <?php
    exit;
}

// If no ID
echo "Invalid request.";
exit;
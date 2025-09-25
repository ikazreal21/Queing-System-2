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
            header("Location: index.php"); // or a "403 Forbidden" page
            exit();
        }
    } else {
        // If user not found
        header("Location: index.php");
        exit();
    }
} else {
    // Redirect to login page if not logged in
    header("Location: index.php");
    exit();
}

// Handle Add Document
if (isset($_POST['add_document'])) {
    $doc_name = trim($_POST['document_name']);
    $processing_days = (int) $_POST['processing_days'];

    if (!empty($doc_name) && $processing_days > 0) {
        $stmt = $pdo->prepare("INSERT INTO documents (name, processing_days) VALUES (?, ?)");
        $stmt->execute([$doc_name, $processing_days]);
    }
    header("Location: admin_documents.php");
    exit();
}


// Handle Delete Document
if (isset($_GET['delete_document'])) {
    $doc_id = (int) $_GET['delete_document'];
    $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    header("Location: admin_documents.php");
    exit();
}

// Handle Add Department
if (isset($_POST['add_department'])) {
    $dept_name = trim($_POST['department_name']);
    if (!empty($dept_name)) {
        $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmt->execute([$dept_name]);
    }
    header("Location: admin_documents.php");
    exit();
}

// Handle Delete Department
if (isset($_GET['delete_department'])) {
    $dept_id = (int) $_GET['delete_department'];
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([$dept_id]);
    header("Location: admin_documents.php");
    exit();
}

// Fetch departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch documents
$documents = $pdo->query("SELECT * FROM documents ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin_documents.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Admin Documents & Departments</title>
</head>
<body>
    <nav class="sidebar">
        <header>
            <div class="image-text">
                <span class="image">
                    <img src="assets/fatimalogo.jpg" alt="logo">
                </span>

                <div class="text header-text">
                    <span class="profession">Admin Documents</span>
                    <span class="name"><?php echo htmlspecialchars($user_name); ?></span> <!-- Display user's name here -->
                </div>
            </div>
            <hr>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <li class="nav-link">
                        <button class="tablinks" id="defaultTab"><a href="admin_dashboard.php" class="tablinks">Dashboard</a></button>
                    </li>

                    <li class="nav-link">
                        <button class="tablinks"><a href="admin_manage.php" class="tablinks">Manage Staff</a></button>
                    </li>    
                    <li class="nav-link">
                        <button class="tablinks"><a href="admin_documents.php" class="tablinks">Add Documents</a></button>
                    </li>                 
                </ul>
            </div>

            <div class="bottom-content">
            <li class="nav-link">
                        <button class="tablinks"><a href="logout_admin.php" class="tablinks">Logout</a></button>
                    </li>
            </div>
        </div>
    </nav>

    <main class="content">
        <h1>Manage Documents</h1>

        <!-- Document Section -->
<h2>Documents</h2>
<form method="POST">
    <input type="text" name="document_name" placeholder="Enter Document Name" required>
    <input type="number" name="processing_days" placeholder="Processing Days" min="1" required>
    <button type="submit" name="add_document">Add Document</button>
</form>


        <ul>
    <?php foreach ($documents as $doc): ?>
        <li>
            <?= htmlspecialchars($doc['name']); ?> 
            - Processing Time: <?= htmlspecialchars($doc['processing_days']); ?> day(s)
            <a href="admin_documents.php?delete_document=<?= $doc['id']; ?>" onclick="return confirm('Delete this document?');">❌</a>
        </li>
    <?php endforeach; ?>
</ul>


        <!-- Department Section -->
        <h2>Departments</h2>
        <form method="POST">
            <input type="text" name="department_name" placeholder="Enter Department Name" required>
            <button type="submit" name="add_department">Add Department</button>
        </form>

        <ul>
            <?php foreach ($departments as $dept): ?>
                <li>
                    <?= htmlspecialchars($dept['name']); ?>
                    <a href="admin_documents.php?delete_department=<?= $dept['id']; ?>" onclick="return confirm('Delete this department?');">❌</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
    
    <script src="admin_dashboard.js"></script>
</body>
</html>

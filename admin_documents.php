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

        // Restrict to admins only
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

// Handle Add Document
if (isset($_POST['add_document'])) {
    $doc_name = trim($_POST['document_name']);
    $processing_days = (int) $_POST['processing_days'];

    if (!empty($doc_name) && $processing_days > 0) {
        $fee = (float) $_POST['fee'];

        $stmt = $pdo->prepare("INSERT INTO documents (name, processing_days, fee) VALUES (?, ?, ?)");
        $stmt->execute([$doc_name, $processing_days, $fee]);
    }
    header("Location: admin_documents.php");
    exit();
}

// Handle Update Document
if (isset($_POST['update_document'])) {
    $doc_id = (int) $_POST['edit_doc_id'];
    $doc_name = trim($_POST['edit_document_name']);
    $processing_days = (int) $_POST['edit_processing_days'];
    $fee = (float) $_POST['edit_fee'];

    $stmt = $pdo->prepare("UPDATE documents SET name=?, processing_days=?, fee=? WHERE id=?");
    $stmt->execute([$doc_name, $processing_days, $fee, $doc_id]);

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

// Handle Add Strand
if (isset($_POST['add_strand'])) {
    $stra_name = trim($_POST['strand_name']);
    if (!empty($stra_name)) {
        $stmt = $pdo->prepare("INSERT INTO strands (name) VALUES (?)");
        $stmt->execute([$stra_name]);
    }
    header("Location: admin_documents.php");
    exit();
}

// Handle Delete Strand
if (isset($_GET['delete_strands'])) {
    $stra_id = (int) $_GET['delete_strands'];
    $stmt = $pdo->prepare("DELETE FROM strands WHERE id = ?");
    $stmt->execute([$stra_id]);
    header("Location: admin_documents.php");
    exit();
}

// Fetch strands
$strands = $pdo->query("SELECT * FROM strands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch documents
$documents = $pdo->query("SELECT * FROM documents ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Documents</title>
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="admin_documents.css">

<style>
/* Main tabs styling */
.main-tabs { display: flex; gap: 1rem; margin-bottom: 1rem; }
.main-tabs .tab-btn { padding: 0.5rem 1rem; cursor: pointer; border: 1px solid #ccc; background: #f5f5f5; border-radius: 5px; transition: 0.2s; }
.main-tabs .tab-btn.active { background: #008c45; color: #fff; border-color: #008c45; }
/* Tab content */
.tab-content { display: none; }
.tab-content.show { display: block; }
</style>
</head>
<body>

<nav class="sidebar">
    <div class="brand">
        <img src="assets/fatimalogo.jpg" alt="logo" class="brand-logo">
        <div class="brand-text">
            <span class="brand-title">Admin Dashboard</span>
            <span class="brand-sub">Welcome, <?= htmlspecialchars($user_name); ?></span>
        </div>
    </div>

    <ul class="menu-links">
        <li><a href="admin_dashboard.php"><i class='bx bx-grid'></i> Dashboard</a></li>
        <li><a href="admin_manage.php"><i class='bx bx-user'></i> Manage Staff</a></li>
        <li><a class="active" href="admin_documents.php"><i class='bx bx-folder-open'></i>Admin Resources</a></li>
        <li class="spacer"></li>
        <li><a href="logout_user.php"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</nav>

<main class="content">
<header class="content-header">
    <h1>Manage Admin Resources</h1>
    <p class="lead">Switch between Documents and Departments/Strands using tabs.</p>
</header>

<!-- Main Tabs -->
<div class="main-tabs">
    <button class="tab-btn active" data-tab="documents-section">Documents</button>
    <button class="tab-btn" data-tab="departments-section">Departments & Strands</button>
</div>

<!-- Documents Section -->
<div class="tab-content show" id="documents-section">

    <!-- Add Document Form -->
    <section class="panel">
        <div class="panel-row">
            <form class="form-inline" method="POST" aria-label="Add document form">
                <div class="form-group">
                    <input id="document_name" name="document_name" type="text" placeholder="Document name" required>
                </div>

                <div class="form-group small">
                    <input id="processing_days" name="processing_days" type="number" placeholder="Processing days" min="1" required>
                </div>

                <div class="form-group small">
                    <input id="fee" name="fee" type="number" placeholder="Fee (₱)" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <button type="submit" name="add_document" class="btn-primary">Add Document</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Documents Table -->
    <section class="panel">
        <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h2>Documents</h2>

    <input type="text" id="searchInput" placeholder="Search documents..."
           style="padding:8px 12px; width:250px; border:1px solid #ccc; border-radius:5px;">
</div>

        <div class="table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Processing (days)</th>
                        <th>Fee (₱)</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($documents) === 0): ?>
                        <tr><td colspan="5" class="muted">No documents yet — add one using the form above.</td></tr>
                    <?php else: foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= htmlspecialchars($doc['name']); ?></td>
                            <td><?= (int)$doc['processing_days']; ?></td>
                            <td>₱<?= number_format((float)$doc['fee'], 2); ?></td>
                            <td class="actions-col">
                                <button class="btn-ghost edit-btn"
                                    data-id="<?= $doc['id']; ?>"
                                    data-name="<?= htmlspecialchars($doc['name']); ?>"
                                    data-days="<?= $doc['processing_days']; ?>"
                                    data-fee="<?= $doc['fee']; ?>"
                                ><i class="bx bx-edit"></i> Edit</button>

                                <a class="btn-danger" href="admin_documents.php?delete_document=<?= (int)$doc['id']; ?>"
                                    onclick="return confirm('Delete document <?= addslashes(htmlspecialchars($doc['name'])); ?>?');">
                                    <i class='bx bx-trash'></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<!-- Departments & Strands Section -->
<div class="tab-content" id="departments-section">

    <section class="panel two-col">
        <div class="col">
            <h3>Departments</h3>
            <form method="POST" class="inline-simple">
                <input type="text" name="department_name" placeholder="Department name" required>
                <button type="submit" name="add_department" class="btn-secondary">Add</button>
            </form>

            <ul class="simple-list">
                <?php foreach ($departments as $dept): ?>
                    <li>
                        <?= htmlspecialchars($dept['name']); ?>
                        <a href="admin_documents.php?delete_department=<?= (int)$dept['id']; ?>" class="link-delete" onclick="return confirm('Delete this department?');">Delete</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col">
            <h3>Strands</h3>
            <form method="POST" class="inline-simple">
                <input type="text" name="strand_name" placeholder="Strand name" required>
                <button type="submit" name="add_strand" class="btn-secondary">Add</button>
            </form>

            <ul class="simple-list">
                <?php foreach ($strands as $str): ?>
                    <li>
                        <?= htmlspecialchars($str['name']); ?>
                        <a href="admin_documents.php?delete_strands=<?= (int)$str['id']; ?>" class="link-delete" onclick="return confirm('Delete this strand?');">Delete</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal-overlay">
  <div class="modal-box">
      <h3>Edit Document</h3>
      <form id="editForm" method="POST">
        <input type="hidden" id="edit_id" name="edit_doc_id">
        <label>Document Name</label>
        <input type="text" id="edit_name" name="edit_document_name" required>
        <label>Processing Days</label>
        <input type="number" id="edit_days" name="edit_processing_days" min="1" required>
        <label>Fee (₱)</label>
        <input type="number" id="edit_fee" name="edit_fee" step="0.01" min="0" required>
        <div class="modal-buttons">
          <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
          <button type="submit" name="update_document" class="btn-save">Save Changes</button>
        </div>
      </form>
  </div>
</div>

<script>
// Main tabs JS
document.querySelectorAll('.main-tabs .tab-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const target = btn.dataset.tab;
        document.querySelectorAll('.main-tabs .tab-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(tc=>tc.classList.remove('show'));
        document.getElementById(target).classList.add('show');
    });
});

// Edit modal JS
function openEditModal(id,name,days,fee,info){
    const modal=document.getElementById("editModal");
    modal.classList.add("show");
    document.getElementById("edit_id").value=id;
    document.getElementById("edit_name").value=name;
    document.getElementById("edit_days").value=days;
    document.getElementById("edit_fee").value=fee;
    document.getElementById("edit_info").value=info;
}
function closeEditModal(){document.getElementById("editModal").classList.remove("show");}
document.querySelectorAll(".edit-btn").forEach(btn=>btn.addEventListener("click",()=>openEditModal(
    btn.dataset.id, btn.dataset.name, btn.dataset.days, btn.dataset.fee, btn.dataset.info
)));
document.addEventListener("click",function(e){if(e.target===document.getElementById("editModal"))closeEditModal();

});

const searchInput = document.getElementById("searchInput");

searchInput.addEventListener("keyup", function () {
    const filter = searchInput.value.toLowerCase();
    const rows = document.querySelectorAll(".doc-table tbody tr");

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

</body>
</html>
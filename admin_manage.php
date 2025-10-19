<?php
// Include DB connection and session
include('db.php');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['user_email'];

// Fetch admin info
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE email = :email");
$stmt->execute(['email' => $user_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$user_name = $user['first_name'] . ' ' . $user['last_name'];

// Fetch departments
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);

// Handle staff registration
if (isset($_POST['registerStaff'])) {
    $first_name   = $_POST['first_name'];
    $last_name    = $_POST['last_name'];
    $email        = $_POST['email'];
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $counter_no   = $_POST['counter_no'];
    $departments_selected = $_POST['departments']; // array of department IDs

    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);

    if ($stmt->rowCount() > 0) {
        $msg = "Email already registered.";
    } else {
        // Insert staff into users table
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, counter_no) 
                               VALUES (:first_name, :last_name, :email, :password, 'staff', :counter_no)");
        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name'  => $last_name,
            ':email'      => $email,
            ':password'   => $password,
            ':counter_no' => $counter_no
        ]);

        $staff_id = $pdo->lastInsertId();

        // Insert staff departments
        $stmtDept = $pdo->prepare("INSERT INTO staff_departments (staff_id, department_id) VALUES (:staff_id, :department_id)");
        foreach ($departments_selected as $dept_id) {
            $stmtDept->execute([
                ':staff_id' => $staff_id,
                ':department_id' => $dept_id
            ]);
        }

        $msg = "Staff registration successful!";
    }
}

// Fetch all staff with multiple departments
$staffResult = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.counter_no,
           GROUP_CONCAT(d.name SEPARATOR ', ') AS departments
    FROM users u
    LEFT JOIN staff_departments sd ON u.id = sd.staff_id
    LEFT JOIN departments d ON sd.department_id = d.id
    WHERE u.role = 'staff'
    GROUP BY u.id
")->fetchAll(PDO::FETCH_ASSOC);

// Handle staff delete
if (isset($_POST['deleteStaff'])) {
    $staffId = $_POST['delete_staff_id'];

    // Remove staff departments first (to avoid foreign key constraint issues)
    $pdo->prepare("DELETE FROM staff_departments WHERE staff_id=?")->execute([$staffId]);

    // Remove staff from users
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$staffId]);

    echo "<script>
        alert('Staff deleted successfully!');
        window.location.href='admin_manage.php';
    </script>";
    exit();
}

// Handle staff update
if (isset($_POST['updateStaff'])) {
    $staffId    = $_POST['edit_staff_id'];
    $firstName  = $_POST['first_name'];
    $lastName   = $_POST['last_name'];
    $email      = $_POST['email'];
    $counterNo  = $_POST['counter_no'];
    $departments = $_POST['departments'] ?? [];

    // Update staff info
    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, counter_no=? WHERE id=?");
    $stmt->execute([$firstName, $lastName, $email, $counterNo, $staffId]);

    // Update staff_departments mapping
    $pdo->prepare("DELETE FROM staff_departments WHERE staff_id=?")->execute([$staffId]);

    if (!empty($departments)) {
        $insert = $pdo->prepare("INSERT INTO staff_departments (staff_id, department_id) VALUES (?, ?)");
        foreach ($departments as $deptId) {
            $insert->execute([$staffId, $deptId]);
        }
    }

    echo "<script>
        alert('Staff updated successfully!');
        window.location.href='admin_manage.php';
    </script>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="admin_manage.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<title>Admin Manage Staff</title>
</head>
<body>
<nav class="sidebar">
    <header>
        <div class="image-text">
            <span class="image">
                <img src="assets/fatimalogo.jpg" alt="logo">
            </span>
            <div class="text header-text">
                <span class="profession">Admin Manage Staff</span>
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

    <!-- ADD STAFF -->
    <div class="stats-container">
        <div class="stat" id="addStaffContainer">
            <div class="stat-content">
                <h3>Add Staff</h3>
            </div>
        </div>
    </div>

    <!-- ADD STAFF MODAL -->
    <div id="addStaffModal" class="modal addStaff">
        <div class="modal-content addStaff">
            <span class="close-btn addStaff">&times;</span>
            <h2>Add Staff</h2>
            <hr>

            <?php if(isset($msg)) echo "<p style='color:red;'>".htmlspecialchars($msg)."</p>"; ?>
            <form class="staff-form" method="POST" action="admin_manage.php">
                <div class="form-row">
                    <div class="form-column">
                        <label for="firstName">First Name:</label>
                        <input type="text" id="firstName" name="first_name" required>
                    </div>
                    <div class="form-column">
                        <label for="lastName">Last Name:</label>
                        <input type="text" id="lastName" name="last_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-column">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-column">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-column">
                        <label for="counter_no">Counter No.:</label>
                        <input type="number" id="counter_no" name="counter_no" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-column">
                        <label for="departments">Departments:</label>
                        <select id="departments" name="departments[]" multiple required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['id']); ?>"><?= htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Hold CTRL (Windows) or CMD (Mac) to select multiple</small>
                    </div>
                </div>

                <button type="submit" name="registerStaff">Register Staff</button>
            </form>
        </div>
    </div>

    <!-- Staff Table -->
<div class="table-container">
    <div class="table_responsive">
        <table>
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Counter No.</th>
                    <th>Departments</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($staffResult) > 0): ?>
                    <?php foreach($staffResult as $row): ?>
                        <tr data-id="<?= $row['id']; ?>"
                            data-firstname="<?= htmlspecialchars($row['first_name']); ?>"
                            data-lastname="<?= htmlspecialchars($row['last_name']); ?>"
                            data-email="<?= htmlspecialchars($row['email']); ?>"
                            data-counter="<?= htmlspecialchars($row['counter_no'] ?? ''); ?>"
                            data-departments="<?= htmlspecialchars($row['departments'] ?? ''); ?>">
                            
                            <td><?= htmlspecialchars($row['first_name']); ?></td>
                            <td><?= htmlspecialchars($row['last_name']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['counter_no'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row['departments'] ?? '-'); ?></td>
                            <td>
                                <button type='button' class='edit-btn'>Edit</button>
                                <button type='button' class='remove-btn'>Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No staff found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Edit Modal -->
<div id="editStaffModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close-btn" id="closeEdit">&times;</span>
    <h2>Edit Staff</h2>
    <form id="editStaffForm" method="POST" action="admin_manage.php">
      <input type="hidden" name="edit_staff_id" id="edit_staff_id">

      <label>First Name:</label>
      <input type="text" id="edit_first_name" name="first_name" required><br>

      <label>Last Name:</label>
      <input type="text" id="edit_last_name" name="last_name" required><br>

      <label>Email:</label>
      <input type="email" id="edit_email" name="email" required><br>

      <label>Counter No.:</label>
      <input type="number" id="edit_counter_no" name="counter_no"><br>

      <label>Departments:</label><br>
      <div id="departmentsCheckboxes">
        <?php
          // Fetch all departments
          $deptStmt = $pdo->query("SELECT * FROM departments");
          while ($dept = $deptStmt->fetch(PDO::FETCH_ASSOC)) {
              echo "<label><input type='checkbox' name='departments[]' value='{$dept['id']}'><span>{$dept['name']}</span></label>";

          }
        ?>
      </div>

      <button type="submit" name="updateStaff">Update Staff</button>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div id="deleteStaffModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close-btn" id="closeDelete">&times;</span>
    <h2>Delete Staff</h2>
    <p>Are you sure you want to delete this staff member?</p>
    <form id="deleteStaffForm" method="POST" action="admin_manage.php">
      <input type="hidden" name="delete_staff_id" id="delete_staff_id">
      <button type="submit" name="deleteStaff">Yes, Delete</button>
      <button type="button" id="cancelDelete">Cancel</button>
    </form>
  </div>
</div>

<style>
/* General Modal Background */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(4px);
}

/* Modal Box */
.modal-content {
  background: #fff;
  margin: 8% auto;
  margin-bottom: 700px;
  padding: 25px 30px;
  width: 450px;
  border-radius: 12px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.2);
  font-family: Arial, sans-serif;
  animation: fadeIn 0.3s ease-out;
}

/* Title */
.modal-content h2 {
  margin-top: 0;
  font-size: 22px;
  text-align: center;
  color: #333;
}

/* Labels */
.modal-content label {
  display: block;
  margin: 12px 0 5px;
  font-weight: bold;
  color: #444;
}

/* Inputs */
.modal-content input[type="text"],
.modal-content input[type="email"],
.modal-content input[type="number"] {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 14px;
  margin-bottom: 10px;
  transition: border 0.2s;
}

.modal-content input:focus {
  border-color: #007bff;
  outline: none;
}

/* Department checkboxes container */
#departmentsCheckboxes {
  display: grid;
  grid-template-columns: repeat(2, 1fr); /* 2 columns */
  gap: 8px 15px;
  margin-top: 8px;
}

/* Checkbox label cards */
#departmentsCheckboxes label {
  display: flex;
  align-items: center;
  font-size: 14px;
  font-weight: normal;
  background: #f8f9fa;
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #ddd;
  cursor: pointer;
  transition: background 0.2s, border 0.2s, box-shadow 0.2s;
}

/* Hover effect */
#departmentsCheckboxes label:hover {
  background: #e9ecef;
  border-color: #ccc;
}

/* Checkbox spacing */
#departmentsCheckboxes input {
  margin-right: 8px;
}

/* Highlight selected checkboxes */
#departmentsCheckboxes input:checked + span {
  font-weight: bold;
  color: #28a745;
}
#departmentsCheckboxes input:checked:parent {
  border-color: #28a745;
  box-shadow: 0 0 5px rgba(40,167,69,0.4);
}

/* Buttons */
.modal-content button {
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s;
  margin-top: 10px;
}

.modal-content button[name="updateStaff"] {
  background: #28a745;
  color: #fff;
  width: 100%;
}

.modal-content button[name="updateStaff"]:hover {
  background: #218838;
}

.modal-content button[name="deleteStaff"] {
  background: #dc3545;
  color: #fff;
  margin-right: 10px;
}

.modal-content button[name="deleteStaff"]:hover {
  background: #c82333;
}

.modal-content button#cancelDelete {
  background: #6c757d;
  color: #fff;
}

.modal-content button#cancelDelete:hover {
  background: #5a6268;
}

/* Close button (X) */
.close-btn {
  float: right;
  cursor: pointer;
  font-size: 22px;
  font-weight: bold;
  color: #999;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #333;
}

/* Animation */
@keyframes fadeIn {
  from {opacity: 0; transform: translateY(-20px);}
  to {opacity: 1; transform: translateY(0);}
}

</style>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const editModal = document.getElementById("editStaffModal");
  const deleteModal = document.getElementById("deleteStaffModal");

  const closeEdit = document.getElementById("closeEdit");
  const closeDelete = document.getElementById("closeDelete");
  const cancelDelete = document.getElementById("cancelDelete");

  // Open Edit Modal
  document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.addEventListener("click", function() {
      const row = this.closest("tr");

      // Fill form with row data
      document.getElementById("edit_staff_id").value = row.dataset.id;
      document.getElementById("edit_first_name").value = row.dataset.firstname;
      document.getElementById("edit_last_name").value = row.dataset.lastname;
      document.getElementById("edit_email").value = row.dataset.email;
      document.getElementById("edit_counter_no").value = row.dataset.counter;

      // Reset all checkboxes
      document.querySelectorAll("#departmentsCheckboxes input[type=checkbox]").forEach(cb => cb.checked = false);

      // Pre-check departments (expects row.dataset.departments = "1,3,5")
      if (row.dataset.departments) {
        const deptArray = row.dataset.departments.split(",");
        deptArray.forEach(deptId => {
          let checkbox = document.querySelector(`#departmentsCheckboxes input[value='${deptId}']`);
          if (checkbox) checkbox.checked = true;
        });
      }

      editModal.style.display = "block";
    });
  });

  // Open Delete Modal
  document.querySelectorAll(".remove-btn").forEach(btn => {
    btn.addEventListener("click", function() {
      const row = this.closest("tr");
      document.getElementById("delete_staff_id").value = row.dataset.id;
      deleteModal.style.display = "block";
    });
  });

  // Close modals
  closeEdit.onclick = () => editModal.style.display = "none";
  closeDelete.onclick = () => deleteModal.style.display = "none";
  cancelDelete.onclick = () => deleteModal.style.display = "none";

  // Close when clicking outside modal
  window.onclick = function(event) {
    if (event.target === editModal) editModal.style.display = "none";
    if (event.target === deleteModal) deleteModal.style.display = "none";
  }
});
</script>



<script src="admin_manage.js"></script>
</body>
</html>

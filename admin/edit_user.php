<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Edit User
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration file
require_once BASE_PATH . '/includes/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect($base_url . 'login.php');
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    redirect($base_url . 'admin/users.php');
}

// Initialize variables
$full_name = '';
$email = '';
$role = '';
$department_id = '';
$college_id = '';
$position = '';
$phone = '';
$status = 1;
$success_message = '';
$error_message = '';
$errors = [];
$user = null;

// Get user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $full_name = $user['full_name'];
    $email = $user['email'];
    $role = $user['role'];
    $department_id = $user['department_id'];
    $college_id = $user['college_id'];
    $position = $user['position'];
    $phone = $user['phone'];
    $status = $user['status'];
} else {
    redirect($base_url . 'admin/users.php');
}

// Get all departments
$departments = [];
$sql = "SELECT d.*, c.name as college_name FROM departments d JOIN colleges c ON d.college_id = c.college_id ORDER BY c.name ASC, d.name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Get all colleges
$colleges = [];
$sql = "SELECT * FROM colleges ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $new_password = !empty($_POST['password']) ? $_POST['password'] : null;
    $confirm_password = !empty($_POST['confirm_password']) ? $_POST['confirm_password'] : null;
    $role = sanitize_input($_POST['role']);
    $department_id = !empty($_POST['department_id']) ? sanitize_input($_POST['department_id']) : null;
    $college_id = !empty($_POST['college_id']) ? sanitize_input($_POST['college_id']) : null;
    $position = !empty($_POST['position']) ? sanitize_input($_POST['position']) : null;
    $phone = !empty($_POST['phone']) ? sanitize_input($_POST['phone']) : null;
    $status = isset($_POST['status']) ? 1 : 0;

    // Validate form data
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists (excluding current user)
        $sql = "SELECT * FROM users WHERE email = ? AND user_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists.";
        }
    }

    // Password validation (only if new password is provided)
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    if (empty($role)) {
        $errors[] = "Role is required.";
    }

    // Role-specific validations
    if ($role === 'head_of_department' && empty($department_id)) {
        $errors[] = "Department is required for Head of Department role.";
    }

    if ($role === 'dean' && empty($college_id)) {
        $errors[] = "College is required for Dean role.";
    }

    if ($role === 'college' && empty($college_id)) {
        $errors[] = "College is required for College role.";
    }

    // If no errors, update user
    if (empty($errors)) {
        // Prepare update query
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash_custom($new_password);
            $sql = "UPDATE users SET full_name = ?, email = ?, password = ?, role = ?, department_id = ?, college_id = ?, position = ?, phone = ?, status = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssiiisii", $full_name, $email, $hashed_password, $role, $department_id, $college_id, $position, $phone, $status, $user_id);
        } else {
            // Update without changing password
            $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, department_id = ?, college_id = ?, position = ?, phone = ?, status = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiiisii", $full_name, $email, $role, $department_id, $college_id, $position, $phone, $status, $user_id);
        }

        if ($stmt->execute()) {
            $success_message = "User updated successfully.";

            // Update user data for display
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['role'] = $role;
            $user['department_id'] = $department_id;
            $user['college_id'] = $college_id;
            $user['position'] = $position;
            $user['phone'] = $phone;
            $user['status'] = $status;
        } else {
            $error_message = "Error updating user: " . $conn->error;
        }
    } else {
        $error_message = "Please fix the following errors:<br>" . implode("<br>", $errors);
    }
}

// Include header
include_once BASE_PATH . '/includes/header_management.php';

// Include sidebar
include_once BASE_PATH . '/includes/sidebar.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Edit User</h1>
            <a href="<?php echo $base_url; ?>admin/users.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Users
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Edit User Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $user_id); ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="full_name">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a full name.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <div class="invalid-feedback">
                                    Please provide a password.
                                </div>
                                <small class="form-text text-muted">Leave blank to keep current password. Password must be at least 6 characters.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                <div class="invalid-feedback">
                                    Please confirm your password.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Role <span class="text-danger">*</span></label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="head_of_department" <?php echo ($role === 'head_of_department') ? 'selected' : ''; ?>>Head of Department</option>
                                    <option value="dean" <?php echo ($role === 'dean') ? 'selected' : ''; ?>>Dean</option>
                                    <option value="college" <?php echo ($role === 'college') ? 'selected' : ''; ?>>College</option>
                                    <option value="hrm" <?php echo ($role === 'hrm') ? 'selected' : ''; ?>>HRM</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a role.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($position); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group department-group" style="display: <?php echo ($role === 'head_of_department') ? 'block' : 'none'; ?>;">
                                <label for="department_id">Department <span class="text-danger">*</span></label>
                                <select class="form-control" id="department_id" name="department_id" <?php echo ($role === 'head_of_department') ? 'required' : ''; ?>>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['department_id']; ?>" data-college="<?php echo $department['college_id']; ?>" <?php echo ($department_id == $department['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo $department['name']; ?> (<?php echo $department['college_name']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a department.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group college-group" style="display: <?php echo ($role === 'dean' || $role === 'college') ? 'block' : 'none'; ?>;">
                                <label for="college_id">College <span class="text-danger">*</span></label>
                                <select class="form-control" id="college_id" name="college_id" <?php echo ($role === 'dean' || $role === 'college') ? 'required' : ''; ?>>
                                    <option value="">Select College</option>
                                    <?php foreach ($colleges as $college): ?>
                                        <option value="<?php echo $college['college_id']; ?>" <?php echo ($college_id == $college['college_id']) ? 'selected' : ''; ?>>
                                            <?php echo $college['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a college.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch mt-4">
                                    <input type="checkbox" class="custom-control-input" id="status" name="status" <?php echo ($status == 1) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Update User
                        </button>
                        <a href="<?php echo $base_url; ?>admin/users.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/form-validation.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show/hide department and college fields based on role
        const roleSelect = document.getElementById('role');
        const departmentGroup = document.querySelector('.department-group');
        const collegeGroup = document.querySelector('.college-group');
        const departmentSelect = document.getElementById('department_id');
        const collegeSelect = document.getElementById('college_id');

        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;

            // Reset required attributes
            departmentSelect.removeAttribute('required');
            collegeSelect.removeAttribute('required');

            // Hide both groups initially
            departmentGroup.style.display = 'none';
            collegeGroup.style.display = 'none';

            // Show appropriate group based on role
            if (selectedRole === 'head_of_department') {
                departmentGroup.style.display = 'block';
                departmentSelect.setAttribute('required', 'required');
            } else if (selectedRole === 'dean' || selectedRole === 'college') {
                collegeGroup.style.display = 'block';
                collegeSelect.setAttribute('required', 'required');
            }
        });

        // Update college when department is selected
        departmentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const collegeId = selectedOption.getAttribute('data-college');

            if (collegeId) {
                collegeSelect.value = collegeId;
            }
        });

        // Password confirmation validation
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');

        function validatePasswords() {
            if (passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordField.setCustomValidity('');
            }
        }

        passwordField.addEventListener('input', validatePasswords);
        confirmPasswordField.addEventListener('input', validatePasswords);
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
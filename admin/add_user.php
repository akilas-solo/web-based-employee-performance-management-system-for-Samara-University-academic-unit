<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Add User
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration file
require_once BASE_PATH . '/includes/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$full_name = '';
$email = '';
$password = '';
$confirm_password = '';
$role = '';
$department_id = '';
$college_id = '';
$position = '';
$phone = '';
$status = 1;
$success_message = '';
$error_message = '';
$errors = [];

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
    $password = $_POST['password']; // Don't sanitize password
    $confirm_password = $_POST['confirm_password']; // Don't sanitize password
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
        // Check if email already exists
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists.";
        }
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
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

    // If no errors, insert user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash_custom($password);

        // Generate username from email (part before @)
        $username = strtolower(explode('@', $email)[0]);

        // Check if username already exists and make it unique if needed
        $original_username = $username;
        $counter = 1;
        while (true) {
            $sql = "SELECT user_id FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                break; // Username is unique
            }

            $username = $original_username . $counter;
            $counter++;
        }

        // Insert user
        $sql = "INSERT INTO users (username, full_name, email, password, role, department_id, college_id, position, phone, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssiissi", $username, $full_name, $email, $hashed_password, $role, $department_id, $college_id, $position, $phone, $status);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id;

            // Create role-specific profile
            if ($role === 'head_of_department') {
                $sql = "INSERT INTO head_profiles (user_id) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            } elseif ($role === 'dean') {
                $sql = "INSERT INTO dean_profiles (user_id) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            } elseif ($role === 'college') {
                $sql = "INSERT INTO college_profiles (user_id) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            } elseif ($role === 'hrm') {
                $sql = "INSERT INTO hrm_profiles (user_id) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            }

            $success_message = "User added successfully.";

            // Reset form fields
            $full_name = '';
            $email = '';
            $password = '';
            $confirm_password = '';
            $role = '';
            $department_id = '';
            $college_id = '';
            $position = '';
            $phone = '';
            $status = 1;
        } else {
            $error_message = "Error adding user: " . $conn->error;
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
            <h1 class="h3 mb-0 text-gray-800">Add New User</h1>
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

        <!-- Add User Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="full_name">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $full_name; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a full name.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">
                                    Please provide a password.
                                </div>
                                <small class="form-text text-muted">Password must be at least 6 characters.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
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
                                <input type="text" class="form-control" id="position" name="position" value="<?php echo $position; ?>">
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
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>">
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
                            <i class="fas fa-user-plus mr-1"></i> Add User
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
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

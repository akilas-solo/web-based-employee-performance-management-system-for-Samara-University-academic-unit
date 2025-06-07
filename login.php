<?php
/**
 * Samara University Academic Performance Evaluation System
 * Login Page
 */

// Include configuration file
require_once 'includes/config.php';

// Check if user is already logged in
if (is_logged_in()) {
    // Redirect based on role
    if (is_admin()) {
        redirect($base_url . 'admin/dashboard.php');
    } else {
        $role = $_SESSION['role'];
        redirect($base_url . $role . '/dashboard.php');
    }
}

// Initialize variables
$email = '';
$password = '';
$role = '';
$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // Don't sanitize password
    $role = sanitize_input($_POST['role']);

    // Validate form data
    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields.';
    } else {
        // Check if role is admin
        if ($role === 'admin') {
            // Check admin credentials in users table
            $sql = "SELECT * FROM users WHERE email = ? AND role = 'admin'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();

                // Verify password
                if (password_verify_custom($password, $admin['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $admin['user_id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['email'] = $admin['email'];
                    $_SESSION['full_name'] = $admin['full_name'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['profile_image'] = $admin['profile_image'];

                    // Redirect to admin dashboard
                    redirect($base_url . 'admin/dashboard.php');
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            // Check user credentials
            $sql = "SELECT * FROM users WHERE email = ? AND role = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $email, $role);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Check if user is active
                if ($user['status'] == STATUS_INACTIVE) {
                    $error = 'Your account is inactive. Please contact the administrator.';
                } else {
                    // Verify password
                    if (password_verify_custom($password, $user['password'])) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['department_id'] = $user['department_id'];
                        $_SESSION['college_id'] = $user['college_id'];
                        $_SESSION['profile_image'] = $user['profile_image'];

                        // Redirect to role-specific dashboard
                        if ($role === 'head_of_department') {
                            redirect($base_url . 'head/dashboard.php');
                        } else {
                            redirect($base_url . $role . '/dashboard.php');
                        }
                    } else {
                        $error = 'Invalid email or password.';
                    }
                }
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Samara University Academic Performance Evaluation System</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-wrapper">
            <div class="login-card">
                <div class="login-header text-center">
                    <img src="<?php echo $base_url; ?>assets/images/logo.png" alt="Samara University" class="university-logo">
                    <div class="login-header-content">
                        <h3>Samara University</h3>
                        <p>Academic Performance Evaluation System</p>
                    </div>
                </div>

                <div class="login-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                </div>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="input-group-append">
                                    <span class="input-group-text toggle-password" title="Show/Hide Password">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Select Role</label>
                            <div class="role-options">
                                <div class="role-option">
                                    <input type="radio" class="role-radio" id="role-admin" name="role" value="admin" <?php echo ($role === 'admin') ? 'checked' : ''; ?>>
                                    <label for="role-admin" class="role-label">
                                        <i class="fas fa-user-shield"></i>
                                        <span>Admin</span>
                                    </label>
                                </div>

                                <div class="role-option">
                                    <input type="radio" class="role-radio" id="role-college" name="role" value="college" <?php echo ($role === 'college') ? 'checked' : ''; ?>>
                                    <label for="role-college" class="role-label">
                                        <i class="fas fa-university"></i>
                                        <span>College</span>
                                    </label>
                                </div>

                                <div class="role-option">
                                    <input type="radio" class="role-radio" id="role-dean" name="role" value="dean" <?php echo ($role === 'dean') ? 'checked' : ''; ?>>
                                    <label for="role-dean" class="role-label">
                                        <i class="fas fa-user-tie"></i>
                                        <span>Dean</span>
                                    </label>
                                </div>

                                <div class="role-option">
                                    <input type="radio" class="role-radio" id="role-head" name="role" value="head_of_department" <?php echo ($role === 'head_of_department') ? 'checked' : ''; ?>>
                                    <label for="role-head" class="role-label">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span>Head</span>
                                    </label>
                                </div>

                                <div class="role-option">
                                    <input type="radio" class="role-radio" id="role-hrm" name="role" value="hrm" <?php echo ($role === 'hrm') ? 'checked' : ''; ?>>
                                    <label for="role-hrm" class="role-label">
                                        <i class="fas fa-users-cog"></i>
                                        <span>HRM</span>
                                    </label>
                                </div>

                                <div class="role-option">
                                    <input type="radio" class="role-radio" id="role-staff" name="role" value="staff" <?php echo ($role === 'staff') ? 'checked' : ''; ?>>
                                    <label for="role-staff" class="role-label">
                                        <i class="fas fa-user-graduate"></i>
                                        <span>Staff</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                            <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn btn-login btn-block">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </button>
                    </form>
                </div>
            </div>

            <div class="login-footer text-center mt-3">
                <p>&copy; <?php echo date('Y'); ?> Samara University. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('.toggle-password').click(function() {
                const passwordField = $('#password');
                const passwordFieldType = passwordField.attr('type');
                const icon = $(this).find('i');

                if (passwordFieldType === 'password') {
                    passwordField.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        });
    </script>
</body>
</html>

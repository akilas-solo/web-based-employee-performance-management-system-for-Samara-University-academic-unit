<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Settings
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
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];

// Get user information
$user = null;
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    $error_message = "User information not found.";
}

// Process form submission for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Get form data
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate form data
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = "Current password is required.";
    } else {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        }
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required.";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "New password must be at least 8 characters long.";
    }
    
    if (empty($confirm_password)) {
        $errors[] = "Confirm password is required.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "New password and confirm password do not match.";
    }
    
    // Update password if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Password updated successfully.";
        } else {
            $error_message = "Failed to update password.";
        }
    } else {
        $error_message = "Please fix the following errors:<br>" . implode("<br>", $errors);
    }
}

// Process form submission for notification settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    // Get form data
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $evaluation_reminders = isset($_POST['evaluation_reminders']) ? 1 : 0;
    $staff_updates = isset($_POST['staff_updates']) ? 1 : 0;
    $system_updates = isset($_POST['system_updates']) ? 1 : 0;

    // Check if notification_settings table exists
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'notification_settings'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }

    if (!$table_exists) {
        // Create notification_settings table if it doesn't exist
        $sql = "CREATE TABLE notification_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email_notifications TINYINT(1) DEFAULT 1,
                evaluation_reminders TINYINT(1) DEFAULT 1,
                staff_updates TINYINT(1) DEFAULT 1,
                system_updates TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            )";
        $conn->query($sql);
        $table_exists = true;
    }

    if ($table_exists) {
        // Check if notification settings exist
        $sql = "SELECT * FROM notification_settings WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                // Update notification settings
                $sql = "UPDATE notification_settings SET
                        email_notifications = ?,
                        evaluation_reminders = ?,
                        staff_updates = ?,
                        system_updates = ?
                        WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("iiiii", $email_notifications, $evaluation_reminders, $staff_updates, $system_updates, $user_id);
                    if ($stmt->execute()) {
                        $success_message = "Notification settings updated successfully.";
                    } else {
                        $error_message = "Failed to update notification settings. Please try again.";
                    }
                } else {
                    $error_message = "Failed to prepare update statement.";
                }
            } else {
                // Insert notification settings
                $sql = "INSERT INTO notification_settings
                        (user_id, email_notifications, evaluation_reminders, staff_updates, system_updates)
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("iiiii", $user_id, $email_notifications, $evaluation_reminders, $staff_updates, $system_updates);
                    if ($stmt->execute()) {
                        $success_message = "Notification settings updated successfully.";
                    } else {
                        $error_message = "Failed to update notification settings. Please try again.";
                    }
                } else {
                    $error_message = "Failed to prepare insert statement.";
                }
            }
        } else {
            $error_message = "Failed to prepare select statement.";
        }
    } else {
        $error_message = "Failed to create notification settings table.";
    }
}

// Get notification settings
$notification_settings = [
    'email_notifications' => 1,
    'evaluation_reminders' => 1,
    'staff_updates' => 1,
    'system_updates' => 1
];

// Check if notification_settings table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'notification_settings'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    $sql = "SELECT * FROM notification_settings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $notification_settings = $result->fetch_assoc();
        }
    }
}

// Process form submission for system settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_system'])) {
    // Get form data
    $site_name = sanitize_input($_POST['site_name']);
    $site_email = sanitize_input($_POST['site_email']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    // Check if system_settings table exists
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'system_settings'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }

    if (!$table_exists) {
        // Create system_settings table if it doesn't exist
        $sql = "CREATE TABLE system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(50) NOT NULL,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
        $conn->query($sql);
        $table_exists = true;
        
        // Insert default settings
        $default_settings = [
            ['site_name', 'Samara University Academic Performance Evaluation System'],
            ['site_email', 'admin@samara.edu.et'],
            ['maintenance_mode', '0']
        ];
        
        $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        foreach ($default_settings as $setting) {
            $stmt->bind_param("ss", $setting[0], $setting[1]);
            $stmt->execute();
        }
    }

    if ($table_exists) {
        // Update system settings
        $settings = [
            ['site_name', $site_name],
            ['site_email', $site_email],
            ['maintenance_mode', $maintenance_mode]
        ];
        
        $sql = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $conn->begin_transaction();
            
            try {
                foreach ($settings as $setting) {
                    $stmt->bind_param("ss", $setting[1], $setting[0]);
                    $stmt->execute();
                }
                
                $conn->commit();
                $success_message = "System settings updated successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Failed to update system settings: " . $e->getMessage();
            }
        } else {
            $error_message = "Failed to prepare update statement.";
        }
    } else {
        $error_message = "Failed to create system settings table.";
    }
}

// Get system settings
$system_settings = [
    'site_name' => 'Samara University Academic Performance Evaluation System',
    'site_email' => 'admin@samara.edu.et',
    'maintenance_mode' => 0
];

// Check if system_settings table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'system_settings'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    $sql = "SELECT * FROM system_settings";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $system_settings[$row['setting_key']] = $row['setting_value'];
        }
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
            <h1 class="h3 mb-0 text-gray-800">Settings</h1>
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

        <div class="row">
            <!-- Change Password Card -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Change Password</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key mr-1"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notification Settings Card -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Notification Settings</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="email_notifications" name="email_notifications" <?php echo ($notification_settings['email_notifications'] == 1) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="email_notifications">Email Notifications</label>
                                <small class="form-text text-muted">Receive notifications via email.</small>
                            </div>
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="evaluation_reminders" name="evaluation_reminders" <?php echo ($notification_settings['evaluation_reminders'] == 1) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="evaluation_reminders">Evaluation Reminders</label>
                                <small class="form-text text-muted">Receive reminders about upcoming evaluations.</small>
                            </div>
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="staff_updates" name="staff_updates" <?php echo ($notification_settings['staff_updates'] == 1) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="staff_updates">Staff Updates</label>
                                <small class="form-text text-muted">Receive notifications about staff changes.</small>
                            </div>
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="system_updates" name="system_updates" <?php echo ($notification_settings['system_updates'] == 1) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="system_updates">System Updates</label>
                                <small class="form-text text-muted">Receive notifications about system updates.</small>
                            </div>
                            <button type="submit" name="update_notifications" class="btn btn-primary">
                                <i class="fas fa-bell mr-1"></i> Update Notification Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Settings Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold">System Settings</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="site_name">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo $system_settings['site_name']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="site_email">Site Email</label>
                                <input type="email" class="form-control" id="site_email" name="site_email" value="<?php echo $system_settings['site_email']; ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode" <?php echo ($system_settings['maintenance_mode'] == 1) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="maintenance_mode">Maintenance Mode</label>
                        <small class="form-text text-muted">Enable maintenance mode to temporarily disable the site for all users except administrators.</small>
                    </div>
                    <button type="submit" name="update_system" class="btn btn-primary">
                        <i class="fas fa-cogs mr-1"></i> Update System Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.toggle-password');

        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

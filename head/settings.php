<?php
/**
 * Samara University Academic Performance Evaluation System
 * Head of Department - Settings
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has head_of_department role
if (!is_logged_in() || !has_role('head_of_department')) {
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
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "New password must be at least 8 characters long.";
    } else {
        // Verify current password
        if (password_verify_custom($current_password, $user['password'])) {
            // Hash new password
            $hashed_password = password_hash_custom($new_password);

            // Update password in database
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                $success_message = "Password changed successfully.";
            } else {
                $error_message = "Failed to change password. Please try again.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
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

// Process form submission for privacy settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_privacy'])) {
    // Get form data
    $show_email = isset($_POST['show_email']) ? 1 : 0;
    $show_phone = isset($_POST['show_phone']) ? 1 : 0;
    $show_profile = isset($_POST['show_profile']) ? 1 : 0;

    // Check if privacy_settings table exists
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'privacy_settings'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }

    if (!$table_exists) {
        // Create privacy_settings table if it doesn't exist
        $sql = "CREATE TABLE privacy_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                show_email TINYINT(1) DEFAULT 1,
                show_phone TINYINT(1) DEFAULT 1,
                show_profile TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            )";
        $conn->query($sql);
        $table_exists = true;
    }

    if ($table_exists) {
        // Check if privacy settings exist
        $sql = "SELECT * FROM privacy_settings WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                // Update privacy settings
                $sql = "UPDATE privacy_settings SET
                        show_email = ?,
                        show_phone = ?,
                        show_profile = ?
                        WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("iiii", $show_email, $show_phone, $show_profile, $user_id);
                    if ($stmt->execute()) {
                        $success_message = "Privacy settings updated successfully.";
                    } else {
                        $error_message = "Failed to update privacy settings. Please try again.";
                    }
                } else {
                    $error_message = "Failed to prepare update statement.";
                }
            } else {
                // Insert privacy settings
                $sql = "INSERT INTO privacy_settings
                        (user_id, show_email, show_phone, show_profile)
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("iiii", $user_id, $show_email, $show_phone, $show_profile);
                    if ($stmt->execute()) {
                        $success_message = "Privacy settings updated successfully.";
                    } else {
                        $error_message = "Failed to update privacy settings. Please try again.";
                    }
                } else {
                    $error_message = "Failed to prepare insert statement.";
                }
            }
        } else {
            $error_message = "Failed to prepare select statement.";
        }
    } else {
        $error_message = "Failed to create privacy settings table.";
    }
}

// Get privacy settings
$privacy_settings = [
    'show_email' => 1,
    'show_phone' => 1,
    'show_profile' => 1
];

// Check if privacy_settings table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'privacy_settings'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    $sql = "SELECT * FROM privacy_settings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $privacy_settings = $result->fetch_assoc();
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
            <h1 class="h3 mb-0 text-gray-800">Account Settings</h1>
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
            <div class="col-lg-12">
                <!-- Settings Tabs -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="password-tab" data-toggle="tab" href="#password" role="tab" aria-controls="password" aria-selected="true">
                                    <i class="fas fa-key mr-1"></i> Change Password
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="notifications-tab" data-toggle="tab" href="#notifications" role="tab" aria-controls="notifications" aria-selected="false">
                                    <i class="fas fa-bell mr-1"></i> Notification Settings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="privacy-tab" data-toggle="tab" href="#privacy" role="tab" aria-controls="privacy" aria-selected="false">
                                    <i class="fas fa-user-shield mr-1"></i> Privacy Settings
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="settingsTabsContent">
                            <!-- Change Password Tab -->
                            <div class="tab-pane fade show active" id="password" role="tabpanel" aria-labelledby="password-tab">
                                <h5 class="card-title mb-4">Change Password</h5>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <div class="form-group">
                                        <label for="current_password">Current Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="current_password">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="new_password">New Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="new_password">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="confirm_password">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" name="change_password" class="btn btn-theme">
                                        <i class="fas fa-save mr-1"></i> Change Password
                                    </button>
                                </form>
                            </div>

                            <!-- Notification Settings Tab -->
                            <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                                <h5 class="card-title mb-4">Notification Settings</h5>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="email_notifications" name="email_notifications" <?php echo $notification_settings['email_notifications'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="email_notifications">Email Notifications</label>
                                        <small class="form-text text-muted">Receive notifications via email.</small>
                                    </div>

                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="evaluation_reminders" name="evaluation_reminders" <?php echo $notification_settings['evaluation_reminders'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="evaluation_reminders">Evaluation Reminders</label>
                                        <small class="form-text text-muted">Receive reminders about pending evaluations.</small>
                                    </div>

                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="staff_updates" name="staff_updates" <?php echo $notification_settings['staff_updates'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="staff_updates">Staff Updates</label>
                                        <small class="form-text text-muted">Receive notifications about staff changes in your department.</small>
                                    </div>

                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="system_updates" name="system_updates" <?php echo $notification_settings['system_updates'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="system_updates">System Updates</label>
                                        <small class="form-text text-muted">Receive notifications about system updates and maintenance.</small>
                                    </div>

                                    <button type="submit" name="update_notifications" class="btn btn-theme">
                                        <i class="fas fa-save mr-1"></i> Save Notification Settings
                                    </button>
                                </form>
                            </div>

                            <!-- Privacy Settings Tab -->
                            <div class="tab-pane fade" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
                                <h5 class="card-title mb-4">Privacy Settings</h5>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="show_email" name="show_email" <?php echo $privacy_settings['show_email'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="show_email">Show Email Address</label>
                                        <small class="form-text text-muted">Allow others to see your email address.</small>
                                    </div>

                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="show_phone" name="show_phone" <?php echo $privacy_settings['show_phone'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="show_phone">Show Phone Number</label>
                                        <small class="form-text text-muted">Allow others to see your phone number.</small>
                                    </div>

                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="show_profile" name="show_profile" <?php echo $privacy_settings['show_profile'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="show_profile">Show Profile</label>
                                        <small class="form-text text-muted">Allow others to view your profile details.</small>
                                    </div>

                                    <button type="submit" name="update_privacy" class="btn btn-theme">
                                        <i class="fas fa-save mr-1"></i> Save Privacy Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Account Security</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Login History</h5>
                                <p>View your recent login activity.</p>
                                <a href="#" class="btn btn-outline-primary" data-toggle="modal" data-target="#loginHistoryModal">
                                    <i class="fas fa-history mr-1"></i> View Login History
                                </a>
                            </div>
                            <div class="col-md-6">
                                <h5>Two-Factor Authentication</h5>
                                <p>Add an extra layer of security to your account.</p>
                                <a href="#" class="btn btn-outline-primary" data-toggle="modal" data-target="#twoFactorModal">
                                    <i class="fas fa-shield-alt mr-1"></i> Setup Two-Factor Authentication
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Login History Modal -->
<div class="modal fade" id="loginHistoryModal" tabindex="-1" role="dialog" aria-labelledby="loginHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginHistoryModalLabel">Login History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>IP Address</th>
                                <th>Browser</th>
                                <th>Device</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s'); ?></td>
                                <td>127.0.0.1</td>
                                <td>Chrome</td>
                                <td>Desktop</td>
                                <td><span class="badge badge-success">Success</span></td>
                            </tr>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime('-1 day')); ?></td>
                                <td>127.0.0.1</td>
                                <td>Chrome</td>
                                <td>Desktop</td>
                                <td><span class="badge badge-success">Success</span></td>
                            </tr>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime('-2 day')); ?></td>
                                <td>127.0.0.1</td>
                                <td>Chrome</td>
                                <td>Desktop</td>
                                <td><span class="badge badge-success">Success</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Two-Factor Authentication Modal -->
<div class="modal fade" id="twoFactorModal" tabindex="-1" role="dialog" aria-labelledby="twoFactorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="twoFactorModalLabel">Two-Factor Authentication</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Two-factor authentication adds an extra layer of security to your account by requiring more than just a password to sign in.</p>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i> This feature is coming soon. Please check back later.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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

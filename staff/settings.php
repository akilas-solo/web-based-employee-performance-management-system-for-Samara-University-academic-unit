<?php
/**
 * Samara University Academic Performance Evaluation System
 * Staff - Settings
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has staff role
if (!is_logged_in() || !has_role('staff')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];

// Get user settings
$settings = [];

// First check if the user_settings table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'user_settings'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}

// Create the table if it doesn't exist
if (!$table_exists) {
    $create_table = "CREATE TABLE IF NOT EXISTS user_settings (
        setting_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email_notifications TINYINT(1) DEFAULT 1,
        dashboard_widgets VARCHAR(50) DEFAULT 'default',
        theme VARCHAR(50) DEFAULT 'light',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $conn->query($create_table);
}

// Get user settings
$sql = "SELECT * FROM user_settings WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $settings = $result->fetch_assoc();
    } else {
        // Create default settings
        $sql = "INSERT INTO user_settings (user_id, email_notifications, dashboard_widgets, theme) VALUES (?, 1, 'default', 'light')";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }

        // Set default settings
        $settings = [
            'email_notifications' => 1,
            'dashboard_widgets' => 'default',
            'theme' => 'light'
        ];
    }
} else {
    // Set default settings if query fails
    $settings = [
        'email_notifications' => 1,
        'dashboard_widgets' => 'default',
        'theme' => 'light'
    ];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $dashboard_widgets = sanitize_input($_POST['dashboard_widgets']);
    $theme = sanitize_input($_POST['theme']);

    // Update settings
    $sql = "UPDATE user_settings SET email_notifications = ?, dashboard_widgets = ?, theme = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("issi", $email_notifications, $dashboard_widgets, $theme, $user_id);

        if ($stmt->execute()) {
            $success_message = 'Settings updated successfully.';

            // Update settings array
            $settings['email_notifications'] = $email_notifications;
            $settings['dashboard_widgets'] = $dashboard_widgets;
            $settings['theme'] = $theme;
        } else {
            $error_message = 'Error updating settings: ' . $conn->error;
        }
    } else {
        // If prepare fails, try to insert instead (in case the record doesn't exist)
        $sql = "INSERT INTO user_settings (user_id, email_notifications, dashboard_widgets, theme) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE email_notifications = VALUES(email_notifications),
                dashboard_widgets = VALUES(dashboard_widgets), theme = VALUES(theme)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iiss", $user_id, $email_notifications, $dashboard_widgets, $theme);

            if ($stmt->execute()) {
                $success_message = 'Settings updated successfully.';

                // Update settings array
                $settings['email_notifications'] = $email_notifications;
                $settings['dashboard_widgets'] = $dashboard_widgets;
                $settings['theme'] = $theme;
            } else {
                $error_message = 'Error updating settings: ' . $conn->error;
            }
        } else {
            $error_message = 'Error preparing query: ' . $conn->error;
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
            <div class="col-lg-8">
                <!-- Settings Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">User Settings</h6>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo $base_url; ?>staff/settings.php" method="post">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="email_notifications" name="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="email_notifications">Email Notifications</label>
                                </div>
                                <small class="form-text text-muted">Receive email notifications for new evaluations, comments, and system updates.</small>
                            </div>

                            <div class="form-group">
                                <label for="dashboard_widgets">Dashboard Widgets</label>
                                <select class="form-control" id="dashboard_widgets" name="dashboard_widgets">
                                    <option value="default" <?php echo $settings['dashboard_widgets'] === 'default' ? 'selected' : ''; ?>>Default</option>
                                    <option value="compact" <?php echo $settings['dashboard_widgets'] === 'compact' ? 'selected' : ''; ?>>Compact</option>
                                    <option value="detailed" <?php echo $settings['dashboard_widgets'] === 'detailed' ? 'selected' : ''; ?>>Detailed</option>
                                </select>
                                <small class="form-text text-muted">Choose how widgets are displayed on your dashboard.</small>
                            </div>

                            <div class="form-group">
                                <label for="theme">Theme</label>
                                <select class="form-control" id="theme" name="theme">
                                    <option value="light" <?php echo $settings['theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                                    <option value="dark" <?php echo $settings['theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                                    <option value="blue" <?php echo $settings['theme'] === 'blue' ? 'selected' : ''; ?>>Blue</option>
                                    <option value="green" <?php echo $settings['theme'] === 'green' ? 'selected' : ''; ?>>Green</option>
                                </select>
                                <small class="form-text text-muted">Choose your preferred theme for the dashboard.</small>
                            </div>

                            <div class="text-right mt-4">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Help Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Help & Support</h6>
                    </div>
                    <div class="card-body">
                        <p>Need help with the system? Contact the system administrator or check the tutorial for guidance.</p>
                        <a href="<?php echo $base_url; ?>tutorial.php" class="btn btn-info btn-block">
                            <i class="fas fa-graduation-cap mr-1"></i> View Tutorial
                        </a>
                        <hr>
                        <h6 class="font-weight-bold">Contact Support</h6>
                        <p><i class="fas fa-envelope mr-1"></i> support@samarauniversity.edu.et</p>
                        <p><i class="fas fa-phone mr-1"></i> +251-11-123-4567</p>
                    </div>
                </div>

                <!-- System Info Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">System Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>System Version:</strong> 1.0.0
                        </div>
                        <div class="mb-2">
                            <strong>Last Update:</strong> <?php echo date('F d, Y'); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Browser:</strong> <span id="browser-info">Detecting...</span>
                        </div>
                        <div class="mb-2">
                            <strong>Screen Resolution:</strong> <span id="screen-info">Detecting...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Detect browser
        var browserInfo = document.getElementById('browser-info');
        var userAgent = navigator.userAgent;
        var browserName;

        if (userAgent.match(/chrome|chromium|crios/i)) {
            browserName = "Chrome";
        } else if (userAgent.match(/firefox|fxios/i)) {
            browserName = "Firefox";
        } else if (userAgent.match(/safari/i)) {
            browserName = "Safari";
        } else if (userAgent.match(/opr\//i)) {
            browserName = "Opera";
        } else if (userAgent.match(/edg/i)) {
            browserName = "Edge";
        } else {
            browserName = "Unknown";
        }

        browserInfo.textContent = browserName;

        // Detect screen resolution
        var screenInfo = document.getElementById('screen-info');
        screenInfo.textContent = window.screen.width + 'x' + window.screen.height;
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

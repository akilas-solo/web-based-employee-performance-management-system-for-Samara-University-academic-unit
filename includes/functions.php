<?php
/**
 * Samara University Academic Performance Evaluation System
 * Utility Functions
 */

// Check if this is a direct access
if (!isset($GLOBALS['BASE_PATH'])) {
    require_once 'config.php';
}

/**
 * Sanitize user input
 *
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = $conn->real_escape_string($data);
    }
    return $data;
}

/**
 * Hash password securely
 *
 * @param string $password Plain text password
 * @return string Hashed password
 */
function password_hash_custom($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 *
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches hash
 */
function password_verify_custom($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate a random token
 *
 * @param int $length Length of token
 * @return string Random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if user is logged in
 *
 * @return bool True if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has admin role
 *
 * @return bool True if user is admin
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_ADMIN;
}

/**
 * Check if user has specific role
 *
 * @param string $role Role to check
 * @return bool True if user has role
 */
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Redirect to URL
 *
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Get user data by ID
 *
 * @param int $user_id User ID
 * @return array|bool User data or false if not found
 */
function get_user_by_id($user_id) {
    global $conn;

    $user_id = (int) $user_id;
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }

    return false;
}

/**
 * Get admin data by ID
 *
 * @param int $admin_id Admin ID
 * @return array|bool Admin data or false if not found
 */
function get_admin_by_id($admin_id) {
    global $conn;

    $admin_id = (int) $admin_id;
    $sql = "SELECT * FROM admin WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }

    return false;
}

/**
 * Display alert message
 *
 * @param string $message Message to display
 * @param string $type Alert type (success, danger, warning, info)
 * @param bool $dismissible Whether alert is dismissible
 * @return string HTML for alert
 */
function display_alert($message, $type = 'info', $dismissible = true) {
    $dismissible_class = $dismissible ? 'alert-dismissible fade show' : '';
    $dismiss_button = $dismissible ? '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' : '';

    return "
    <div class=\"alert alert-{$type} {$dismissible_class}\" role=\"alert\">
        {$message}
        {$dismiss_button}
    </div>";
}

/**
 * Set flash message in session
 *
 * @param string $message Message to set
 * @param string $type Message type (success, danger, warning, info)
 * @return void
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Display flash message from session
 *
 * @return string|null HTML for alert or null if no message
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return display_alert($flash['message'], $flash['type']);
    }
    return null;
}

/**
 * Format date
 *
 * @param string $date Date string
 * @param string $format Format string
 * @return string Formatted date
 */
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Get department name by ID
 *
 * @param int $department_id Department ID
 * @return string|bool Department name or false if not found
 */
function get_department_name($department_id) {
    global $conn;

    $department_id = (int) $department_id;
    $sql = "SELECT name FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['name'];
    }

    return false;
}

/**
 * Get college name by ID
 *
 * @param int $college_id College ID
 * @return string|bool College name or false if not found
 */
function get_college_name($college_id) {
    global $conn;

    $college_id = (int) $college_id;
    $sql = "SELECT name FROM colleges WHERE college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['name'];
    }

    return false;
}
?>

<?php
/**
 * Samara University Academic Performance Evaluation System
 * Management Header
 */

// Check if this is a direct access
if (!isset($GLOBALS['BASE_PATH'])) {
    require_once 'config.php';
}

// Check if user is logged in
if (!is_logged_in()) {
    redirect($base_url . 'login.php');
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';

// Set theme class based on role
$theme_class = '';
$role_color = '';

if ($user_role === 'admin') {
    $theme_class = 'admin-theme';
    $role_color = 'primary';
} elseif ($user_role === 'head_of_department') {
    $theme_class = 'head-theme';
    $role_color = 'info';
} elseif ($user_role === 'dean') {
    $theme_class = 'dean-theme';
    $role_color = 'purple';
} elseif ($user_role === 'college') {
    $theme_class = 'college-theme';
    $role_color = 'success';
} elseif ($user_role === 'hrm') {
    $theme_class = 'hrm-theme';
    $role_color = 'warning';
} elseif ($user_role === 'staff') {
    $theme_class = 'staff-theme';
    $role_color = 'indigo';
}

// Check if user is admin
$is_admin = ($user_role === 'admin');

// Get notifications count
$notifications_count = 0;
if ($user_id) {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $notifications_count = $row['count'];
    }
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php if ($is_admin): ?>
            Admin Panel -
        <?php elseif ($user_role == 'college'): ?>
            College Dashboard -
        <?php elseif ($user_role == 'dean'): ?>
            Dean Dashboard -
        <?php elseif ($user_role == 'head_of_department'): ?>
            Head of Department Dashboard -
        <?php elseif ($user_role == 'hrm'): ?>
            HR Management -
        <?php elseif ($user_role == 'staff'): ?>
            Staff Dashboard -
        <?php endif; ?>
        Samara University Academic Performance Evaluation System
    </title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/theme.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/management.css">

    <!-- Apply theme class to body -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('<?php echo $theme_class; ?>');
        });
    </script>
</head>
<body>
    <!-- Management Header -->
    <header class="management-header">
        <div class="management-header-inner">
            <div class="management-header-left">
                <!-- Sidebar Toggle Button (Mobile) -->
                <button id="sidebarMobileToggler" class="sidebar-toggler d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Sidebar Toggle Button (Desktop) -->
                <button id="sidebarDesktopToggler" class="sidebar-toggler d-none d-lg-block">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Brand with Logo -->
                <div class="management-brand">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo $base_url; ?>assets/images/logo.png" alt="Samara University" class="header-logo mr-2" style="height: 40px; background: white; padding: 3px; border-radius: 4px;">
                        <div>
                            <h5 class="mb-0 font-weight-bold">Samara University</h5>
                            <div class="small">
                                <?php if ($is_admin): ?>
                                    Admin Panel
                                <?php elseif ($user_role == 'college'): ?>
                                    College Dashboard
                                <?php elseif ($user_role == 'dean'): ?>
                                    Dean Dashboard
                                <?php elseif ($user_role == 'head_of_department'): ?>
                                    Head of Department Dashboard
                                <?php elseif ($user_role == 'hrm'): ?>
                                    HR Management
                                <?php elseif ($user_role == 'staff'): ?>
                                    Staff Dashboard
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb (optional) -->
                <nav aria-label="breadcrumb" class="d-none d-md-block ml-3">
                    <ol class="management-breadcrumb breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>">Home</a></li>
                        <?php if ($is_admin): ?>
                            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>admin/dashboard.php">Admin</a></li>
                        <?php elseif ($user_role == 'college'): ?>
                            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>college/dashboard.php">College</a></li>
                        <?php elseif ($user_role == 'dean'): ?>
                            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>dean/dashboard.php">Dean</a></li>
                        <?php elseif ($user_role == 'head_of_department'): ?>
                            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>head/dashboard.php">Head</a></li>
                        <?php elseif ($user_role == 'hrm'): ?>
                            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>hrm/dashboard.php">HRM</a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php
                            // Get page title based on current page
                            $page_title = '';
                            switch ($current_page) {
                                case 'dashboard.php':
                                    $page_title = 'Dashboard';
                                    break;
                                case 'profile.php':
                                    $page_title = 'Profile';
                                    break;
                                case 'settings.php':
                                    $page_title = 'Settings';
                                    break;
                                case 'users.php':
                                    $page_title = 'Users';
                                    break;
                                case 'departments.php':
                                    $page_title = 'Departments';
                                    break;
                                case 'colleges.php':
                                    $page_title = 'Colleges';
                                    break;
                                case 'evaluations.php':
                                    $page_title = 'Evaluations';
                                    break;
                                case 'reports.php':
                                    $page_title = 'Reports';
                                    break;
                                default:
                                    $page_title = ucfirst(str_replace('.php', '', $current_page));
                            }
                            echo $page_title;
                            ?>
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="management-header-right">
                <!-- Search Form -->
                <form id="searchForm" class="d-none d-md-flex mr-3" action="<?php echo $base_url; ?>search.php" method="GET">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search..." name="q" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                        <div class="input-group-append">
                            <button class="btn btn-sm" type="submit" style="background: rgba(255,255,255,0.1); border: none; color: white;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Notifications Dropdown -->
                <div class="notification-dropdown dropdown">
                    <button class="notification-dropdown-toggle" type="button" id="notificationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifications_count > 0): ?>
                            <span class="notification-badge"><?php echo $notifications_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notificationDropdown">
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            <?php if ($notifications_count > 0): ?>
                                <a href="#" class="mark-all-read text-muted small">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-divider"></div>
                        <?php
                        // Get notifications
                        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($notification = $result->fetch_assoc()) {
                                $is_read = $notification['is_read'] == 1;
                                $read_class = $is_read ? '' : 'unread';
                                $created_at = date('M d, g:i a', strtotime($notification['created_at']));

                                echo "
                                <a class='dropdown-item notification-item {$read_class}' href='#'>
                                    <div class='d-flex align-items-center'>
                                        <div class='notification-icon bg-" . ($is_read ? 'light' : 'theme') . " text-" . ($is_read ? 'muted' : 'white') . " rounded-circle'>
                                            <i class='fas fa-bell'></i>
                                        </div>
                                        <div class='notification-content ml-3'>
                                            <div class='notification-title'>{$notification['title']}</div>
                                            <div class='notification-text small'>{$notification['message']}</div>
                                            <div class='notification-time text-muted smaller'>{$created_at}</div>
                                        </div>
                                    </div>
                                </a>";
                            }
                        } else {
                            echo "<div class='dropdown-item text-center'>No notifications</div>";
                        }
                        ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center small" href="<?php echo $base_url; ?>notifications.php">View all notifications</a>
                    </div>
                </div>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <div class="user-dropdown" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="user-dropdown-avatar">
                            <?php if (!empty($profile_image)): ?>
                                <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $profile_image; ?>" alt="<?php echo $user_name; ?>">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-dropdown-info d-none d-md-flex">
                            <div class="user-dropdown-name"><?php echo $user_name; ?></div>
                            <div class="user-dropdown-role"><?php echo ucfirst($user_role); ?></div>
                        </div>
                        <div class="user-dropdown-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <div class="dropdown-item-text">
                            <div class="d-flex align-items-center">
                                <div class="user-dropdown-avatar mr-3">
                                    <?php if (!empty($profile_image)): ?>
                                        <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $profile_image; ?>" alt="<?php echo $user_name; ?>">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-weight-bold"><?php echo $user_name; ?></div>
                                    <div class="small text-muted"><?php echo $_SESSION['email']; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo $base_url . ($is_admin ? 'admin' : ($user_role == 'head_of_department' ? 'head' : $user_role)); ?>/profile.php">
                            <i class="fas fa-user-circle fa-fw mr-2"></i> Profile
                        </a>
                        <a class="dropdown-item" href="<?php echo $base_url . ($is_admin ? 'admin' : ($user_role == 'head_of_department' ? 'head' : $user_role)); ?>/settings.php">
                            <i class="fas fa-cog fa-fw mr-2"></i> Settings
                        </a>
                        <a class="dropdown-item" href="<?php echo $base_url; ?>activity-log.php">
                            <i class="fas fa-list fa-fw mr-2"></i> Activity Log
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo $base_url; ?>logout.php">
                            <i class="fas fa-sign-out-alt fa-fw mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

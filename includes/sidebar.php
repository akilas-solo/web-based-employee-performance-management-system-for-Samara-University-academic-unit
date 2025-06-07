<?php
/**
 * Samara University Academic Performance Evaluation System
 * Sidebar Navigation
 */

// Check if this is a direct access
if (!isset($GLOBALS['BASE_PATH'])) {
    require_once 'config.php';
}

// Define menu items based on user role
$menu_items = [];

if ($is_admin) {
    $menu_items = [
        [
            'title' => 'Dashboard',
            'icon' => 'tachometer-alt',
            'url' => $base_url . 'admin/dashboard.php',
            'active' => $current_page == 'dashboard.php'
        ],
        [
            'title' => 'Users',
            'icon' => 'users',
            'url' => $base_url . 'admin/users.php',
            'active' => $current_page == 'users.php' || $current_page == 'add_user.php' || $current_page == 'edit_user.php',
            'badge' => [
                'type' => 'danger',
                'count' => $notifications_count
            ]
        ],
        [
            'title' => 'Colleges',
            'icon' => 'university',
            'url' => $base_url . 'admin/colleges.php',
            'active' => $current_page == 'colleges.php' || $current_page == 'add_college.php' || $current_page == 'edit_college.php'
        ],
        [
            'title' => 'Departments',
            'icon' => 'building',
            'url' => $base_url . 'admin/departments.php',
            'active' => $current_page == 'departments.php' || $current_page == 'add_department.php' || $current_page == 'edit_department.php'
        ],
        [
            'title' => 'Evaluation Criteria',
            'icon' => 'clipboard-list',
            'url' => $base_url . 'admin/evaluation_criteria.php',
            'active' => $current_page == 'evaluation_criteria.php' || $current_page == 'add_criteria.php' || $current_page == 'edit_criteria.php'
        ],
        [
            'title' => 'Evaluation Periods',
            'icon' => 'calendar-alt',
            'url' => $base_url . 'admin/evaluation_periods.php',
            'active' => $current_page == 'evaluation_periods.php' || $current_page == 'add_period.php' || $current_page == 'edit_period.php'
        ],
        [
            'title' => 'Reports',
            'icon' => 'chart-bar',
            'url' => $base_url . 'admin/reports.php',
            'active' => $current_page == 'reports.php'
        ],
        [
            'title' => 'System Settings',
            'icon' => 'cogs',
            'url' => $base_url . 'admin/settings.php',
            'active' => $current_page == 'settings.php'
        ],
        [
            'title' => 'Tutorial',
            'icon' => 'graduation-cap',
            'url' => $base_url . 'tutorial.php',
            'active' => $current_page == 'tutorial.php'
        ]
    ];
} elseif ($user_role == 'college') {
    $menu_items = [
        [
            'title' => 'Dashboard',
            'icon' => 'tachometer-alt',
            'url' => $base_url . 'college/dashboard.php',
            'active' => $current_page == 'dashboard.php'
        ],
        [
            'title' => 'Departments',
            'icon' => 'building',
            'url' => $base_url . 'college/departments.php',
            'active' => $current_page == 'departments.php'
        ],
        [
            'title' => 'Deans',
            'icon' => 'user-tie',
            'url' => $base_url . 'college/deans.php',
            'active' => $current_page == 'deans.php'
        ],
        [
            'title' => 'Evaluations',
            'icon' => 'clipboard-check',
            'url' => $base_url . 'college/evaluations.php',
            'active' => $current_page == 'evaluations.php' || $current_page == 'evaluation_form.php'
        ],
        [
            'title' => 'Reports',
            'icon' => 'chart-bar',
            'url' => $base_url . 'college/reports.php',
            'active' => $current_page == 'reports.php'
        ],
        [
            'title' => 'Tutorial',
            'icon' => 'graduation-cap',
            'url' => $base_url . 'tutorial.php',
            'active' => $current_page == 'tutorial.php'
        ]
    ];
} elseif ($user_role == 'dean') {
    $menu_items = [
        [
            'title' => 'Dashboard',
            'icon' => 'tachometer-alt',
            'url' => $base_url . 'dean/dashboard.php',
            'active' => $current_page == 'dashboard.php'
        ],
        [
            'title' => 'Department Heads',
            'icon' => 'user-tie',
            'url' => $base_url . 'dean/department_heads.php',
            'active' => $current_page == 'department_heads.php'
        ],
        [
            'title' => 'Evaluations',
            'icon' => 'clipboard-check',
            'url' => $base_url . 'dean/evaluations.php',
            'active' => $current_page == 'evaluations.php' || $current_page == 'evaluation_form.php'
        ],
        [
            'title' => 'Reports',
            'icon' => 'chart-bar',
            'url' => $base_url . 'dean/reports.php',
            'active' => $current_page == 'reports.php'
        ],
        [
            'title' => 'Tutorial',
            'icon' => 'graduation-cap',
            'url' => $base_url . 'tutorial.php',
            'active' => $current_page == 'tutorial.php'
        ]
    ];
} elseif ($user_role == 'head_of_department') {
    $menu_items = [
        [
            'title' => 'Dashboard',
            'icon' => 'tachometer-alt',
            'url' => $base_url . 'head/dashboard.php',
            'active' => $current_page == 'dashboard.php'
        ],
        [
            'title' => 'Staff',
            'icon' => 'users',
            'url' => $base_url . 'head/staff.php',
            'active' => $current_page == 'staff.php'
        ],
        [
            'title' => 'Evaluations',
            'icon' => 'clipboard-check',
            'url' => $base_url . 'head/evaluations.php',
            'active' => $current_page == 'evaluations.php' || $current_page == 'evaluation_form.php'
        ],
        [
            'title' => 'Reports',
            'icon' => 'chart-bar',
            'url' => $base_url . 'head/reports.php',
            'active' => $current_page == 'reports.php'
        ],
        [
            'title' => 'Tutorial',
            'icon' => 'graduation-cap',
            'url' => $base_url . 'tutorial.php',
            'active' => $current_page == 'tutorial.php'
        ]
    ];
} elseif ($user_role == 'hrm') {
    $menu_items = [
        [
            'title' => 'Dashboard',
            'icon' => 'tachometer-alt',
            'url' => $base_url . 'hrm/dashboard.php',
            'active' => $current_page == 'dashboard.php'
        ],
        [
            'title' => 'Colleges',
            'icon' => 'university',
            'url' => $base_url . 'hrm/colleges.php',
            'active' => $current_page == 'colleges.php'
        ],
        [
            'title' => 'Departments',
            'icon' => 'building',
            'url' => $base_url . 'hrm/departments.php',
            'active' => $current_page == 'departments.php'
        ],
        [
            'title' => 'Evaluations',
            'icon' => 'clipboard-check',
            'url' => $base_url . 'hrm/evaluations.php',
            'active' => $current_page == 'evaluations.php'
        ],
        [
            'title' => 'Reports',
            'icon' => 'chart-bar',
            'url' => $base_url . 'hrm/reports.php',
            'active' => $current_page == 'reports.php'
        ],
        [
            'title' => 'Tutorial',
            'icon' => 'graduation-cap',
            'url' => $base_url . 'tutorial.php',
            'active' => $current_page == 'tutorial.php'
        ]
    ];
} elseif ($user_role == 'staff') {
    $menu_items = [
        [
            'title' => 'Dashboard',
            'icon' => 'tachometer-alt',
            'url' => $base_url . 'staff/dashboard.php',
            'active' => $current_page == 'dashboard.php'
        ],
        [
            'title' => 'My Evaluations',
            'icon' => 'clipboard-check',
            'url' => $base_url . 'staff/evaluations.php',
            'active' => $current_page == 'evaluations.php' || $current_page == 'evaluation_details.php'
        ],
        [
            'title' => 'Performance Reports',
            'icon' => 'chart-line',
            'url' => $base_url . 'staff/reports.php',
            'active' => $current_page == 'reports.php'
        ],
        [
            'title' => 'Tutorial',
            'icon' => 'graduation-cap',
            'url' => $base_url . 'tutorial.php',
            'active' => $current_page == 'tutorial.php'
        ]
    ];
} else {
    // Default menu for other roles
    $menu_items = [
        [
            'title' => 'Tutorial',
            'icon' => 'graduation-cap',
            'url' => $base_url . 'tutorial.php',
            'active' => $current_page == 'tutorial.php'
        ]
    ];
}
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <button id="sidebarToggler" class="btn btn-link d-lg-none">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <?php if (!empty($profile_image)): ?>
                <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $profile_image; ?>" alt="<?php echo $user_name; ?>">
            <?php else: ?>
                <div class="user-avatar-placeholder bg-<?php echo $role_color; ?>">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <div class="user-name"><?php echo $user_name; ?></div>
            <div class="user-role text-<?php echo $role_color; ?>">
                <?php echo $is_admin ? 'Administrator' : ucfirst($user_role); ?>
            </div>
        </div>
    </div>

    <div class="sidebar-divider">
        <div class="sidebar-divider-text">Main Menu</div>
    </div>

    <ul class="sidebar-nav">
        <?php foreach ($menu_items as $item): ?>
            <li class="sidebar-item <?php echo $item['active'] ? 'active' : ''; ?>">
                <a href="<?php echo $item['url']; ?>" class="sidebar-link">
                    <i class="sidebar-icon fas fa-<?php echo $item['icon']; ?>"></i>
                    <span class="sidebar-text"><?php echo $item['title']; ?></span>
                    <?php if (isset($item['badge'])): ?>
                        <span class="badge badge-<?php echo $item['badge']['type']; ?> sidebar-badge">
                            <?php echo $item['badge']['count']; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-divider">
        <div class="sidebar-divider-text">Account</div>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <a href="<?php echo $base_url . ($is_admin ? 'admin' : ($user_role == 'head_of_department' ? 'head' : $user_role)); ?>/profile.php" class="sidebar-link">
                <i class="sidebar-icon fas fa-user-circle"></i>
                <span class="sidebar-text">Profile</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <a href="<?php echo $base_url . ($is_admin ? 'admin' : ($user_role == 'head_of_department' ? 'head' : $user_role)); ?>/settings.php" class="sidebar-link">
                <i class="sidebar-icon fas fa-cogs"></i>
                <span class="sidebar-text">Settings</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo $base_url; ?>logout.php" class="sidebar-link">
                <i class="sidebar-icon fas fa-sign-out-alt"></i>
                <span class="sidebar-text">Logout</span>
            </a>
        </li>
    </ul>
</div>

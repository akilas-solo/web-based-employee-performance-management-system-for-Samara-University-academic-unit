<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - View Department
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
$department_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if department_id is provided
if ($department_id <= 0) {
    redirect($base_url . 'admin/departments.php');
}

// Get department information
$department = null;
$sql = "SELECT d.*, c.name as college_name 
        FROM departments d 
        JOIN colleges c ON d.college_id = c.college_id 
        WHERE d.department_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $department = $result->fetch_assoc();
} else {
    redirect($base_url . 'admin/departments.php');
}

// Get users in the department
$users = [];
$sql = "SELECT u.*, 
        CASE 
            WHEN u.role = 'head_of_department' THEN 1
            WHEN u.role = 'instructor' THEN 2
            ELSE 3
        END as role_order
        FROM users u 
        WHERE u.department_id = ? 
        ORDER BY role_order, u.full_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get department head
$department_head = null;
foreach ($users as $user) {
    if ($user['role'] === 'head_of_department') {
        $department_head = $user;
        break;
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
            <h1 class="h3 mb-0 text-gray-800">Department Details</h1>
            <div>
                <a href="<?php echo $base_url; ?>admin/departments.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Departments
                </a>
                <a href="<?php echo $base_url; ?>admin/edit_department.php?id=<?php echo $department_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-edit fa-sm text-white-50 mr-1"></i> Edit Department
                </a>
            </div>
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
            <!-- Department Info Card -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Department Information</h6>
                    </div>
                    <div class="card-body">
                        <h4 class="font-weight-bold text-primary mb-3"><?php echo $department['name']; ?></h4>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Code:</div>
                            <div class="col-md-8"><?php echo $department['code']; ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">College:</div>
                            <div class="col-md-8"><?php echo $department['college_name']; ?></div>
                        </div>
                        <?php if (!empty($department['description'])): ?>
                            <div class="row mb-3">
                                <div class="col-md-4 font-weight-bold">Description:</div>
                                <div class="col-md-8"><?php echo $department['description']; ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Created:</div>
                            <div class="col-md-8"><?php echo date('M d, Y', strtotime($department['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Stats Card -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Department Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Total Users</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($users); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-users fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Instructors</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php 
                                                    $instructor_count = 0;
                                                    foreach ($users as $user) {
                                                        if ($user['role'] === 'instructor') {
                                                            $instructor_count++;
                                                        }
                                                    }
                                                    echo $instructor_count;
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="chart-pie pt-4">
                            <canvas id="userRolesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Head Card -->
        <?php if ($department_head): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Department Head</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center">
                            <?php if (!empty($department_head['profile_image'])): ?>
                                <img class="img-profile rounded-circle mb-3" src="<?php echo $base_url; ?>uploads/profiles/<?php echo $department_head['profile_image']; ?>" alt="Profile Image" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-10">
                            <h5 class="font-weight-bold text-primary"><?php echo $department_head['full_name']; ?></h5>
                            <p>
                                <strong>Email:</strong> <?php echo $department_head['email']; ?><br>
                                <?php if (!empty($department_head['phone'])): ?>
                                    <strong>Phone:</strong> <?php echo $department_head['phone']; ?><br>
                                <?php endif; ?>
                                <?php if (!empty($department_head['position'])): ?>
                                    <strong>Position:</strong> <?php echo $department_head['position']; ?><br>
                                <?php endif; ?>
                                <strong>Status:</strong> 
                                <span class="badge badge-<?php echo ($department_head['status'] == 1) ? 'success' : 'danger'; ?>">
                                    <?php echo ($department_head['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Users Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Users</h6>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['full_name']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></td>
                                        <td><?php echo !empty($user['position']) ? $user['position'] : 'N/A'; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($user['status'] == 1) ? 'success' : 'danger'; ?>">
                                                <?php echo ($user['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No users found in this department.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/datatables/jquery.dataTables.min.js',
    'assets/js/datatables/dataTables.bootstrap4.min.js',
    'assets/js/chart.js/Chart.min.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTables
        $('#usersTable').DataTable();
        
        // Chart.js - User Roles
        var userRolesCtx = document.getElementById('userRolesChart');
        
        // Count users by role
        var roles = {};
        <?php foreach ($users as $user): ?>
            var role = '<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>';
            roles[role] = (roles[role] || 0) + 1;
        <?php endforeach; ?>
        
        var roleLabels = Object.keys(roles);
        var roleCounts = Object.values(roles);
        var backgroundColors = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
        ];
        
        // Create User Roles Chart
        if (userRolesCtx) {
            new Chart(userRolesCtx, {
                type: 'doughnut',
                data: {
                    labels: roleLabels,
                    datasets: [{
                        data: roleCounts,
                        backgroundColor: backgroundColors,
                        hoverBackgroundColor: backgroundColors,
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                    },
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    cutoutPercentage: 70,
                },
            });
        }
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

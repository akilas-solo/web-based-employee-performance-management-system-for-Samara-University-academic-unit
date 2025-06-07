<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - View College
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
$college_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if college_id is provided
if ($college_id <= 0) {
    redirect($base_url . 'admin/colleges.php');
}

// Get college information
$college = null;
$sql = "SELECT * FROM colleges WHERE college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $college = $result->fetch_assoc();
} else {
    redirect($base_url . 'admin/colleges.php');
}

// Get departments in the college
$departments = [];
$sql = "SELECT d.*, 
        (SELECT COUNT(*) FROM users u WHERE u.department_id = d.department_id) as user_count
        FROM departments d 
        WHERE d.college_id = ? 
        ORDER BY d.name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Get users in the college
$users = [];
$sql = "SELECT u.*, 
        CASE 
            WHEN u.role = 'dean' THEN 1
            WHEN u.role = 'head_of_department' THEN 2
            WHEN u.role = 'instructor' THEN 3
            ELSE 4
        END as role_order
        FROM users u 
        WHERE u.college_id = ? 
        ORDER BY role_order, u.full_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">College Details</h1>
            <div>
                <a href="<?php echo $base_url; ?>admin/colleges.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Colleges
                </a>
                <a href="<?php echo $base_url; ?>admin/edit_college.php?id=<?php echo $college_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-edit fa-sm text-white-50 mr-1"></i> Edit College
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
            <!-- College Info Card -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">College Information</h6>
                    </div>
                    <div class="card-body">
                        <h4 class="font-weight-bold text-primary mb-3"><?php echo $college['name']; ?></h4>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Code:</div>
                            <div class="col-md-8"><?php echo $college['code']; ?></div>
                        </div>
                        <?php if (!empty($college['description'])): ?>
                            <div class="row mb-3">
                                <div class="col-md-4 font-weight-bold">Description:</div>
                                <div class="col-md-8"><?php echo $college['description']; ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($college['vision'])): ?>
                            <div class="row mb-3">
                                <div class="col-md-4 font-weight-bold">Vision:</div>
                                <div class="col-md-8"><?php echo $college['vision']; ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($college['mission'])): ?>
                            <div class="row mb-3">
                                <div class="col-md-4 font-weight-bold">Mission:</div>
                                <div class="col-md-8"><?php echo $college['mission']; ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Created:</div>
                            <div class="col-md-8"><?php echo date('M d, Y', strtotime($college['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- College Stats Card -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">College Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Departments</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($departments); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-building fa-2x text-gray-300"></i>
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
                                                    Users</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($users); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-users fa-2x text-gray-300"></i>
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

        <!-- Departments Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Departments</h6>
            </div>
            <div class="card-body">
                <?php if (count($departments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="departmentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Users</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departments as $department): ?>
                                    <tr>
                                        <td><?php echo $department['name']; ?></td>
                                        <td><?php echo $department['code']; ?></td>
                                        <td><?php echo $department['user_count']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($department['created_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>admin/view_department.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/edit_department.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No departments found in this college. <a href="<?php echo $base_url; ?>admin/add_department.php">Add a department</a> to get started.</p>
                <?php endif; ?>
            </div>
        </div>

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
                                    <th>Department</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['full_name']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></td>
                                        <td>
                                            <?php 
                                            if ($user['department_id']) {
                                                foreach ($departments as $dept) {
                                                    if ($dept['department_id'] == $user['department_id']) {
                                                        echo $dept['name'];
                                                        break;
                                                    }
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
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
                    <p class="text-center">No users found in this college.</p>
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
        $('#departmentsTable').DataTable();
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

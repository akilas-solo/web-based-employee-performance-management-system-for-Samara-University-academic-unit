<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM Dashboard
 */

// Include configuration file - use direct path to avoid memory issues
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has hrm role
if (!is_logged_in() || !has_role('hrm')) {
    redirect($base_url . 'login.php');
}

// Get statistics
$total_colleges = 0;
$total_departments = 0;
$total_users = 0;
$total_evaluations = 0;
$pending_reports = 0;
$approved_reports = 0;

// Get total colleges
$sql = "SELECT COUNT(*) as count FROM colleges";
$result = $conn->query($sql);
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $total_colleges = $row['count'];
}

// Get total departments
$sql = "SELECT COUNT(*) as count FROM departments";
$result = $conn->query($sql);
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $total_departments = $row['count'];
}

// Get total users
$sql = "SELECT COUNT(*) as count FROM users WHERE role != 'hrm'";
$result = $conn->query($sql);
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $total_users = $row['count'];
}

// Get total evaluations
$sql = "SELECT COUNT(*) as count FROM evaluations";
$result = $conn->query($sql);
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $total_evaluations = $row['count'];
}

// Get pending reports
$sql = "SELECT COUNT(*) as count FROM performance_reports WHERE status = 'pending'";
$result = $conn->query($sql);
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $pending_reports = $row['count'];
}

// Get approved reports
$sql = "SELECT COUNT(*) as count FROM performance_reports WHERE status = 'approved'";
$result = $conn->query($sql);
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $approved_reports = $row['count'];
}

// Get recent performance reports
$recent_reports = [];
$sql = "SELECT pr.*, u.full_name, d.name as department_name, c.name as college_name
        FROM performance_reports pr
        JOIN users u ON pr.user_id = u.user_id
        LEFT JOIN departments d ON pr.department_id = d.department_id
        LEFT JOIN colleges c ON pr.college_id = c.college_id
        ORDER BY pr.created_at DESC LIMIT 5";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_reports[] = $row;
    }
}

// Get college performance
$college_performance = [];
$sql = "SELECT c.name, AVG(pr.average_score) as avg_score
        FROM performance_reports pr
        JOIN colleges c ON pr.college_id = c.college_id
        GROUP BY c.college_id
        ORDER BY avg_score DESC LIMIT 5";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $college_performance[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">HRM Dashboard</h1>
            <a href="<?php echo $base_url; ?>hrm/reports.php" class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm">
                <i class="fas fa-download fa-sm text-white-50 mr-1"></i> Generate Report
            </a>
        </div>

        <!-- HRM Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">HRM Information</h6>
            </div>
            <div class="card-body">
                <h4 class="text-theme">Human Resource Management</h4>
                <p class="mb-0">Welcome to the HRM Dashboard. Here you can monitor university-wide performance evaluations, generate reports, and analyze trends.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <!-- Colleges Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-theme shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-theme text-uppercase mb-1">
                                    Colleges</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_colleges; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-university fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Departments Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-theme shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-theme text-uppercase mb-1">
                                    Departments</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_departments; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-building fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-theme shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-theme text-uppercase mb-1">
                                    Academic Staff</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evaluations Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-theme shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-theme text-uppercase mb-1">
                                    Total Evaluations</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_evaluations; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Statistics -->
        <div class="row">
            <!-- Pending Reports Card -->
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Pending Reports</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_reports; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approved Reports Card -->
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Approved Reports</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approved_reports; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Recent Performance Reports -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Recent Performance Reports</h6>
                        <a href="<?php echo $base_url; ?>hrm/reports.php" class="btn btn-sm btn-theme">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_reports) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Department/College</th>
                                            <th>Period</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_reports as $report): ?>
                                            <tr>
                                                <td><?php echo $report['full_name']; ?></td>
                                                <td>
                                                    <?php
                                                    if (!empty($report['department_name'])) {
                                                        echo $report['department_name'];
                                                    } elseif (!empty($report['college_name'])) {
                                                        echo $report['college_name'];
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $report['report_period']; ?></td>
                                                <td><?php echo $report['average_score']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        switch ($report['status']) {
                                                            case 'pending':
                                                                echo 'warning';
                                                                break;
                                                            case 'approved':
                                                                echo 'success';
                                                                break;
                                                            case 'rejected':
                                                                echo 'danger';
                                                                break;
                                                            default:
                                                                echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($report['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No performance reports found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- College Performance -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">College Performance</h6>
                        <a href="<?php echo $base_url; ?>hrm/colleges.php" class="btn btn-sm btn-theme">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($college_performance) > 0): ?>
                            <?php foreach ($college_performance as $college): ?>
                                <h4 class="small font-weight-bold">
                                    <?php echo $college['name']; ?>
                                    <span class="float-right"><?php echo number_format($college['avg_score'], 2); ?></span>
                                </h4>
                                <div class="progress mb-4">
                                    <div class="progress-bar bg-theme" role="progressbar" style="width: <?php echo ($college['avg_score'] / 5) * 100; ?>%"
                                        aria-valuenow="<?php echo $college['avg_score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center">No college performance data available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

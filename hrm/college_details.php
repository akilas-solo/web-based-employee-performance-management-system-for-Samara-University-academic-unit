<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - College Details
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has hrm role
if (!is_logged_in() || !has_role('hrm')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$college_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if college_id is provided
if ($college_id <= 0) {
    redirect($base_url . 'hrm/colleges.php');
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
    redirect($base_url . 'hrm/colleges.php');
}

// Get departments in the college with performance data
$departments = [];
$sql = "SELECT d.*,
        (SELECT COUNT(*) FROM users u WHERE u.department_id = d.department_id) as user_count,
        (SELECT AVG(e.total_score) FROM evaluations e
         JOIN users u ON e.evaluatee_id = u.user_id
         WHERE u.department_id = d.department_id) as avg_score,
        (SELECT COUNT(*) FROM evaluations e
         JOIN users u ON e.evaluatee_id = u.user_id
         WHERE u.department_id = d.department_id) as evaluation_count
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

// Get users in the college with role distribution
$users = [];
$sql = "SELECT u.*, d.name as department_name,
        CASE
            WHEN u.role = 'dean' THEN 1
            WHEN u.role = 'head_of_department' THEN 2
            WHEN u.role = 'instructor' THEN 3
            ELSE 4
        END as role_order
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.department_id
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

// Get college performance statistics
$performance_stats = [];
$sql = "SELECT
        COUNT(DISTINCT e.evaluation_id) as total_evaluations,
        AVG(e.total_score) as avg_score,
        COUNT(DISTINCT e.evaluatee_id) as evaluated_users,
        COUNT(DISTINCT CASE WHEN e.total_score >= 4 THEN e.evaluatee_id END) as excellent_performers,
        COUNT(DISTINCT CASE WHEN e.total_score >= 3 AND e.total_score < 4 THEN e.evaluatee_id END) as good_performers,
        COUNT(DISTINCT CASE WHEN e.total_score < 3 THEN e.evaluatee_id END) as needs_improvement
        FROM evaluations e
        JOIN users u ON e.evaluatee_id = u.user_id
        WHERE u.college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $performance_stats = $result->fetch_assoc();
}

// Get recent evaluations for the college
$recent_evaluations = [];
$sql = "SELECT e.*, u.full_name as evaluatee_name, u.role as evaluatee_role,
        d.name as department_name, ep.title as period_name
        FROM evaluations e
        JOIN users u ON e.evaluatee_id = u.user_id
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN evaluation_periods ep ON e.period_id = ep.period_id
        WHERE u.college_id = ?
        ORDER BY e.created_at DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_evaluations[] = $row;
    }
}

// Include header
include_once dirname(__DIR__) . '/includes/header_management.php';

// Include sidebar
include_once dirname(__DIR__) . '/includes/sidebar.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">College Details</h1>
            <div>
                <a href="<?php echo $base_url; ?>hrm/colleges.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Colleges
                </a>
                <a href="<?php echo $base_url; ?>hrm/college_report.php?id=<?php echo $college_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
                    <i class="fas fa-chart-bar fa-sm text-white-50 mr-1"></i> Generate Report
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
                        <h6 class="m-0 font-weight-bold text-theme">College Information</h6>
                    </div>
                    <div class="card-body">
                        <h4 class="font-weight-bold text-theme mb-3"><?php echo $college['name']; ?></h4>
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
                            <div class="col-md-4 font-weight-bold">Established:</div>
                            <div class="col-md-8"><?php echo date('M d, Y', strtotime($college['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- College Performance Stats Card -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Performance Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Avg. Score</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo $performance_stats['avg_score'] ? number_format($performance_stats['avg_score'], 2) : 'N/A'; ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-star fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Evaluations</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $performance_stats['total_evaluations']; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="chart-pie pt-4">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards Row -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
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
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Staff</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($users); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Evaluated Staff</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $performance_stats['evaluated_users']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Excellent Performers</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $performance_stats['excellent_performers']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-trophy fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Departments Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Departments</h6>
            </div>
            <div class="card-body">
                <?php if (count($departments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="departmentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Staff</th>
                                    <th>Evaluations</th>
                                    <th>Avg. Performance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departments as $department): ?>
                                    <tr>
                                        <td><?php echo $department['name']; ?></td>
                                        <td><?php echo $department['code']; ?></td>
                                        <td><?php echo $department['user_count']; ?></td>
                                        <td><?php echo $department['evaluation_count']; ?></td>
                                        <td>
                                            <?php if ($department['avg_score']): ?>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-theme" role="progressbar"
                                                        style="width: <?php echo ($department['avg_score'] / 5) * 100; ?>%"
                                                        aria-valuenow="<?php echo $department['avg_score']; ?>"
                                                        aria-valuemin="0" aria-valuemax="5">
                                                        <?php echo number_format($department['avg_score'], 2); ?>/5
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No data</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>hrm/department_details.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>hrm/staff.php?department_id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-primary" title="View Staff">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>hrm/department_report.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-success" title="Generate Report">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No departments found in this college.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Staff Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Staff Members</h6>
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
                                        <td><?php echo $user['department_name'] ? $user['department_name'] : 'N/A'; ?></td>
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
                    <p class="text-center">No staff members found in this college.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Evaluations Card -->
        <?php if (count($recent_evaluations) > 0): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Recent Evaluations</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="evaluationsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Staff Member</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Period</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_evaluations as $evaluation): ?>
                                <tr>
                                    <td><?php echo $evaluation['evaluatee_name']; ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluatee_role'])); ?></td>
                                    <td><?php echo $evaluation['department_name'] ? $evaluation['department_name'] : 'N/A'; ?></td>
                                    <td><?php echo $evaluation['period_name'] ? $evaluation['period_name'] : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                            if ($evaluation['total_score'] >= 4) echo 'success';
                                            elseif ($evaluation['total_score'] >= 3) echo 'warning';
                                            else echo 'danger';
                                        ?>">
                                            <?php echo number_format($evaluation['total_score'], 2); ?>/5
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js',
    $base_url . 'assets/js/datatables.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTables
        $('#departmentsTable').DataTable();
        $('#usersTable').DataTable();
        $('#evaluationsTable').DataTable();

        // Chart.js - Performance Distribution
        var performanceCtx = document.getElementById('performanceChart');

        // Performance data
        var excellentPerformers = <?php echo $performance_stats['excellent_performers']; ?>;
        var goodPerformers = <?php echo $performance_stats['good_performers']; ?>;
        var needsImprovement = <?php echo $performance_stats['needs_improvement']; ?>;

        // Create Performance Chart
        if (performanceCtx) {
            new Chart(performanceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Excellent (4.0+)', 'Good (3.0-3.9)', 'Needs Improvement (<3.0)'],
                    datasets: [{
                        data: [excellentPerformers, goodPerformers, needsImprovement],
                        backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                        hoverBackgroundColor: ['#17a673', '#dda20a', '#c0392b'],
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
include_once dirname(__DIR__) . '/includes/footer_management.php';
?>

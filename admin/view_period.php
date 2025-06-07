<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - View Evaluation Period
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
$period_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if period_id is provided
if ($period_id <= 0) {
    redirect($base_url . 'admin/evaluation_periods.php');
}

// Get period information
$period = null;
$sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $period_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $period = $result->fetch_assoc();
} else {
    redirect($base_url . 'admin/evaluation_periods.php');
}

// Get evaluations for this period
$evaluations = [];
$sql = "SELECT e.*,
        u1.full_name as evaluator_name,
        u2.full_name as evaluatee_name,
        u1.role as evaluator_role,
        u2.role as evaluatee_role,
        d.name as department_name,
        c.name as college_name
        FROM evaluations e
        JOIN users u1 ON e.evaluator_id = u1.user_id
        JOIN users u2 ON e.evaluatee_id = u2.user_id
        LEFT JOIN departments d ON u2.department_id = d.department_id
        LEFT JOIN colleges c ON u2.college_id = c.college_id
        WHERE e.period_id = ?
        ORDER BY e.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $period_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
    }
}

// Get statistics
$total_evaluations = count($evaluations);
$completed_evaluations = 0;
$in_progress_evaluations = 0;
$pending_evaluations = 0;
$avg_score = 0;
$total_score = 0;

foreach ($evaluations as $evaluation) {
    if ($evaluation['status'] === 'submitted' || $evaluation['status'] === 'reviewed' || $evaluation['status'] === 'approved') {
        $completed_evaluations++;
        $total_score += $evaluation['total_score'];
    } elseif ($evaluation['status'] === 'draft') {
        $in_progress_evaluations++;
    } else {
        $pending_evaluations++;
    }
}

if ($completed_evaluations > 0) {
    $avg_score = $total_score / $completed_evaluations;
}

// Get evaluations by role
$evaluations_by_role = [
    'dean' => 0,
    'head_of_department' => 0,
    'instructor' => 0,
    'college' => 0,
    'hrm' => 0,
    'admin' => 0
];

foreach ($evaluations as $evaluation) {
    $role = $evaluation['evaluatee_role'];
    if (isset($evaluations_by_role[$role])) {
        $evaluations_by_role[$role]++;
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
            <h1 class="h3 mb-0 text-gray-800">Evaluation Period Details</h1>
            <div>
                <a href="<?php echo $base_url; ?>admin/evaluation_periods.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Periods
                </a>
                <a href="<?php echo $base_url; ?>admin/edit_period.php?id=<?php echo $period_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-edit fa-sm text-white-50 mr-1"></i> Edit Period
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
            <!-- Period Info Card -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Period Information</h6>
                    </div>
                    <div class="card-body">
                        <h4 class="font-weight-bold text-primary mb-3"><?php echo $period['title']; ?></h4>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Academic Year:</div>
                            <div class="col-md-8"><?php echo $period['academic_year']; ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Semester:</div>
                            <div class="col-md-8"><?php echo $period['semester']; ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Start Date:</div>
                            <div class="col-md-8"><?php echo date('M d, Y', strtotime($period['start_date'])); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">End Date:</div>
                            <div class="col-md-8"><?php echo date('M d, Y', strtotime($period['end_date'])); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Status:</div>
                            <div class="col-md-8">
                                <span class="badge badge-<?php echo ($period['status'] == 'active') ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($period['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($period['description'])): ?>
                            <div class="row mb-3">
                                <div class="col-md-4 font-weight-bold">Description:</div>
                                <div class="col-md-8"><?php echo $period['description']; ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Created:</div>
                            <div class="col-md-8"><?php echo date('M d, Y', strtotime($period['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Stats Card -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Evaluation Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Total Evaluations</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_evaluations; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                                                    Average Score</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($avg_score, 2); ?>/5.00</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-star fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="chart-pie pt-4">
                                    <canvas id="evaluationStatusChart"></canvas>
                                </div>
                                <div class="mt-4 text-center small">
                                    <span class="mr-2">
                                        <i class="fas fa-circle text-success"></i> Completed (<?php echo $completed_evaluations; ?>)
                                    </span>
                                    <span class="mr-2">
                                        <i class="fas fa-circle text-warning"></i> In Progress (<?php echo $in_progress_evaluations; ?>)
                                    </span>
                                    <span class="mr-2">
                                        <i class="fas fa-circle text-secondary"></i> Pending (<?php echo $pending_evaluations; ?>)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="chart-bar">
                            <canvas id="evaluationsByRoleChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Evaluations Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Evaluations</h6>
            </div>
            <div class="card-body">
                <?php if (count($evaluations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="evaluationsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Evaluator</th>
                                    <th>Evaluatee</th>
                                    <th>Department</th>
                                    <th>College</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evaluations as $evaluation): ?>
                                    <tr>
                                        <td>
                                            <?php echo $evaluation['evaluator_name']; ?>
                                            <small class="d-block text-muted"><?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluator_role'])); ?></small>
                                        </td>
                                        <td>
                                            <?php echo $evaluation['evaluatee_name']; ?>
                                            <small class="d-block text-muted"><?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluatee_role'])); ?></small>
                                        </td>
                                        <td><?php echo $evaluation['department_name'] ?? 'N/A'; ?></td>
                                        <td><?php echo $evaluation['college_name'] ?? 'N/A'; ?></td>
                                        <td><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo ($evaluation['status'] == 'submitted' || $evaluation['status'] == 'reviewed' || $evaluation['status'] == 'approved') ? 'success' : 
                                                    (($evaluation['status'] == 'draft') ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($evaluation['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>admin/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No evaluations found for this period.</p>
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
        // Initialize DataTable
        $('#evaluationsTable').DataTable();
        
        // Chart.js - Evaluation Status
        var evaluationStatusCtx = document.getElementById('evaluationStatusChart');
        var evaluationsByRoleCtx = document.getElementById('evaluationsByRoleChart');
        
        // Create Evaluation Status Chart
        if (evaluationStatusCtx) {
            new Chart(evaluationStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Pending'],
                    datasets: [{
                        data: [
                            <?php echo $completed_evaluations; ?>,
                            <?php echo $in_progress_evaluations; ?>,
                            <?php echo $pending_evaluations; ?>
                        ],
                        backgroundColor: ['#1cc88a', '#f6c23e', '#858796'],
                        hoverBackgroundColor: ['#17a673', '#dda20a', '#6e707e'],
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
                        display: false
                    },
                    cutoutPercentage: 70,
                },
            });
        }
        
        // Create Evaluations by Role Chart
        if (evaluationsByRoleCtx) {
            new Chart(evaluationsByRoleCtx, {
                type: 'bar',
                data: {
                    labels: ['Dean', 'Head of Department', 'Instructor', 'College', 'HRM', 'Admin'],
                    datasets: [{
                        label: 'Number of Evaluations',
                        data: [
                            <?php echo $evaluations_by_role['dean']; ?>,
                            <?php echo $evaluations_by_role['head_of_department']; ?>,
                            <?php echo $evaluations_by_role['instructor']; ?>,
                            <?php echo $evaluations_by_role['college']; ?>,
                            <?php echo $evaluations_by_role['hrm']; ?>,
                            <?php echo $evaluations_by_role['admin']; ?>
                        ],
                        backgroundColor: '#4e73df',
                        hoverBackgroundColor: '#2e59d9',
                        borderColor: '#4e73df',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                stepSize: 1
                            }
                        }]
                    },
                    title: {
                        display: true,
                        text: 'Evaluations by Role'
                    }
                }
            });
        }
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

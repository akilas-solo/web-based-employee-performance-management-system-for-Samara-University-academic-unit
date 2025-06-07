<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - View Department Head
 */

// Include configuration file - use direct path to avoid memory issues
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has dean role
if (!is_logged_in() || !has_role('dean')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$college_id = $_SESSION['college_id'];
$head_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if head_id is provided
if ($head_id <= 0) {
    redirect($base_url . 'dean/department_heads.php');
}

// Get department head information
$head = null;
$sql = "SELECT u.*, d.name as department_name, d.department_id, d.code as department_code, c.name as college_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN colleges c ON d.college_id = c.college_id
        WHERE u.user_id = ? AND u.role = 'head_of_department' AND d.college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $head_id, $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $head = $result->fetch_assoc();
} else {
    redirect($base_url . 'dean/department_heads.php');
}

// Get department information
$department = null;
$sql = "SELECT * FROM departments WHERE department_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $head['department_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $department = $result->fetch_assoc();
}

// Get active evaluation periods
$active_periods = [];
$sql = "SELECT * FROM evaluation_periods WHERE status = 1 ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $active_periods[] = $row;
    }
}

// Get recent evaluations
$recent_evaluations = [];
$sql = "SELECT e.*, p.title as period_title, p.academic_year, p.semester,
        u.full_name as evaluator_name, u.role as evaluator_role
        FROM evaluations e
        JOIN evaluation_periods p ON e.period_id = p.period_id
        JOIN users u ON e.evaluator_id = u.user_id
        WHERE e.evaluatee_id = ?
        ORDER BY e.created_at DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $head_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_evaluations[] = $row;
    }
}

// Get performance history
$performance_history = [];
$sql = "SELECT p.period_id, p.title, p.academic_year, p.semester,
        AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count
        FROM evaluation_periods p
        LEFT JOIN evaluations e ON p.period_id = e.period_id
        WHERE e.evaluatee_id = ?
        GROUP BY p.period_id
        ORDER BY p.start_date DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $head_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $performance_history[] = $row;
    }
}

// Get department staff count
$staff_count = 0;
$sql = "SELECT COUNT(*) as count FROM users WHERE department_id = ? AND role != 'head_of_department'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $head['department_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $staff_count = $row['count'];
}

// Include header
include_once $GLOBALS['BASE_PATH'] . '/includes/header_management.php';

// Include sidebar
include_once $GLOBALS['BASE_PATH'] . '/includes/sidebar.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Department Head Profile</h1>
            <div>
                <a href="<?php echo $base_url; ?>dean/department_heads.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Department Heads
                </a>
                <a href="<?php echo $base_url; ?>dean/head_evaluations.php?id=<?php echo $head_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-chart-bar fa-sm text-white-50 mr-1"></i> View Evaluations
                </a>
                <?php if (count($active_periods) > 0): ?>
                    <div class="dropdown d-inline-block">
                        <button class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm dropdown-toggle" type="button" id="evaluateDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-clipboard-check fa-sm text-white-50 mr-1"></i> Evaluate
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="evaluateDropdown">
                            <?php foreach ($active_periods as $period): ?>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>dean/evaluation_form.php?evaluatee_id=<?php echo $head_id; ?>&period_id=<?php echo $period['period_id']; ?>">
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Head Info Card -->
        <div class="row">
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Head Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($head['profile_image'])): ?>
                                <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $head['profile_image']; ?>" alt="<?php echo $head['full_name']; ?>" class="img-profile rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; font-size: 3rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h4 class="text-center text-theme mb-3"><?php echo $head['full_name']; ?></h4>
                        <p class="text-center mb-4">
                            <span class="badge badge-primary"><?php echo ucwords(str_replace('_', ' ', $head['role'])); ?></span>
                            <span class="badge badge-<?php echo ($head['status'] == 1) ? 'success' : 'danger'; ?>">
                                <?php echo ($head['status'] == 1) ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Contact Information</h6>
                            <p>
                                <i class="fas fa-envelope mr-2 text-theme"></i> <?php echo $head['email']; ?><br>
                                <?php if (!empty($head['phone'])): ?>
                                    <i class="fas fa-phone mr-2 text-theme"></i> <?php echo $head['phone']; ?><br>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Department</h6>
                            <p>
                                <i class="fas fa-building mr-2 text-theme"></i> <?php echo $head['department_name']; ?> (<?php echo $head['department_code']; ?>)<br>
                                <i class="fas fa-university mr-2 text-theme"></i> <?php echo $head['college_name']; ?><br>
                                <i class="fas fa-users mr-2 text-theme"></i> <?php echo $staff_count; ?> Staff Members
                            </p>
                        </div>
                        <?php if (!empty($head['position'])): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Position</h6>
                                <p><?php echo $head['position']; ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($head['bio'])): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Bio</h6>
                                <p><?php echo $head['bio']; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 col-lg-7">
                <!-- Performance Summary Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Performance Summary</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Calculate overall average score
                        $overall_avg = 0;
                        $total_evaluations = 0;
                        foreach ($performance_history as $period) {
                            if ($period['avg_score']) {
                                $overall_avg += $period['avg_score'] * $period['evaluation_count'];
                                $total_evaluations += $period['evaluation_count'];
                            }
                        }
                        if ($total_evaluations > 0) {
                            $overall_avg = $overall_avg / $total_evaluations;
                        }
                        ?>
                        <div class="row">
                            <div class="col-md-6 text-center mb-4">
                                <div class="h1 mb-0 font-weight-bold text-theme"><?php echo number_format($overall_avg, 2); ?></div>
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Overall Average Score (out of 5.00)</div>
                                <div class="mt-3">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-theme" role="progressbar" style="width: <?php echo ($overall_avg / 5) * 100; ?>%" aria-valuenow="<?php echo $overall_avg; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-center mb-4">
                                <div class="h1 mb-0 font-weight-bold text-theme"><?php echo $total_evaluations; ?></div>
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Evaluations</div>
                                <div class="mt-3">
                                    <div class="row">
                                        <div class="col-6 text-center">
                                            <div class="h4 mb-0 font-weight-bold text-theme"><?php echo count($performance_history); ?></div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Periods</div>
                                        </div>
                                        <div class="col-6 text-center">
                                            <div class="h4 mb-0 font-weight-bold text-theme"><?php echo count($recent_evaluations); ?></div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Recent</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if (count($performance_history) > 0): ?>
                            <div class="chart-container" style="position: relative; height:300px;">
                                <canvas id="performanceHistoryChart"></canvas>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No performance history available.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Evaluations Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Recent Evaluations</h6>
                        <a href="<?php echo $base_url; ?>dean/head_evaluations.php?id=<?php echo $head_id; ?>" class="btn btn-sm btn-theme">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_evaluations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Evaluator</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_evaluations as $evaluation): ?>
                                            <tr>
                                                <td><?php echo $evaluation['period_title']; ?> (<?php echo $evaluation['academic_year']; ?>, Sem <?php echo $evaluation['semester']; ?>)</td>
                                                <td>
                                                    <?php echo $evaluation['evaluator_name']; ?>
                                                    <span class="badge badge-info"><?php echo ucwords(str_replace('_', ' ', $evaluation['evaluator_role'])); ?></span>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-theme" role="progressbar"
                                                            style="width: <?php echo ($evaluation['total_score'] / 5) * 100; ?>%"
                                                            aria-valuenow="<?php echo $evaluation['total_score']; ?>"
                                                            aria-valuemin="0" aria-valuemax="5">
                                                            <?php echo number_format($evaluation['total_score'], 2); ?>/5
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $evaluation['status'] == 'completed' ? 'success' :
                                                            ($evaluation['status'] == 'in_progress' ? 'warning' : 'secondary');
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $evaluation['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo $base_url; ?>dean/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No evaluations found for this department head.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/chart.js/Chart.min.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart.js - Performance History
        var performanceHistoryCtx = document.getElementById('performanceHistoryChart');

        <?php if (count($performance_history) > 0): ?>
            var periods = [<?php echo implode(', ', array_map(function($period) { return "'" . $period['title'] . "'"; }, $performance_history)); ?>];
            var scores = [<?php echo implode(', ', array_map(function($period) { return $period['avg_score'] ? $period['avg_score'] : 0; }, $performance_history)); ?>];

            new Chart(performanceHistoryCtx, {
                type: 'line',
                data: {
                    labels: periods,
                    datasets: [{
                        label: 'Average Performance Score',
                        data: scores,
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        fill: true
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                max: 5
                            }
                        }]
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once $GLOBALS['BASE_PATH'] . '/includes/footer_management.php';
?>

<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Department Details
 */

// Include configuration file - use direct path to avoid memory issues
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has hrm role
if (!is_logged_in() || !has_role('hrm')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$department_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if department_id is provided
if ($department_id <= 0) {
    redirect($base_url . 'hrm/departments.php');
}

// Get department information
$department_info = null;
$sql = "SELECT d.*, c.name as college_name FROM departments d JOIN colleges c ON d.college_id = c.college_id WHERE d.department_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $department_info = $result->fetch_assoc();
} else {
    redirect($base_url . 'hrm/departments.php');
}

// Get staff members in the department
$staff = [];
$sql = "SELECT u.*,
        (SELECT AVG(e.total_score) FROM evaluations e WHERE e.evaluatee_id = u.user_id) as avg_score,
        (SELECT COUNT(e.evaluation_id) FROM evaluations e WHERE e.evaluatee_id = u.user_id) as evaluation_count
        FROM users u
        WHERE u.department_id = ?
        ORDER BY u.full_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }
}

// Get department head
$department_head = null;
$sql = "SELECT u.* FROM users u WHERE u.department_id = ? AND u.role = 'head_of_department' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $department_head = $result->fetch_assoc();
}

// Get department performance by period
$performance_history = [];
$sql = "SELECT p.period_id, p.title, p.academic_year, p.semester,
        AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count
        FROM evaluation_periods p
        LEFT JOIN evaluations e ON p.period_id = e.period_id
        LEFT JOIN users u ON e.evaluatee_id = u.user_id
        WHERE u.department_id = ?
        GROUP BY p.period_id
        ORDER BY p.start_date DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $performance_history[] = $row;
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
                <a href="<?php echo $base_url; ?>hrm/departments.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Departments
                </a>
                <a href="<?php echo $base_url; ?>hrm/department_report.php?id=<?php echo $department_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
                    <i class="fas fa-chart-bar fa-sm text-white-50 mr-1"></i> View Report
                </a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Department Info Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Department Information</h6>
                    </div>
                    <div class="card-body">
                        <h4 class="text-theme"><?php echo $department_info['name']; ?></h4>
                        <p>
                            <strong>Code:</strong> <?php echo $department_info['code']; ?><br>
                            <strong>College:</strong> <?php echo $department_info['college_name']; ?><br>
                            <strong>Total Staff:</strong> <?php echo count($staff); ?><br>
                            <?php if (!empty($department_info['description'])): ?>
                                <strong>Description:</strong> <?php echo $department_info['description']; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Department Head Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Department Head</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($department_head): ?>
                            <div class="text-center mb-3">
                                <?php if (!empty($department_head['profile_image'])): ?>
                                    <img src="<?php echo $base_url . 'uploads/profiles/' . $department_head['profile_image']; ?>" class="img-profile rounded-circle" style="width: 100px; height: 100px;">
                                <?php else: ?>
                                    <img src="<?php echo $base_url; ?>assets/img/undraw_profile.svg" class="img-profile rounded-circle" style="width: 100px; height: 100px;">
                                <?php endif; ?>
                            </div>
                            <h5 class="text-center text-theme"><?php echo $department_head['full_name']; ?></h5>
                            <p class="text-center">
                                <?php echo $department_head['position'] ? $department_head['position'] : 'Head of Department'; ?>
                            </p>
                            <p>
                                <strong>Email:</strong> <?php echo $department_head['email']; ?><br>
                                <?php if (!empty($department_head['phone'])): ?>
                                    <strong>Phone:</strong> <?php echo $department_head['phone']; ?><br>
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <p class="text-center">No department head assigned.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Performance Summary Card -->
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Performance Summary</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Calculate overall average score
                        $overall_avg = 0;
                        $total_evaluations = 0;
                        foreach ($staff as $member) {
                            if ($member['avg_score']) {
                                $overall_avg += $member['avg_score'] * $member['evaluation_count'];
                                $total_evaluations += $member['evaluation_count'];
                            }
                        }
                        if ($total_evaluations > 0) {
                            $overall_avg = $overall_avg / $total_evaluations;
                        }
                        ?>
                        <div class="text-center mb-3">
                            <h1 class="text-theme"><?php echo number_format($overall_avg, 2); ?></h1>
                            <p>Overall Average Score (out of 5.00)</p>
                        </div>
                        <div class="text-center">
                            <p>
                                <strong>Total Evaluations:</strong> <?php echo $total_evaluations; ?><br>
                                <strong>Staff Evaluated:</strong> <?php echo count(array_filter($staff, function($member) { return $member['evaluation_count'] > 0; })); ?> of <?php echo count($staff); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance History Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Performance History</h6>
            </div>
            <div class="card-body">
                <?php if (count($performance_history) > 0): ?>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="performanceHistoryChart"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-center">No performance history available.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Staff List Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Staff Members</h6>
            </div>
            <div class="card-body">
                <?php if (count($staff) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="staffTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Email</th>
                                    <th>Evaluations</th>
                                    <th>Avg. Score</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff as $member): ?>
                                    <tr>
                                        <td><?php echo $member['full_name']; ?></td>
                                        <td><?php echo $member['position'] ? $member['position'] : 'Staff'; ?></td>
                                        <td><?php echo $member['email']; ?></td>
                                        <td><?php echo $member['evaluation_count']; ?></td>
                                        <td>
                                            <?php if ($member['avg_score']): ?>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-theme" role="progressbar"
                                                        style="width: <?php echo ($member['avg_score'] / 5) * 100; ?>%"
                                                        aria-valuenow="<?php echo $member['avg_score']; ?>"
                                                        aria-valuemin="0" aria-valuemax="5">
                                                        <?php echo number_format($member['avg_score'], 2); ?>/5
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No data</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>hrm/staff_report.php?user_id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-info" title="View Report">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No staff members found in this department.</p>
                <?php endif; ?>
            </div>
        </div>
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
        // Initialize DataTable
        $('#staffTable').DataTable();

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
include_once BASE_PATH . '/includes/footer_management.php';
?>

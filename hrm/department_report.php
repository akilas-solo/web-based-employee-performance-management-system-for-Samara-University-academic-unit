<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Department Report
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
$user_id = $_SESSION['user_id'];
$department_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : 'summary';

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

// Get all evaluation periods
$periods = [];
$sql = "SELECT * FROM evaluation_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row;
    }
}

// Get period details if selected
$period_info = null;
if ($period_id > 0) {
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $period_info = $result->fetch_assoc();
    }
}

// Get staff in the department
$staff = [];
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM evaluations e WHERE e.evaluatee_id = u.user_id) as evaluation_count,
        (SELECT AVG(e.total_score) FROM evaluations e WHERE e.evaluatee_id = u.user_id) as avg_score
        FROM users u 
        WHERE u.department_id = ? AND u.role = 'staff'
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

// Get department performance by period
$performance_history = [];
$sql = "SELECT p.period_id, p.title, p.academic_year, p.semester, 
        AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count 
        FROM evaluation_periods p 
        LEFT JOIN evaluations e ON p.period_id = e.period_id 
        LEFT JOIN users u ON e.evaluatee_id = u.user_id 
        WHERE u.department_id = ? 
        GROUP BY p.period_id 
        ORDER BY p.start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $performance_history[] = $row;
    }
}

// Get staff performance for the selected period
$staff_performance = [];
if ($period_id > 0) {
    $sql = "SELECT u.user_id, u.full_name, u.email, u.position, 
            AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count 
            FROM users u 
            LEFT JOIN evaluations e ON u.user_id = e.evaluatee_id AND e.period_id = ? 
            WHERE u.department_id = ? AND u.role = 'staff' 
            GROUP BY u.user_id 
            ORDER BY avg_score DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $period_id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $staff_performance[] = $row;
        }
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
            <h1 class="h3 mb-0 text-gray-800">Department Performance Report</h1>
            <?php if ($period_id > 0 && !empty($staff_performance)): ?>
                <a href="<?php echo $base_url; ?>hrm/export_department_report.php?id=<?php echo $department_id; ?>&period_id=<?php echo $period_id; ?>&report_type=<?php echo $report_type; ?>" class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm">
                    <i class="fas fa-download fa-sm text-white-50 mr-1"></i> Export Report
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Department Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">Department Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="text-theme"><?php echo $department_info['name']; ?> Department</h4>
                        <p>
                            <strong>Code:</strong> <?php echo $department_info['code']; ?><br>
                            <strong>College:</strong> <?php echo $department_info['college_name']; ?><br>
                            <strong>Total Staff:</strong> <?php echo count($staff); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                            <input type="hidden" name="id" value="<?php echo $department_id; ?>">
                            <div class="form-group mb-2 mr-2">
                                <label for="period_id" class="mr-2">Evaluation Period:</label>
                                <select class="form-control" id="period_id" name="period_id">
                                    <option value="0">All Periods</option>
                                    <?php foreach ($periods as $period): ?>
                                        <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                            <?php echo $period['title'] . ' (' . $period['academic_year'] . ', Semester ' . $period['semester'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group mb-2 mr-2">
                                <label for="report_type" class="mr-2">Report Type:</label>
                                <select class="form-control" id="report_type" name="report_type">
                                    <option value="summary" <?php echo ($report_type == 'summary') ? 'selected' : ''; ?>>Summary</option>
                                    <option value="detailed" <?php echo ($report_type == 'detailed') ? 'selected' : ''; ?>>Detailed</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-theme mb-2">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Overview -->
        <div class="row">
            <!-- Performance History -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Performance History</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($performance_history) > 0): ?>
                            <div class="chart-bar">
                                <canvas id="performanceHistoryChart"></canvas>
                            </div>
                            <hr>
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Evaluations</th>
                                            <th>Avg. Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($performance_history as $history): ?>
                                            <tr>
                                                <td>
                                                    <?php echo $history['title']; ?>
                                                    (<?php echo $history['academic_year']; ?>, Semester <?php echo $history['semester']; ?>)
                                                </td>
                                                <td><?php echo $history['evaluation_count']; ?></td>
                                                <td>
                                                    <?php if ($history['avg_score']): ?>
                                                        <?php echo number_format($history['avg_score'], 2); ?>/5
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No performance history available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Department Summary -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Department Summary</h6>
                    </div>
                    <div class="card-body">
                        <?php 
                        $current_avg = 0;
                        $current_count = 0;
                        $previous_avg = 0;
                        $previous_count = 0;
                        
                        if (count($performance_history) > 0) {
                            $current_avg = $performance_history[0]['avg_score'] ?? 0;
                            $current_count = $performance_history[0]['evaluation_count'] ?? 0;
                            
                            if (count($performance_history) > 1) {
                                $previous_avg = $performance_history[1]['avg_score'] ?? 0;
                                $previous_count = $performance_history[1]['evaluation_count'] ?? 0;
                            }
                        }
                        
                        $change = 0;
                        if ($previous_avg > 0 && $current_avg > 0) {
                            $change = (($current_avg - $previous_avg) / $previous_avg) * 100;
                        }
                        ?>
                        
                        <div class="text-center mb-4">
                            <h1 class="display-4 text-theme">
                                <?php echo $current_avg ? number_format($current_avg, 2) : 'N/A'; ?>
                            </h1>
                            <p class="lead">Current Average Score</p>
                            
                            <?php if ($change != 0): ?>
                                <p class="text-<?php echo $change > 0 ? 'success' : 'danger'; ?>">
                                    <i class="fas fa-<?php echo $change > 0 ? 'arrow-up' : 'arrow-down'; ?> mr-1"></i>
                                    <?php echo abs(number_format($change, 1)); ?>% from previous period
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo count($staff); ?></h5>
                                        <p class="card-text">Total Staff</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $current_count; ?></h5>
                                        <p class="card-text">Total Evaluations</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Performance -->
        <?php if ($period_id > 0): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">Staff Performance</h6>
                </div>
                <div class="card-body">
                    <?php if (count($staff_performance) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="staffPerformanceTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Evaluations</th>
                                        <th>Avg. Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_performance as $member): ?>
                                        <tr>
                                            <td><?php echo $member['full_name']; ?></td>
                                            <td><?php echo $member['position'] ?? 'N/A'; ?></td>
                                            <td><?php echo $member['evaluation_count']; ?></td>
                                            <td>
                                                <?php if ($member['avg_score']): ?>
                                                    <?php echo number_format($member['avg_score'], 2); ?>/5.00
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo $base_url; ?>hrm/staff_report.php?user_id=<?php echo $member['user_id']; ?>&period_id=<?php echo $period_id; ?>" class="btn btn-sm btn-info" title="View Report">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No staff performance data available for the selected period.</p>
                    <?php endif; ?>
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
        // Initialize DataTable
        $('#staffPerformanceTable').DataTable();
        
        <?php if (count($performance_history) > 0): ?>
        // Performance History Chart
        var historyCtx = document.getElementById('performanceHistoryChart');
        if (historyCtx) {
            var historyChart = new Chart(historyCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($history) { return "'" . $history['title'] . "'"; }, $performance_history)); ?>],
                    datasets: [{
                        label: 'Average Score',
                        data: [<?php echo implode(', ', array_map(function($history) { return $history['avg_score'] ? $history['avg_score'] : 0; }, $performance_history)); ?>],
                        backgroundColor: 'rgba(78, 115, 223, 0.8)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1
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
        }
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

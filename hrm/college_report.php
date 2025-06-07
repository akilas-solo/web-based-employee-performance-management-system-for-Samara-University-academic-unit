<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - College Report
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
$college_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : 'summary';

// Check if college_id is provided
if ($college_id <= 0) {
    redirect($base_url . 'hrm/colleges.php');
}

// Get college information
$college_info = null;
$sql = "SELECT * FROM colleges WHERE college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $college_info = $result->fetch_assoc();
} else {
    redirect($base_url . 'hrm/colleges.php');
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

// Get departments in the college
$departments = [];
$sql = "SELECT d.*, 
        (SELECT COUNT(*) FROM users u WHERE u.department_id = d.department_id) as staff_count,
        (SELECT AVG(e.total_score) FROM evaluations e 
         JOIN users u ON e.evaluatee_id = u.user_id 
         WHERE u.department_id = d.department_id) as avg_score
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

// Get college performance by period
$performance_history = [];
$sql = "SELECT p.period_id, p.title, p.academic_year, p.semester, 
        AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count 
        FROM evaluation_periods p 
        LEFT JOIN evaluations e ON p.period_id = e.period_id 
        LEFT JOIN users u ON e.evaluatee_id = u.user_id 
        WHERE u.college_id = ? 
        GROUP BY p.period_id 
        ORDER BY p.start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $performance_history[] = $row;
    }
}

// Get department performance for the selected period
$department_performance = [];
if ($period_id > 0) {
    $sql = "SELECT d.department_id, d.name, d.code, 
            COUNT(DISTINCT u.user_id) as staff_count,
            COUNT(DISTINCT e.evaluation_id) as evaluation_count,
            AVG(e.total_score) as avg_score 
            FROM departments d 
            LEFT JOIN users u ON d.department_id = u.department_id 
            LEFT JOIN evaluations e ON u.user_id = e.evaluatee_id AND e.period_id = ? 
            WHERE d.college_id = ? 
            GROUP BY d.department_id 
            ORDER BY avg_score DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $period_id, $college_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $department_performance[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">College Performance Report</h1>
            <?php if ($period_id > 0 && !empty($department_performance)): ?>
                <a href="<?php echo $base_url; ?>hrm/export_college_report.php?id=<?php echo $college_id; ?>&period_id=<?php echo $period_id; ?>&report_type=<?php echo $report_type; ?>" class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm">
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

        <!-- College Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">College Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="text-theme"><?php echo $college_info['name']; ?> College</h4>
                        <p>
                            <strong>Code:</strong> <?php echo $college_info['code']; ?><br>
                            <strong>Total Departments:</strong> <?php echo count($departments); ?><br>
                            <strong>Total Staff:</strong> <?php echo array_sum(array_column($departments, 'staff_count')); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                            <input type="hidden" name="id" value="<?php echo $college_id; ?>">
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

            <!-- College Summary -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">College Summary</h6>
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
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo count($departments); ?></h5>
                                        <p class="card-text">Departments</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo array_sum(array_column($departments, 'staff_count')); ?></h5>
                                        <p class="card-text">Staff</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $current_count; ?></h5>
                                        <p class="card-text">Evaluations</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Performance -->
        <?php if ($period_id > 0): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">Department Performance</h6>
                </div>
                <div class="card-body">
                    <?php if (count($department_performance) > 0): ?>
                        <div class="chart-bar mb-4">
                            <canvas id="departmentPerformanceChart"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="departmentPerformanceTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Code</th>
                                        <th>Staff Count</th>
                                        <th>Evaluations</th>
                                        <th>Avg. Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($department_performance as $dept): ?>
                                        <tr>
                                            <td><?php echo $dept['name']; ?></td>
                                            <td><?php echo $dept['code']; ?></td>
                                            <td><?php echo $dept['staff_count']; ?></td>
                                            <td><?php echo $dept['evaluation_count']; ?></td>
                                            <td>
                                                <?php if ($dept['avg_score']): ?>
                                                    <?php echo number_format($dept['avg_score'], 2); ?>/5.00
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo $base_url; ?>hrm/department_report.php?id=<?php echo $dept['department_id']; ?>&period_id=<?php echo $period_id; ?>" class="btn btn-sm btn-info" title="View Report">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No department performance data available for the selected period.</p>
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
        $('#departmentPerformanceTable').DataTable();
        
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
        
        <?php if ($period_id > 0 && count($department_performance) > 0): ?>
        // Department Performance Chart
        var deptCtx = document.getElementById('departmentPerformanceChart');
        if (deptCtx) {
            var deptChart = new Chart(deptCtx, {
                type: 'horizontalBar',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($dept) { return "'" . $dept['name'] . "'"; }, $department_performance)); ?>],
                    datasets: [{
                        label: 'Average Score',
                        data: [<?php echo implode(', ', array_map(function($dept) { return $dept['avg_score'] ? $dept['avg_score'] : 0; }, $department_performance)); ?>],
                        backgroundColor: 'rgba(78, 115, 223, 0.8)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
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

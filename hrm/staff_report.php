<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Staff Report
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
$staff_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : 'summary';

// Check if staff_id is provided
if ($staff_id <= 0) {
    redirect($base_url . 'hrm/staff.php');
}

// Get staff information
$staff_info = null;
$sql = "SELECT u.*, d.name as department_name, c.name as college_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        LEFT JOIN colleges c ON u.college_id = c.college_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $staff_info = $result->fetch_assoc();
} else {
    redirect($base_url . 'hrm/staff.php');
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

// Get evaluations for the staff member
$evaluations = [];
$sql = "SELECT e.*, u.full_name as evaluator_name, u.role as evaluator_role, 
        p.title as period_title, p.academic_year, p.semester 
        FROM evaluations e 
        JOIN users u ON e.evaluator_id = u.user_id 
        JOIN evaluation_periods p ON e.period_id = p.period_id 
        WHERE e.evaluatee_id = ?";

if ($period_id > 0) {
    $sql .= " AND e.period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $staff_id, $period_id);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staff_id);
}

$sql .= " ORDER BY e.created_at DESC";
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
    }
}

// Get performance by category
$category_performance = [];
if ($period_id > 0) {
    $sql = "SELECT cat.category_id, cat.name, 
            AVG(er.rating * ec.weight) / SUM(ec.weight) as avg_score 
            FROM evaluation_categories cat 
            JOIN evaluation_criteria ec ON cat.category_id = ec.category_id 
            JOIN evaluation_responses er ON ec.criteria_id = er.criteria_id 
            JOIN evaluations e ON er.evaluation_id = e.evaluation_id 
            WHERE e.evaluatee_id = ? AND e.period_id = ? 
            GROUP BY cat.category_id 
            ORDER BY avg_score DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $staff_id, $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $category_performance[] = $row;
        }
    }
}

// Get performance history (scores by period)
$performance_history = [];
$sql = "SELECT p.period_id, p.title, p.academic_year, p.semester, 
        AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count 
        FROM evaluation_periods p 
        LEFT JOIN evaluations e ON p.period_id = e.period_id AND e.evaluatee_id = ? 
        GROUP BY p.period_id 
        ORDER BY p.start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
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
            <h1 class="h3 mb-0 text-gray-800">Staff Performance Report</h1>
            <?php if ($period_id > 0 && !empty($evaluations)): ?>
                <a href="<?php echo $base_url; ?>hrm/export_staff_report.php?user_id=<?php echo $staff_id; ?>&period_id=<?php echo $period_id; ?>&report_type=<?php echo $report_type; ?>" class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm">
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

        <!-- Staff Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">Staff Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="text-theme"><?php echo $staff_info['full_name']; ?></h4>
                        <p>
                            <strong>Email:</strong> <?php echo $staff_info['email']; ?><br>
                            <strong>Position:</strong> <?php echo $staff_info['position'] ?? 'N/A'; ?><br>
                            <strong>Department:</strong> <?php echo $staff_info['department_name'] ?? 'N/A'; ?><br>
                            <strong>College:</strong> <?php echo $staff_info['college_name'] ?? 'N/A'; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                            <input type="hidden" name="user_id" value="<?php echo $staff_id; ?>">
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
            <!-- Performance by Category -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Performance by Category</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($period_id > 0 && count($category_performance) > 0): ?>
                            <div class="chart-pie">
                                <canvas id="categoryPerformanceChart"></canvas>
                            </div>
                            <hr>
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($category_performance as $category): ?>
                                            <tr>
                                                <td><?php echo $category['name']; ?></td>
                                                <td><?php echo number_format($category['avg_score'], 2); ?>/5</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">Please select an evaluation period to view category performance.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

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
        <?php if ($period_id > 0 && count($category_performance) > 0): ?>
        // Category Performance Chart
        var categoryCtx = document.getElementById('categoryPerformanceChart');
        if (categoryCtx) {
            var categoryChart = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($category) { return "'" . $category['name'] . "'"; }, $category_performance)); ?>],
                    datasets: [{
                        data: [<?php echo implode(', ', array_map(function($category) { return $category['avg_score']; }, $category_performance)); ?>],
                        backgroundColor: [
                            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#5a5c69', '#858796'
                        ],
                        hoverBackgroundColor: [
                            '#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617', '#3a3b45', '#60616f'
                        ],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex];
                                var currentValue = dataset.data[tooltipItem.index];
                                return data.labels[tooltipItem.index] + ': ' + currentValue.toFixed(2) + '/5';
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>

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

<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Reports
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
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$college_id = isset($_GET['college_id']) ? (int)$_GET['college_id'] : 0;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : 'summary';

// Get all evaluation periods
$periods = [];
$sql = "SELECT * FROM evaluation_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row;
    }
}

// Get all colleges
$colleges = [];
$sql = "SELECT * FROM colleges ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

// Get departments based on college selection
$departments = [];
$sql = "SELECT * FROM departments";
if ($college_id > 0) {
    $sql .= " WHERE college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Initialize report data arrays
$college_performance = [];
$department_performance = [];
$staff_performance = [];
$category_performance = [];
$period_info = null;

// Get report data if period is selected
if ($period_id > 0) {
    // Get period info
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $period_info = $result->fetch_assoc();
    }

    // Get college performance
    $sql = "SELECT c.college_id, c.name, c.code,
            COUNT(DISTINCT u.user_id) as staff_count,
            COUNT(DISTINCT e.evaluation_id) as evaluation_count,
            AVG(e.total_score) as avg_score
            FROM colleges c
            LEFT JOIN users u ON c.college_id = u.college_id
            LEFT JOIN evaluations e ON u.user_id = e.evaluatee_id AND e.period_id = ?
            GROUP BY c.college_id
            ORDER BY avg_score DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $college_performance[] = $row;
        }
    }

    // Get department performance
    $sql = "SELECT d.department_id, d.name, d.code, c.name as college_name,
            COUNT(DISTINCT u.user_id) as staff_count,
            COUNT(DISTINCT e.evaluation_id) as evaluation_count,
            AVG(e.total_score) as avg_score
            FROM departments d
            JOIN colleges c ON d.college_id = c.college_id
            LEFT JOIN users u ON d.department_id = u.department_id
            LEFT JOIN evaluations e ON u.user_id = e.evaluatee_id AND e.period_id = ?";

    if ($college_id > 0) {
        $sql .= " WHERE d.college_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $period_id, $college_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $period_id);
    }

    $sql .= " GROUP BY d.department_id ORDER BY avg_score DESC";
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $department_performance[] = $row;
        }
    }

    // Get staff performance
    $sql = "SELECT u.user_id, u.full_name, u.email, u.position,
            d.name as department_name, c.name as college_name,
            AVG(e.total_score) as avg_score,
            COUNT(e.evaluation_id) as evaluation_count
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON u.college_id = c.college_id
            LEFT JOIN evaluations e ON u.user_id = e.evaluatee_id AND e.period_id = ?
            WHERE u.role != 'hrm' AND u.role != 'admin'";

    $params = [$period_id];
    $types = "i";

    if ($college_id > 0) {
        $sql .= " AND u.college_id = ?";
        $params[] = $college_id;
        $types .= "i";
    }

    if ($department_id > 0) {
        $sql .= " AND u.department_id = ?";
        $params[] = $department_id;
        $types .= "i";
    }

    $sql .= " GROUP BY u.user_id ORDER BY avg_score DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $staff_performance[] = $row;
        }
    }

    // Get category performance
    $sql = "SELECT cat.category_id, cat.name,
            AVG((er.rating / ec.max_rating) * ec.weight) / SUM(ec.weight) * 5 as avg_score
            FROM evaluation_categories cat
            JOIN evaluation_criteria ec ON cat.category_id = ec.category_id
            JOIN evaluation_responses er ON ec.criteria_id = er.criteria_id
            JOIN evaluations e ON er.evaluation_id = e.evaluation_id
            JOIN users u ON e.evaluatee_id = u.user_id
            WHERE e.period_id = ?";

    $params = [$period_id];
    $types = "i";

    if ($college_id > 0) {
        $sql .= " AND u.college_id = ?";
        $params[] = $college_id;
        $types .= "i";
    }

    if ($department_id > 0) {
        $sql .= " AND u.department_id = ?";
        $params[] = $department_id;
        $types .= "i";
    }

    $sql .= " GROUP BY cat.category_id ORDER BY avg_score DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $category_performance[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Performance Reports</h1>
            <?php if ($period_id > 0 && !empty($staff_performance)): ?>
                <a href="<?php echo $base_url; ?>hrm/export_report.php?period_id=<?php echo $period_id; ?>&college_id=<?php echo $college_id; ?>&department_id=<?php echo $department_id; ?>&report_type=<?php echo $report_type; ?>" class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm">
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

        <!-- Filters Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Generate Report</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                    <div class="form-group mb-2 mr-2">
                        <label for="period_id" class="mr-2">Evaluation Period:</label>
                        <select class="form-control" id="period_id" name="period_id" required>
                            <option value="">Select Period</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $period['title'] . ' (' . $period['academic_year'] . ', Semester ' . $period['semester'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-2 mr-2">
                        <label for="college_id" class="mr-2">College:</label>
                        <select class="form-control" id="college_id" name="college_id">
                            <option value="0">All Colleges</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo $college['college_id']; ?>" <?php echo ($college_id == $college['college_id']) ? 'selected' : ''; ?>>
                                    <?php echo $college['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-2 mr-2">
                        <label for="department_id" class="mr-2">Department:</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>" <?php echo ($department_id == $department['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo $department['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-2 mr-2">
                        <label for="report_type" class="mr-2">Report Type:</label>
                        <select class="form-control" id="report_type" name="report_type">
                            <option value="summary" <?php echo ($report_type == 'summary') ? 'selected' : ''; ?>>Summary</option>
                            <option value="detailed" <?php echo ($report_type == 'detailed') ? 'selected' : ''; ?>>Detailed</option>
                            <option value="comparative" <?php echo ($report_type == 'comparative') ? 'selected' : ''; ?>>Comparative</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-theme mb-2">
                        <i class="fas fa-chart-bar mr-1"></i> Generate Report
                    </button>
                </form>
            </div>
        </div>

        <?php if ($period_id > 0 && $period_info): ?>
            <!-- Report Header -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-theme text-white">
                    <h6 class="m-0 font-weight-bold">Report Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-theme"><?php echo $period_info['title']; ?></h5>
                            <p>
                                <strong>Academic Year:</strong> <?php echo $period_info['academic_year']; ?><br>
                                <strong>Semester:</strong> <?php echo $period_info['semester']; ?><br>
                                <strong>Period:</strong> <?php echo date('M d, Y', strtotime($period_info['start_date'])); ?> to <?php echo date('M d, Y', strtotime($period_info['end_date'])); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p>
                                <strong>Report Type:</strong> <?php echo ucfirst($report_type); ?><br>
                                <strong>Generated On:</strong> <?php echo date('M d, Y H:i:s'); ?><br>
                                <strong>Generated By:</strong> <?php echo $_SESSION['full_name']; ?> (HRM)
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Overview -->
            <div class="row">
                <!-- College Performance -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-theme">College Performance</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($college_performance) > 0): ?>
                                <div class="chart-bar">
                                    <canvas id="collegePerformanceChart"></canvas>
                                </div>
                                <hr>
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>College</th>
                                                <th>Staff</th>
                                                <th>Evaluations</th>
                                                <th>Avg. Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($college_performance as $college): ?>
                                                <tr>
                                                    <td><?php echo $college['name']; ?></td>
                                                    <td><?php echo $college['staff_count']; ?></td>
                                                    <td><?php echo $college['evaluation_count']; ?></td>
                                                    <td>
                                                        <?php if ($college['avg_score']): ?>
                                                            <?php echo number_format($college['avg_score'], 2); ?>/5
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
                                <p class="text-center">No college performance data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Category Performance -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-theme">Performance by Category</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($category_performance) > 0): ?>
                                <div class="chart-pie">
                                    <canvas id="categoryPerformanceChart"></canvas>
                                </div>
                                <hr>
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Avg. Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($category_performance as $category): ?>
                                                <tr>
                                                    <td><?php echo $category['name']; ?></td>
                                                    <td>
                                                        <?php if ($category['avg_score']): ?>
                                                            <?php echo number_format($category['avg_score'], 2); ?>/5
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
                                <p class="text-center">No category performance data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Performance -->
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
                                        <th>Department</th>
                                        <th>College</th>
                                        <th>Evaluations</th>
                                        <th>Avg. Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_performance as $staff): ?>
                                        <tr>
                                            <td><?php echo $staff['full_name']; ?></td>
                                            <td><?php echo $staff['position']; ?></td>
                                            <td><?php echo $staff['department_name']; ?></td>
                                            <td><?php echo $staff['college_name']; ?></td>
                                            <td><?php echo $staff['evaluation_count']; ?></td>
                                            <td>
                                                <?php if ($staff['avg_score']): ?>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-theme" role="progressbar"
                                                            style="width: <?php echo ($staff['avg_score'] / 5) * 100; ?>%"
                                                            aria-valuenow="<?php echo $staff['avg_score']; ?>"
                                                            aria-valuemin="0" aria-valuemax="5">
                                                            <?php echo number_format($staff['avg_score'], 2); ?>/5
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No data</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo $base_url; ?>hrm/staff_report.php?user_id=<?php echo $staff['user_id']; ?>&period_id=<?php echo $period_id; ?>" class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No staff performance data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($period_id > 0): ?>
            <div class="alert alert-warning">
                <p>No data available for the selected period. Please select a different period or check if evaluations have been conducted.</p>
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

        // Handle college change to update departments
        $('#college_id').change(function() {
            var collegeId = $(this).val();
            if (collegeId > 0) {
                // AJAX call to get departments for selected college
                $.ajax({
                    url: '<?php echo $base_url; ?>includes/ajax_handlers.php',
                    type: 'POST',
                    data: {
                        action: 'get_departments',
                        college_id: collegeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        var departmentSelect = $('#department_id');
                        departmentSelect.empty();
                        departmentSelect.append('<option value="0">All Departments</option>');

                        if (response.success && response.departments.length > 0) {
                            $.each(response.departments, function(index, department) {
                                departmentSelect.append('<option value="' + department.department_id + '">' + department.name + '</option>');
                            });
                        }
                    }
                });
            } else {
                // Reset departments dropdown
                $('#department_id').html('<option value="0">All Departments</option>');
            }
        });

        <?php if ($period_id > 0 && !empty($college_performance)): ?>
        // College Performance Chart
        var collegeCtx = document.getElementById('collegePerformanceChart');
        if (collegeCtx) {
            var collegeChart = new Chart(collegeCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($college) { return "'" . $college['name'] . "'"; }, $college_performance)); ?>],
                    datasets: [{
                        label: 'Average Performance Score',
                        data: [<?php echo implode(', ', array_map(function($college) { return $college['avg_score'] ? $college['avg_score'] : 0; }, $college_performance)); ?>],
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

        <?php if ($period_id > 0 && !empty($category_performance)): ?>
        // Category Performance Chart
        var categoryCtx = document.getElementById('categoryPerformanceChart');
        if (categoryCtx) {
            var categoryChart = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($category) { return "'" . $category['name'] . "'"; }, $category_performance)); ?>],
                    datasets: [{
                        data: [<?php echo implode(', ', array_map(function($category) { return $category['avg_score'] ? $category['avg_score'] : 0; }, $category_performance)); ?>],
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
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

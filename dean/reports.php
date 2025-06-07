<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - Reports
 */

// Include configuration file
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
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : 'summary';
$college_name = '';

// Get college name
if ($college_id) {
    $sql = "SELECT name FROM colleges WHERE college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $college_name = $row['name'];
    }
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

// Get all departments in the college
$departments = [];
$sql = "SELECT * FROM departments WHERE college_id = ? ORDER BY name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Get report data
$department_performance = [];
$category_performance = [];
$period_details = null;
$department_details = null;

if ($period_id > 0) {
    // Get period details
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $period_details = $result->fetch_assoc();
    }

    // Get department details if selected
    if ($department_id > 0) {
        $sql = "SELECT * FROM departments WHERE department_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $department_details = $result->fetch_assoc();
        }
    }

    // Get department performance
    $sql = "SELECT d.department_id, d.name,
            AVG(e.total_score) as avg_score, COUNT(DISTINCT e.evaluation_id) as evaluation_count,
            COUNT(DISTINCT u.user_id) as staff_count
            FROM departments d
            LEFT JOIN users u ON u.department_id = d.department_id
            LEFT JOIN evaluations e ON u.user_id = e.evaluatee_id AND e.period_id = ?
            WHERE d.college_id = ?";

    if ($department_id > 0) {
        $sql .= " AND d.department_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $period_id, $college_id, $department_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $period_id, $college_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $department_performance[] = $row;
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
            JOIN departments d ON u.department_id = d.department_id
            WHERE e.period_id = ? AND d.college_id = ?";

    if ($department_id > 0) {
        $sql .= " AND d.department_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $period_id, $college_id, $department_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $period_id, $college_id);
    }

    $sql .= " GROUP BY cat.category_id ORDER BY avg_score DESC";

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $category_performance[] = $row;
        }
    }
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
            <h1 class="h3 mb-0 text-gray-800">College Performance Reports</h1>
            <?php if ($period_id > 0 && !empty($department_performance)): ?>
                <a href="<?php echo $base_url; ?>dean/export_report.php?period_id=<?php echo $period_id; ?>&department_id=<?php echo $department_id; ?>&report_type=<?php echo $report_type; ?>" class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm">
                    <i class="fas fa-download fa-sm text-white-50 mr-1"></i> Export Report
                </a>
            <?php endif; ?>
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

        <!-- College Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">College Information</h6>
            </div>
            <div class="card-body">
                <h4 class="text-theme"><?php echo $college_name; ?> College</h4>
                <p class="mb-0">Total Departments: <?php echo count($departments); ?></p>
            </div>
        </div>

        <!-- Report Filters Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Report Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                    <div class="form-group mb-2 mr-2">
                        <label for="period_id" class="mr-2">Evaluation Period:</label>
                        <select class="form-control" id="period_id" name="period_id" required>
                            <option value="">Select Period</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
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
                            <option value="summary" <?php echo ($report_type === 'summary') ? 'selected' : ''; ?>>Summary</option>
                            <option value="detailed" <?php echo ($report_type === 'detailed') ? 'selected' : ''; ?>>Detailed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-theme mb-2">
                        <i class="fas fa-filter mr-1"></i> Generate Report
                    </button>
                </form>
            </div>
        </div>

        <?php if ($period_id > 0 && $period_details): ?>
            <!-- Report Header -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">
                        <?php echo $period_details['title']; ?> (<?php echo $period_details['academic_year']; ?>, Semester <?php echo $period_details['semester']; ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Period:</strong> <?php echo date('M d, Y', strtotime($period_details['start_date'])); ?> - <?php echo date('M d, Y', strtotime($period_details['end_date'])); ?></p>
                            <p><strong>Report Type:</strong> <?php echo ucfirst($report_type); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>College:</strong> <?php echo $college_name; ?></p>
                            <?php if ($department_details): ?>
                                <p><strong>Department:</strong> <?php echo $department_details['name']; ?></p>
                            <?php else: ?>
                                <p><strong>Department:</strong> All Departments</p>
                            <?php endif; ?>
                            <p><strong>Generated On:</strong> <?php echo date('M d, Y H:i:s'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- College Performance Overview -->
            <div class="row">
                <!-- Department Performance -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-theme">Department Performance</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($department_performance) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="departmentPerformanceTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th>Staff Count</th>
                                                <th>Evaluations</th>
                                                <th>Average Score</th>
                                                <th>Rating</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($department_performance as $dept): ?>
                                                <tr>
                                                    <td><?php echo $dept['name']; ?></td>
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
                                                        <?php if ($dept['avg_score']): ?>
                                                            <span class="badge badge-<?php
                                                                $score = $dept['avg_score'];
                                                                if ($score >= 4.5) {
                                                                    echo 'success';
                                                                } elseif ($score >= 3.5) {
                                                                    echo 'primary';
                                                                } elseif ($score >= 2.5) {
                                                                    echo 'info';
                                                                } elseif ($score >= 1.5) {
                                                                    echo 'warning';
                                                                } else {
                                                                    echo 'danger';
                                                                }
                                                            ?>">
                                                                <?php
                                                                $score = $dept['avg_score'];
                                                                if ($score >= 4.5) {
                                                                    echo 'Excellent';
                                                                } elseif ($score >= 3.5) {
                                                                    echo 'Very Good';
                                                                } elseif ($score >= 2.5) {
                                                                    echo 'Good';
                                                                } elseif ($score >= 1.5) {
                                                                    echo 'Fair';
                                                                } else {
                                                                    echo 'Poor';
                                                                }
                                                                ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Not Evaluated</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo $base_url; ?>dean/reports.php?period_id=<?php echo $period_id; ?>&department_id=<?php echo $dept['department_id']; ?>&report_type=<?php echo $report_type; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> View
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
                </div>

                <!-- Category Performance -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-theme">Performance by Category</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($category_performance) > 0): ?>
                                <?php foreach ($category_performance as $category): ?>
                                    <h4 class="small font-weight-bold">
                                        <?php echo $category['name']; ?>
                                        <span class="float-right"><?php echo number_format($category['avg_score'], 2); ?>/5.00</span>
                                    </h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar bg-<?php
                                            $score_percent = ($category['avg_score'] / 5) * 100;
                                            if ($score_percent >= 80) {
                                                echo 'success';
                                            } elseif ($score_percent >= 60) {
                                                echo 'info';
                                            } elseif ($score_percent >= 40) {
                                                echo 'warning';
                                            } else {
                                                echo 'danger';
                                            }
                                        ?>" role="progressbar" style="width: <?php echo $score_percent; ?>%"
                                            aria-valuenow="<?php echo $category['avg_score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center">No category performance data available for the selected period.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- College Average -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-theme">College Average</h6>
                        </div>
                        <div class="card-body text-center">
                            <?php
                            $college_avg = 0;
                            $total_depts_with_scores = 0;

                            foreach ($department_performance as $dept) {
                                if ($dept['avg_score']) {
                                    $college_avg += $dept['avg_score'];
                                    $total_depts_with_scores++;
                                }
                            }

                            if ($total_depts_with_scores > 0) {
                                $college_avg = $college_avg / $total_depts_with_scores;
                            }
                            ?>

                            <?php if ($college_avg > 0): ?>
                                <div class="h1 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($college_avg, 2); ?>/5.00
                                </div>
                                <div class="progress progress-lg mt-2 mb-2">
                                    <div class="progress-bar bg-<?php
                                        $score_percent = ($college_avg / 5) * 100;
                                        if ($score_percent >= 80) {
                                            echo 'success';
                                        } elseif ($score_percent >= 60) {
                                            echo 'info';
                                        } elseif ($score_percent >= 40) {
                                            echo 'warning';
                                        } else {
                                            echo 'danger';
                                        }
                                    ?>" role="progressbar" style="width: <?php echo $score_percent; ?>%"
                                        aria-valuenow="<?php echo $college_avg; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                </div>
                                <p class="text-<?php
                                    if ($score_percent >= 80) {
                                        echo 'success';
                                    } elseif ($score_percent >= 60) {
                                        echo 'info';
                                    } elseif ($score_percent >= 40) {
                                        echo 'warning';
                                    } else {
                                        echo 'danger';
                                    }
                                ?>">
                                    <?php
                                    if ($score_percent >= 90) {
                                        echo 'Excellent';
                                    } elseif ($score_percent >= 80) {
                                        echo 'Very Good';
                                    } elseif ($score_percent >= 70) {
                                        echo 'Good';
                                    } elseif ($score_percent >= 60) {
                                        echo 'Satisfactory';
                                    } elseif ($score_percent >= 50) {
                                        echo 'Fair';
                                    } else {
                                        echo 'Needs Improvement';
                                    }
                                    ?>
                                </p>
                            <?php else: ?>
                                <p class="text-center">No college average available for the selected period.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Chart -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">Department Performance Comparison</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="departmentPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <p>Please select an evaluation period to generate reports.</p>
            </div>
        <?php endif; ?>
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
        $('#departmentPerformanceTable').DataTable();

        // Chart.js - Department Performance
        var departmentPerformanceCtx = document.getElementById('departmentPerformanceChart');

        <?php if ($period_id > 0 && count($department_performance) > 0): ?>
            // Prepare data for chart
            var departmentNames = [];
            var departmentScores = [];
            var backgroundColors = [];

            <?php foreach ($department_performance as $dept): ?>
                <?php if ($dept['avg_score']): ?>
                    departmentNames.push('<?php echo $dept['name']; ?>');
                    departmentScores.push(<?php echo $dept['avg_score']; ?>);

                    // Set color based on score
                    <?php
                    $score = $dept['avg_score'];
                    if ($score >= 4.5) {
                        echo "backgroundColors.push('rgba(40, 167, 69, 0.8)');";
                    } elseif ($score >= 3.5) {
                        echo "backgroundColors.push('rgba(0, 123, 255, 0.8)');";
                    } elseif ($score >= 2.5) {
                        echo "backgroundColors.push('rgba(23, 162, 184, 0.8)');";
                    } elseif ($score >= 1.5) {
                        echo "backgroundColors.push('rgba(255, 193, 7, 0.8)');";
                    } else {
                        echo "backgroundColors.push('rgba(220, 53, 69, 0.8)');";
                    }
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>

            // Create Department Performance Chart
            if (departmentPerformanceCtx && departmentNames.length > 0) {
                new Chart(departmentPerformanceCtx, {
                    type: 'horizontalBar',
                    data: {
                        labels: departmentNames,
                        datasets: [{
                            label: 'Performance Score',
                            data: departmentScores,
                            backgroundColor: backgroundColors,
                            borderColor: backgroundColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            xAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    max: 5,
                                    stepSize: 1
                                },
                                scaleLabel: {
                                    display: true,
                                    labelString: 'Score (out of 5)'
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

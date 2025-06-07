<?php
/**
 * Samara University Academic Performance Evaluation System
 * Staff - Performance Reports
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has staff role
if (!is_logged_in() || !has_role('staff')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : 'summary';
$periods = [];
$period_details = null;
$evaluation_data = [];
$criteria_scores = [];
$evaluator_scores = [];
$period_scores = [];

// Get all evaluation periods
$sql = "SELECT * FROM evaluation_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row;

        // Set default period_id to the most recent period if not specified
        if ($period_id === 0 && $row['status'] === 'active') {
            $period_id = $row['period_id'];
        }
    }

    // If no active period, use the most recent one
    if ($period_id === 0 && count($periods) > 0) {
        $period_id = $periods[0]['period_id'];
    }
}

// Get period details if period_id is set
if ($period_id > 0) {
    foreach ($periods as $period) {
        if ($period['period_id'] === $period_id) {
            $period_details = $period;
            break;
        }
    }
}

// Get evaluation data for the selected period or all periods
if ($period_id > 0) {
    // Get evaluations for specific period
    $sql = "SELECT e.*, ep.title as period_name, u.full_name as evaluator_name, u.role as evaluator_role
            FROM evaluations e
            JOIN evaluation_periods ep ON e.period_id = ep.period_id
            JOIN users u ON e.evaluator_id = u.user_id
            WHERE e.evaluatee_id = ? AND e.period_id = ? AND e.status IN ('submitted', 'reviewed', 'approved')
            ORDER BY e.updated_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $period_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $evaluation_data[] = $row;
            }
        }
    } else {
        $error_message = "Error preparing query: " . $conn->error;
    }
} else {
    // Get evaluations for all periods
    $sql = "SELECT e.*, ep.title as period_name, u.full_name as evaluator_name, u.role as evaluator_role
            FROM evaluations e
            JOIN evaluation_periods ep ON e.period_id = ep.period_id
            JOIN users u ON e.evaluator_id = u.user_id
            WHERE e.evaluatee_id = ? AND e.status IN ('submitted', 'reviewed', 'approved')
            ORDER BY e.updated_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $evaluation_data[] = $row;
            }
        }
    } else {
        $error_message = "Error preparing query: " . $conn->error;
    }
}

// Get criteria scores
if (count($evaluation_data) > 0) {
    $evaluation_ids = array_column($evaluation_data, 'evaluation_id');
    $evaluation_ids_str = implode(',', $evaluation_ids);

    $sql = "SELECT er.*, ec.name as criteria_name, ec.description as criteria_description,
            ec.weight as criteria_weight, ec.max_rating as criteria_max_score, e.period_id
            FROM evaluation_responses er
            JOIN evaluation_criteria ec ON er.criteria_id = ec.criteria_id
            JOIN evaluations e ON er.evaluation_id = e.evaluation_id
            WHERE er.evaluation_id IN ($evaluation_ids_str)
            ORDER BY ec.criteria_id ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $criteria_scores[] = $row;
        }
    }

    // Calculate average scores by criteria
    $criteria_avg = [];
    foreach ($criteria_scores as $score) {
        $criteria_name = $score['criteria_name'];
        if (!isset($criteria_avg[$criteria_name])) {
            $criteria_avg[$criteria_name] = [
                'total' => 0,
                'count' => 0,
                'avg' => 0,
                'description' => $score['criteria_description'],
                'max_score' => $score['criteria_max_score']
            ];
        }
        $criteria_avg[$criteria_name]['total'] += $score['rating'];
        $criteria_avg[$criteria_name]['count']++;
    }

    foreach ($criteria_avg as $name => $data) {
        $criteria_avg[$name]['avg'] = $data['total'] / $data['count'];
    }

    // Calculate average scores by evaluator role
    $evaluator_avg = [];
    foreach ($evaluation_data as $eval) {
        $role = $eval['evaluator_role'];
        if (!isset($evaluator_avg[$role])) {
            $evaluator_avg[$role] = [
                'total' => 0,
                'count' => 0,
                'avg' => 0
            ];
        }
        $evaluator_avg[$role]['total'] += $eval['total_score'];
        $evaluator_avg[$role]['count']++;
    }

    foreach ($evaluator_avg as $role => $data) {
        $evaluator_avg[$role]['avg'] = $data['total'] / $data['count'];
    }

    // Calculate average scores by period
    $period_avg = [];
    foreach ($evaluation_data as $eval) {
        $period = $eval['period_name'];
        $period_id = $eval['period_id'];
        if (!isset($period_avg[$period])) {
            $period_avg[$period] = [
                'period_id' => $period_id,
                'total' => 0,
                'count' => 0,
                'avg' => 0
            ];
        }
        $period_avg[$period]['total'] += $eval['total_score'];
        $period_avg[$period]['count']++;
    }

    foreach ($period_avg as $period => $data) {
        $period_avg[$period]['avg'] = $data['total'] / $data['count'];
    }

    // Sort periods chronologically
    uksort($period_avg, function($a, $b) use ($period_avg) {
        return $period_avg[$a]['period_id'] - $period_avg[$b]['period_id'];
    });
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
            <div>
                <form action="<?php echo $base_url; ?>staff/reports.php" method="get" class="form-inline">
                    <div class="form-group mr-2">
                        <select class="form-control" name="period_id" onchange="this.form.submit()">
                            <option value="0" <?php echo ($period_id === 0) ? 'selected' : ''; ?>>All Periods</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id === $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $period['title']; ?>
                                    <?php if ($period['status'] === 'active'): ?>
                                        (Active)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mr-2">
                        <select class="form-control" name="report_type" onchange="this.form.submit()">
                            <option value="summary" <?php echo ($report_type === 'summary') ? 'selected' : ''; ?>>Summary</option>
                            <option value="criteria" <?php echo ($report_type === 'criteria') ? 'selected' : ''; ?>>By Criteria</option>
                            <option value="evaluator" <?php echo ($report_type === 'evaluator') ? 'selected' : ''; ?>>By Evaluator</option>
                            <option value="period" <?php echo ($report_type === 'period') ? 'selected' : ''; ?>>By Period</option>
                        </select>
                    </div>
                </form>
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

        <?php if (count($evaluation_data) > 0): ?>
            <!-- Performance Summary Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php if ($period_details): ?>
                            Performance Report: <?php echo $period_details['title']; ?>
                        <?php else: ?>
                            Overall Performance Report
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($report_type === 'summary'): ?>
                        <!-- Summary Report -->
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="chart-area mb-4">
                                    <canvas id="performanceChart"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card border-left-primary shadow h-100 py-2 mb-4">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Overall Average Score</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php
                                                    $overall_avg = array_sum(array_column($evaluation_data, 'total_score')) / count($evaluation_data);
                                                    echo number_format($overall_avg, 2);
                                                    ?>/5.00
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-star fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card border-left-success shadow h-100 py-2 mb-4">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Evaluations</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($evaluation_data); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Performance Rating</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo get_performance_rating($overall_avg); ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-award fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($report_type === 'criteria'): ?>
                        <!-- Criteria Report -->
                        <div class="chart-area mb-4">
                            <canvas id="criteriaChart"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Criteria</th>
                                        <th>Description</th>
                                        <th>Average Score</th>
                                        <th>Max Score</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($criteria_avg as $name => $data): ?>
                                        <tr>
                                            <td><?php echo $name; ?></td>
                                            <td><?php echo $data['description']; ?></td>
                                            <td><?php echo number_format($data['avg'], 2); ?></td>
                                            <td><?php echo $data['max_score']; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($data['avg'] / $data['max_score']) * 100; ?>%;" aria-valuenow="<?php echo $data['avg']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $data['max_score']; ?>">
                                                        <?php echo number_format(($data['avg'] / $data['max_score']) * 100, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($report_type === 'evaluator'): ?>
                        <!-- Evaluator Report -->
                        <div class="chart-area mb-4">
                            <canvas id="evaluatorChart"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Evaluator Role</th>
                                        <th>Average Score</th>
                                        <th>Number of Evaluations</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($evaluator_avg as $role => $data): ?>
                                        <tr>
                                            <td><?php echo ucfirst($role); ?></td>
                                            <td><?php echo number_format($data['avg'], 2); ?>/5.00</td>
                                            <td><?php echo $data['count']; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($data['avg'] / 5) * 100; ?>%;" aria-valuenow="<?php echo $data['avg']; ?>" aria-valuemin="0" aria-valuemax="5">
                                                        <?php echo number_format(($data['avg'] / 5) * 100, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($report_type === 'period'): ?>
                        <!-- Period Report -->
                        <div class="chart-area mb-4">
                            <canvas id="periodChart"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Evaluation Period</th>
                                        <th>Average Score</th>
                                        <th>Number of Evaluations</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($period_avg as $period => $data): ?>
                                        <tr>
                                            <td><?php echo $period; ?></td>
                                            <td><?php echo number_format($data['avg'], 2); ?>/5.00</td>
                                            <td><?php echo $data['count']; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($data['avg'] / 5) * 100; ?>%;" aria-valuenow="<?php echo $data['avg']; ?>" aria-valuemin="0" aria-valuemax="5">
                                                        <?php echo number_format(($data['avg'] / 5) * 100, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle mr-1"></i> No evaluation data available for the selected period.
            </div>
        <?php endif; ?>
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
        <?php if (count($evaluation_data) > 0): ?>
            <?php if ($report_type === 'summary'): ?>
                // Performance Chart
                var ctx = document.getElementById('performanceChart');
                var performanceChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [<?php echo "'" . implode("', '", array_keys($period_avg)) . "'"; ?>],
                        datasets: [{
                            label: 'Average Score',
                            data: [<?php echo implode(', ', array_column($period_avg, 'avg')); ?>],
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            pointRadius: 3,
                            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            lineTension: 0.3
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                left: 10,
                                right: 25,
                                top: 25,
                                bottom: 0
                            }
                        },
                        scales: {
                            xAxes: [{
                                gridLines: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 7
                                }
                            }],
                            yAxes: [{
                                ticks: {
                                    maxTicksLimit: 5,
                                    padding: 10,
                                    min: 0,
                                    max: 5,
                                    stepSize: 1
                                },
                                gridLines: {
                                    color: "rgb(234, 236, 244)",
                                    zeroLineColor: "rgb(234, 236, 244)",
                                    drawBorder: false,
                                    borderDash: [2],
                                    zeroLineBorderDash: [2]
                                }
                            }],
                        },
                        legend: {
                            display: false
                        },
                        tooltips: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyFontColor: "#858796",
                            titleMarginBottom: 10,
                            titleFontColor: '#6e707e',
                            titleFontSize: 14,
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            intersect: false,
                            mode: 'index',
                            caretPadding: 10,
                            callbacks: {
                                label: function(tooltipItem, chart) {
                                    var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                                    return datasetLabel + ': ' + tooltipItem.yLabel.toFixed(2) + '/5.00';
                                }
                            }
                        }
                    }
                });
            <?php elseif ($report_type === 'criteria'): ?>
                // Criteria Chart
                var ctx = document.getElementById('criteriaChart');
                var criteriaChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [<?php echo "'" . implode("', '", array_keys($criteria_avg)) . "'"; ?>],
                        datasets: [{
                            label: 'Average Score',
                            data: [<?php echo implode(', ', array_column($criteria_avg, 'avg')); ?>],
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
            <?php elseif ($report_type === 'evaluator'): ?>
                // Evaluator Chart
                var ctx = document.getElementById('evaluatorChart');
                var evaluatorChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [<?php
                            $roles = array_keys($evaluator_avg);
                            $roles = array_map('ucfirst', $roles);
                            echo "'" . implode("', '", $roles) . "'";
                        ?>],
                        datasets: [{
                            label: 'Average Score',
                            data: [<?php echo implode(', ', array_column($evaluator_avg, 'avg')); ?>],
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
            <?php elseif ($report_type === 'period'): ?>
                // Period Chart
                var ctx = document.getElementById('periodChart');
                var periodChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [<?php echo "'" . implode("', '", array_keys($period_avg)) . "'"; ?>],
                        datasets: [{
                            label: 'Average Score',
                            data: [<?php echo implode(', ', array_column($period_avg, 'avg')); ?>],
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            pointRadius: 3,
                            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            lineTension: 0.3
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
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';

/**
 * Get performance rating based on score
 *
 * @param float $score Average score
 * @return string Performance rating
 */
function get_performance_rating($score) {
    if ($score >= 4.5) {
        return 'Excellent';
    } elseif ($score >= 3.5) {
        return 'Very Good';
    } elseif ($score >= 2.5) {
        return 'Good';
    } elseif ($score >= 1.5) {
        return 'Fair';
    } else {
        return 'Poor';
    }
}
?>

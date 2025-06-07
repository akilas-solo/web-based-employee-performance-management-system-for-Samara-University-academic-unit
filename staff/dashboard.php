<?php
/**
 * Samara University Academic Performance Evaluation System
 * Staff Dashboard
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has staff role
if (!is_logged_in() || !has_role('staff')) {
    redirect($base_url . 'login.php');
}

// Get staff information
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];
$college_id = $_SESSION['college_id'];
$department_name = '';
$college_name = '';
$total_evaluations = 0;
$recent_evaluations = [];
$evaluation_scores = [];
$evaluation_periods = [];

// Get department name
if ($department_id) {
    $sql = "SELECT name FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $department_name = $row['name'];
    }
}

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

// Get total evaluations
$sql = "SELECT COUNT(*) as count FROM evaluations WHERE evaluatee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $total_evaluations = $row['count'];
}

// Get recent evaluations
$sql = "SELECT e.*, ep.title as period_name, u.full_name as evaluator_name, u.role as evaluator_role
        FROM evaluations e
        JOIN evaluation_periods ep ON e.period_id = ep.period_id
        JOIN users u ON e.evaluator_id = u.user_id
        WHERE e.evaluatee_id = ?
        ORDER BY e.updated_at DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $recent_evaluations[] = $row;
        }
    }
} else {
    // Handle error - query preparation failed
    error_log("Error preparing query: " . $conn->error);
}

// Get evaluation scores by period
$sql = "SELECT ep.title as period_name, AVG(e.total_score) as avg_score
        FROM evaluations e
        JOIN evaluation_periods ep ON e.period_id = ep.period_id
        WHERE e.evaluatee_id = ? AND e.status IN ('submitted', 'reviewed', 'approved')
        GROUP BY e.period_id
        ORDER BY ep.end_date DESC
        LIMIT 6";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $evaluation_scores[] = $row;
        }
    }
} else {
    // Handle error - query preparation failed
    error_log("Error preparing query: " . $conn->error);
}

// Get active evaluation periods
$sql = "SELECT * FROM evaluation_periods WHERE status = 'active' ORDER BY end_date ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluation_periods[] = $row;
    }
} else {
    // If no active periods, get the most recent period
    $sql = "SELECT * FROM evaluation_periods ORDER BY end_date DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $evaluation_periods[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Staff Dashboard</h1>
            <div>
                <?php if (count($evaluation_periods) > 0): ?>
                    <div class="alert alert-info d-inline-block mb-0 py-2">
                        <i class="fas fa-info-circle mr-1"></i> Active Evaluation Period:
                        <?php foreach ($evaluation_periods as $index => $period): ?>
                            <strong><?php echo $period['title']; ?></strong>
                            <?php echo ($index < count($evaluation_periods) - 1) ? ', ' : ''; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Staff Info Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Department</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $department_name ?: 'N/A'; ?></div>
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
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">College</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $college_name ?: 'N/A'; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-university fa-2x text-gray-300"></i>
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
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Evaluations</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_evaluations; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
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
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Last Evaluation</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php if (count($recent_evaluations) > 0): ?>
                                        <?php echo date('M d, Y', strtotime($recent_evaluations[0]['updated_at'])); ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Chart and Recent Evaluations -->
        <div class="row">
            <!-- Performance Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Performance Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="performanceChart"></canvas>
                        </div>
                        <?php if (count($evaluation_scores) === 0): ?>
                            <div class="text-center mt-4">
                                <p class="text-muted">No evaluation data available yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Evaluations -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Evaluations</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_evaluations) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($recent_evaluations as $evaluation): ?>
                                    <a href="<?php echo $base_url; ?>staff/evaluation_details.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo $evaluation['period_name']; ?></h6>
                                            <small><?php echo date('M d, Y', strtotime($evaluation['updated_at'])); ?></small>
                                        </div>
                                        <p class="mb-1">Evaluated by: <?php echo $evaluation['evaluator_name']; ?> (<?php echo ucfirst($evaluation['evaluator_role']); ?>)</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">Score: <?php echo number_format($evaluation['total_score'], 2); ?>/5.00</small>
                                            <span class="badge badge-<?php echo get_status_color($evaluation['status']); ?>"><?php echo ucfirst($evaluation['status']); ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No evaluations found.</p>
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
        // Performance Chart
        var ctx = document.getElementById('performanceChart');

        <?php if (count($evaluation_scores) > 0): ?>
            var performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [<?php echo "'" . implode("', '", array_column($evaluation_scores, 'period_name')) . "'"; ?>],
                    datasets: [{
                        label: 'Average Score',
                        data: [<?php echo implode(', ', array_column($evaluation_scores, 'avg_score')); ?>],
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
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';

/**
 * Get status color for badges
 *
 * @param string $status Status string
 * @return string Bootstrap color class
 */
function get_status_color($status) {
    switch ($status) {
        case 'draft':
            return 'secondary';
        case 'submitted':
            return 'primary';
        case 'reviewed':
            return 'info';
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

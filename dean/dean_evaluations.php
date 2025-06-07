<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - Dean Evaluations
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
$dean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;

// Check if dean_id is provided
if ($dean_id <= 0) {
    redirect($base_url . 'dean/deans.php');
}

// Get dean information
$dean = null;
$sql = "SELECT u.*, c.name as college_name
        FROM users u
        LEFT JOIN colleges c ON u.college_id = c.college_id
        WHERE u.user_id = ? AND u.role = 'dean' AND u.college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $dean_id, $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $dean = $result->fetch_assoc();
} else {
    redirect($base_url . 'dean/deans.php');
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

// Get active evaluation periods
$active_periods = [];
$sql = "SELECT * FROM evaluation_periods WHERE status = 'active' ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $active_periods[] = $row;
    }
}

// Get evaluations for this dean
$evaluations = [];
$sql = "SELECT e.*, p.title as period_title, p.academic_year, p.semester,
        u.full_name as evaluator_name, u.role as evaluator_role
        FROM evaluations e
        JOIN evaluation_periods p ON e.period_id = p.period_id
        JOIN users u ON e.evaluator_id = u.user_id
        WHERE e.evaluatee_id = ?";

// Add period filter if provided
if ($period_id > 0) {
    $sql .= " AND e.period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $dean_id, $period_id);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $dean_id);
}

$sql .= " ORDER BY e.created_at DESC";
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Evaluations for <?php echo $dean['full_name']; ?></h1>
            <div>
                <a href="<?php echo $base_url; ?>dean/view_dean.php?id=<?php echo $dean_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Dean Profile
                </a>
                <?php if (count($active_periods) > 0): ?>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-success shadow-sm dropdown-toggle" type="button" id="evaluateDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-edit fa-sm text-white-50 mr-1"></i> Evaluate
                        </button>
                        <div class="dropdown-menu" aria-labelledby="evaluateDropdown">
                            <?php foreach ($active_periods as $period): ?>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>dean/evaluation_form.php?evaluatee_id=<?php echo $dean_id; ?>&period_id=<?php echo $period['period_id']; ?>">
                                    <?php echo $period['title']; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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

        <!-- Filter Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Filter Evaluations</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                    <input type="hidden" name="id" value="<?php echo $dean_id; ?>">
                    <div class="form-group mr-3">
                        <label for="period_id" class="mr-2">Evaluation Period:</label>
                        <select class="form-control" id="period_id" name="period_id">
                            <option value="0">All Periods</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, <?php echo $period['semester']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-theme">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </form>
            </div>
        </div>

        <!-- Performance Summary Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Performance Summary</h6>
            </div>
            <div class="card-body">
                <?php if (count($evaluations) > 0): ?>
                    <?php
                    // Calculate average score
                    $avg_score = 0;
                    $total_evaluations = count($evaluations);
                    if ($total_evaluations > 0) {
                        $sum = 0;
                        foreach ($evaluations as $evaluation) {
                            $sum += $evaluation['total_score'];
                        }
                        $avg_score = $sum / $total_evaluations;
                    }

                    // Count evaluations by status
                    $status_counts = [
                        'completed' => 0,
                        'in_progress' => 0,
                        'pending' => 0
                    ];
                    foreach ($evaluations as $evaluation) {
                        $status_counts[$evaluation['status']]++;
                    }

                    // Count evaluations by score range
                    $score_ranges = [
                        'excellent' => 0, // 4.5-5.0
                        'very_good' => 0, // 3.5-4.4
                        'good' => 0,      // 2.5-3.4
                        'fair' => 0,      // 1.5-2.4
                        'poor' => 0       // 0-1.4
                    ];
                    foreach ($evaluations as $evaluation) {
                        $score = $evaluation['total_score'];
                        if ($score >= 4.5) {
                            $score_ranges['excellent']++;
                        } elseif ($score >= 3.5) {
                            $score_ranges['very_good']++;
                        } elseif ($score >= 2.5) {
                            $score_ranges['good']++;
                        } elseif ($score >= 1.5) {
                            $score_ranges['fair']++;
                        } else {
                            $score_ranges['poor']++;
                        }
                    }
                    ?>
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <h1 class="text-theme"><?php echo number_format($avg_score, 2); ?></h1>
                            <p>Average Performance Score (out of 5.00)</p>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-theme" role="progressbar" 
                                    style="width: <?php echo ($avg_score / 5) * 100; ?>%" 
                                    aria-valuenow="<?php echo $avg_score; ?>" 
                                    aria-valuemin="0" aria-valuemax="5">
                                    <?php echo number_format($avg_score, 2); ?>/5
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center mb-4">
                            <div class="chart-pie">
                                <canvas id="evaluationStatusChart"></canvas>
                            </div>
                            <p>Evaluation Status</p>
                        </div>
                        <div class="col-md-4 text-center mb-4">
                            <div class="chart-pie">
                                <canvas id="scoreRangesChart"></canvas>
                            </div>
                            <p>Score Distribution</p>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-center">No evaluations found for this dean.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluations Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">All Evaluations</h6>
            </div>
            <div class="card-body">
                <?php if (count($evaluations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="evaluationsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Academic Year</th>
                                    <th>Semester</th>
                                    <th>Evaluator</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evaluations as $evaluation): ?>
                                    <tr>
                                        <td><?php echo $evaluation['period_title']; ?></td>
                                        <td><?php echo $evaluation['academic_year']; ?></td>
                                        <td><?php echo $evaluation['semester']; ?></td>
                                        <td>
                                            <?php echo $evaluation['evaluator_name']; ?>
                                            <small class="d-block text-muted"><?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluator_role'])); ?></small>
                                        </td>
                                        <td><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo ($evaluation['status'] == 'completed') ? 'success' : 
                                                    (($evaluation['status'] == 'in_progress') ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($evaluation['status']); ?>
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
                    <p class="text-center">No evaluations found for this dean.</p>
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
        
        <?php if (count($evaluations) > 0): ?>
            // Prepare data for charts
            var scoreLabels = ['Excellent (4.5-5.0)', 'Very Good (3.5-4.4)', 'Good (2.5-3.4)', 'Fair (1.5-2.4)', 'Poor (0-1.4)'];
            var scoreData = [
                <?php echo $score_ranges['excellent']; ?>,
                <?php echo $score_ranges['very_good']; ?>,
                <?php echo $score_ranges['good']; ?>,
                <?php echo $score_ranges['fair']; ?>,
                <?php echo $score_ranges['poor']; ?>
            ];
            
            var statusLabels = ['Completed', 'In Progress', 'Pending'];
            var statusData = [
                <?php echo $status_counts['completed']; ?>,
                <?php echo $status_counts['in_progress']; ?>,
                <?php echo $status_counts['pending']; ?>
            ];
            
            // Create score ranges chart
            var scoreRangesCtx = document.getElementById('scoreRangesChart');
            if (scoreRangesCtx) {
                new Chart(scoreRangesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: scoreLabels,
                        datasets: [{
                            data: scoreData,
                            backgroundColor: [
                                '#1cc88a', // Excellent
                                '#4e73df', // Very Good
                                '#36b9cc', // Good
                                '#f6c23e', // Fair
                                '#e74a3b'  // Poor
                            ],
                            hoverBackgroundColor: [
                                '#17a673',
                                '#2e59d9',
                                '#2c9faf',
                                '#dda20a',
                                '#be2617'
                            ],
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
            
            // Create evaluation status chart
            var evaluationStatusCtx = document.getElementById('evaluationStatusChart');
            if (evaluationStatusCtx) {
                new Chart(evaluationStatusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusData,
                            backgroundColor: [
                                '#1cc88a', // Completed
                                '#f6c23e', // In Progress
                                '#858796'  // Pending
                            ],
                            hoverBackgroundColor: [
                                '#17a673',
                                '#dda20a',
                                '#6e707e'
                            ],
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
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once $GLOBALS['BASE_PATH'] . '/includes/footer_management.php';
?>

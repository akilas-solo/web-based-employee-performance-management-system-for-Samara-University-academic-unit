<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - Department Head Evaluations
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
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$head = null;
$evaluations = [];
$periods = [];
$period_details = null;
$evaluation_history = [];

// Check if head_id is provided
if ($head_id <= 0) {
    redirect($base_url . 'dean/department_heads.php');
}

// Get department head information
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

// Get all evaluation periods
$sql = "SELECT * FROM evaluation_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row;
    }
}

// Get period details if period_id is provided
if ($period_id > 0) {
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $period_details = $result->fetch_assoc();
    }
}

// Get evaluations for the department head
$sql = "SELECT e.*, 
        u.full_name as evaluator_name, 
        u.role as evaluator_role,
        p.title as period_title,
        p.academic_year,
        p.semester
        FROM evaluations e 
        JOIN users u ON e.evaluator_id = u.user_id 
        JOIN evaluation_periods p ON e.period_id = p.period_id 
        WHERE e.evaluatee_id = ?";

// Add period filter if provided
if ($period_id > 0) {
    $sql .= " AND e.period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $head_id, $period_id);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $head_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
    }
}

// Get evaluation history (scores by period)
$sql = "SELECT p.period_id, p.title, p.academic_year, p.semester,
        AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count
        FROM evaluation_periods p
        LEFT JOIN evaluations e ON p.period_id = e.period_id AND e.evaluatee_id = ?
        GROUP BY p.period_id
        ORDER BY p.start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $head_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluation_history[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Department Head Evaluations</h1>
            <div>
                <a href="<?php echo $base_url; ?>dean/view_head.php?id=<?php echo $head_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Head Profile
                </a>
                <?php if (count($periods) > 0): ?>
                    <div class="dropdown d-inline-block">
                        <button class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm dropdown-toggle" type="button" id="evaluateDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-clipboard-check fa-sm text-white-50 mr-1"></i> Evaluate
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="evaluateDropdown">
                            <?php foreach ($periods as $period): ?>
                                <?php if ($period['status'] == 1): ?>
                                    <a class="dropdown-item" href="<?php echo $base_url; ?>dean/evaluation_form.php?evaluatee_id=<?php echo $head_id; ?>&period_id=<?php echo $period['period_id']; ?>">
                                        <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                    </a>
                                <?php endif; ?>
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

        <!-- Head Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">Department Head Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <?php if (!empty($head['profile_image'])): ?>
                            <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $head['profile_image']; ?>" alt="<?php echo $head['full_name']; ?>" class="img-profile rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-5">
                        <h4 class="text-theme"><?php echo $head['full_name']; ?></h4>
                        <p>
                            <i class="fas fa-envelope mr-2 text-theme"></i> <?php echo $head['email']; ?><br>
                            <?php if (!empty($head['phone'])): ?>
                                <i class="fas fa-phone mr-2 text-theme"></i> <?php echo $head['phone']; ?><br>
                            <?php endif; ?>
                            <i class="fas fa-building mr-2 text-theme"></i> <?php echo $head['department_name']; ?> (<?php echo $head['department_code']; ?>)<br>
                            <i class="fas fa-university mr-2 text-theme"></i> <?php echo $head['college_name']; ?>
                        </p>
                    </div>
                    <div class="col-md-5">
                        <?php
                        // Calculate overall average score
                        $overall_avg = 0;
                        $total_evaluations = 0;
                        foreach ($evaluation_history as $period) {
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
                            <div class="col-6 text-center">
                                <div class="h2 mb-0 font-weight-bold text-theme"><?php echo number_format($overall_avg, 2); ?></div>
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Overall Score (out of 5.00)</div>
                                <div class="mt-2">
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar bg-theme" role="progressbar" style="width: <?php echo ($overall_avg / 5) * 100; ?>%" aria-valuenow="<?php echo $overall_avg; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="h2 mb-0 font-weight-bold text-theme"><?php echo $total_evaluations; ?></div>
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Evaluations</div>
                                <div class="mt-2">
                                    <span class="badge badge-<?php 
                                        if ($overall_avg >= 4.5) {
                                            echo 'success';
                                        } elseif ($overall_avg >= 3.5) {
                                            echo 'primary';
                                        } elseif ($overall_avg >= 2.5) {
                                            echo 'info';
                                        } elseif ($overall_avg >= 1.5) {
                                            echo 'warning';
                                        } else {
                                            echo 'danger';
                                        }
                                    ?> p-2">
                                        <?php
                                        if ($overall_avg >= 4.5) {
                                            echo 'Excellent';
                                        } elseif ($overall_avg >= 3.5) {
                                            echo 'Very Good';
                                        } elseif ($overall_avg >= 2.5) {
                                            echo 'Good';
                                        } elseif ($overall_avg >= 1.5) {
                                            echo 'Fair';
                                        } else {
                                            echo 'Poor';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Filter Evaluations</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                    <input type="hidden" name="id" value="<?php echo $head_id; ?>">
                    <div class="form-group mb-2 mr-2">
                        <label for="period_id" class="mr-2">Evaluation Period:</label>
                        <select class="form-control" id="period_id" name="period_id">
                            <option value="0">All Periods</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-theme mb-2">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <?php if ($period_id > 0): ?>
                        <a href="<?php echo $base_url; ?>dean/head_evaluations.php?id=<?php echo $head_id; ?>" class="btn btn-secondary mb-2 ml-2">
                            <i class="fas fa-times mr-1"></i> Clear Filter
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Evaluations Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">
                    <?php 
                    if ($period_id > 0 && $period_details) {
                        echo 'Evaluations for ' . $period_details['title'] . ' (' . $period_details['academic_year'] . ', Semester ' . $period_details['semester'] . ')';
                    } else {
                        echo 'All Evaluations';
                    }
                    ?>
                </h6>
            </div>
            <div class="card-body">
                <?php if (count($evaluations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="evaluationsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Period</th>
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
                                        <td>
                                            <?php echo $evaluation['period_title']; ?><br>
                                            <span class="small text-gray-600">
                                                <?php echo $evaluation['academic_year']; ?>, Semester <?php echo $evaluation['semester']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $evaluation['evaluator_name']; ?><br>
                                            <span class="badge badge-info"><?php echo ucwords(str_replace('_', ' ', $evaluation['evaluator_role'])); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($evaluation['total_score'] > 0): ?>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo number_format($evaluation['total_score'], 2); ?>/5.00
                                                </div>
                                                <div class="progress progress-sm mr-2 mt-1">
                                                    <div class="progress-bar bg-<?php 
                                                        $score_percent = ($evaluation['total_score'] / 5) * 100;
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
                                                        aria-valuenow="<?php echo $evaluation['total_score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-500">Not scored</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                switch ($evaluation['status']) {
                                                    case 'draft':
                                                        echo 'secondary';
                                                        break;
                                                    case 'submitted':
                                                        echo 'info';
                                                        break;
                                                    case 'reviewed':
                                                        echo 'warning';
                                                        break;
                                                    case 'approved':
                                                        echo 'success';
                                                        break;
                                                    case 'rejected':
                                                        echo 'danger';
                                                        break;
                                                    default:
                                                        echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($evaluation['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?>
                                            <?php if (!empty($evaluation['submission_date'])): ?>
                                                <br>
                                                <span class="small text-gray-600">
                                                    Submitted: <?php echo date('M d, Y', strtotime($evaluation['submission_date'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>dean/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>dean/print_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No evaluations found for this department head<?php echo ($period_id > 0) ? ' in the selected period' : ''; ?>.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance History Chart -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Performance History</h6>
            </div>
            <div class="card-body">
                <?php if (count($evaluation_history) > 0): ?>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="performanceHistoryChart"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-center">No performance history available.</p>
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
        
        // Chart.js - Performance History
        var performanceHistoryCtx = document.getElementById('performanceHistoryChart');
        
        <?php if (count($evaluation_history) > 0): ?>
            // Prepare data for chart
            var periods = [];
            var scores = [];
            var counts = [];
            
            <?php foreach ($evaluation_history as $period): ?>
                periods.push('<?php echo $period['title'] . ' (' . $period['academic_year'] . ', Sem ' . $period['semester'] . ')'; ?>');
                scores.push(<?php echo $period['avg_score'] ? $period['avg_score'] : 0; ?>);
                counts.push(<?php echo $period['evaluation_count']; ?>);
            <?php endforeach; ?>
            
            new Chart(performanceHistoryCtx, {
                type: 'bar',
                data: {
                    labels: periods,
                    datasets: [{
                        label: 'Average Score',
                        data: scores,
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
                                max: 5,
                                stepSize: 1
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Average Score (out of 5)'
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var score = data.datasets[0].data[tooltipItem.index];
                                var count = counts[tooltipItem.index];
                                return 'Score: ' + score.toFixed(2) + ' (from ' + count + ' evaluation' + (count !== 1 ? 's' : '') + ')';
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
include_once $GLOBALS['BASE_PATH'] . '/includes/footer_management.php';
?>

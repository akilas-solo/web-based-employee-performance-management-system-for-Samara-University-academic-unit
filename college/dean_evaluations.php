<?php
/**
 * Samara University Academic Performance Evaluation System
 * College - Dean Evaluations
 */

// Include configuration file - use direct path to avoid memory issues
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has college role
if (!is_logged_in() || !has_role('college')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$college_id = $_SESSION['college_id'];
$dean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Check if dean_id is provided
if ($dean_id <= 0) {
    redirect($base_url . 'college/deans.php');
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
    redirect($base_url . 'college/deans.php');
}

// Get evaluation periods
$periods = [];
$sql = "SELECT * FROM evaluation_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[$row['period_id']] = $row;
    }
}

// Build query conditions
$conditions = ["e.evaluatee_id = ?"];
$params = [$dean_id];
$param_types = "i";

if ($period_id > 0) {
    $conditions[] = "e.period_id = ?";
    $params[] = $period_id;
    $param_types .= "i";
}

if (!empty($status)) {
    $conditions[] = "e.status = ?";
    $params[] = $status;
    $param_types .= "s";
}

// Get evaluations
$evaluations = [];
$sql = "SELECT e.*, u.full_name as evaluator_name, u.role as evaluator_role,
        p.title as period_title, p.academic_year, p.semester
        FROM evaluations e
        JOIN users u ON e.evaluator_id = u.user_id
        JOIN evaluation_periods p ON e.period_id = p.period_id
        WHERE " . implode(" AND ", $conditions) . "
        ORDER BY e.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
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
                <a href="<?php echo $base_url; ?>college/view_dean.php?id=<?php echo $dean_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Dean Profile
                </a>
                <?php if (count($active_periods) > 0): ?>
                    <div class="dropdown d-inline-block">
                        <button class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm dropdown-toggle" type="button" id="evaluateDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-clipboard-check fa-sm text-white-50 mr-1"></i> Evaluate
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="evaluateDropdown">
                            <?php foreach ($active_periods as $period): ?>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>college/evaluation_form.php?evaluatee_id=<?php echo $dean_id; ?>&period_id=<?php echo $period['period_id']; ?>">
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
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

        <!-- Filters Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">Filter Evaluations</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                    <input type="hidden" name="id" value="<?php echo $dean_id; ?>">
                    
                    <div class="form-group mb-2 mr-2">
                        <label for="period_id" class="mr-2">Period:</label>
                        <select class="form-control" id="period_id" name="period_id">
                            <option value="0">All Periods</option>
                            <?php foreach ($periods as $p): ?>
                                <option value="<?php echo $p['period_id']; ?>" <?php echo ($period_id == $p['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $p['title']; ?> (<?php echo $p['academic_year']; ?>, Sem <?php echo $p['semester']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mb-2 mr-2">
                        <label for="status" class="mr-2">Status:</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="draft" <?php echo ($status === 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="submitted" <?php echo ($status === 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                            <option value="reviewed" <?php echo ($status === 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="approved" <?php echo ($status === 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo ($status === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-theme mb-2">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    
                    <a href="<?php echo $base_url; ?>college/dean_evaluations.php?id=<?php echo $dean_id; ?>" class="btn btn-secondary mb-2 ml-2">
                        <i class="fas fa-sync-alt mr-1"></i> Reset
                    </a>
                </form>
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
                                    <th>Evaluator</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Submission Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evaluations as $evaluation): ?>
                                    <tr>
                                        <td><?php echo $evaluation['period_title']; ?> (<?php echo $evaluation['academic_year']; ?>, Sem <?php echo $evaluation['semester']; ?>)</td>
                                        <td>
                                            <?php echo $evaluation['evaluator_name']; ?><br>
                                            <span class="badge badge-info"><?php echo ucwords(str_replace('_', ' ', $evaluation['evaluator_role'])); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($evaluation['status'] !== 'draft'): ?>
                                                <?php echo number_format($evaluation['total_score'], 2); ?>/5.00
                                            <?php else: ?>
                                                <span class="text-muted">Not submitted</span>
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
                                            <?php if ($evaluation['submission_date']): ?>
                                                <?php echo date('M d, Y', strtotime($evaluation['submission_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not submitted</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>college/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($evaluation['evaluator_id'] == $user_id && $evaluation['status'] === 'draft'): ?>
                                                <a href="<?php echo $base_url; ?>college/evaluation_form.php?evaluation_id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo $base_url; ?>college/print_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                                <i class="fas fa-print"></i>
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

        <!-- Performance Summary Card -->
        <?php if (count($evaluations) > 0): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-theme text-white">
                    <h6 class="m-0 font-weight-bold">Performance Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container" style="position: relative; height:300px;">
                                <canvas id="scoreDistributionChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container" style="position: relative; height:300px;">
                                <canvas id="evaluatorDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
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
        $('#evaluationsTable').DataTable();
        
        <?php if (count($evaluations) > 0): ?>
            // Prepare data for charts
            var scoreLabels = ['Excellent (4.5-5.0)', 'Very Good (3.5-4.4)', 'Good (2.5-3.4)', 'Fair (1.5-2.4)', 'Poor (0-1.4)'];
            var scoreData = [0, 0, 0, 0, 0];
            
            var evaluatorRoles = [];
            var evaluatorCounts = [];
            var evaluatorColors = [];
            var roleData = {};
            
            <?php foreach ($evaluations as $evaluation): ?>
                <?php if ($evaluation['status'] !== 'draft'): ?>
                    // Score distribution
                    var score = <?php echo $evaluation['total_score']; ?>;
                    if (score >= 4.5) {
                        scoreData[0]++;
                    } else if (score >= 3.5) {
                        scoreData[1]++;
                    } else if (score >= 2.5) {
                        scoreData[2]++;
                    } else if (score >= 1.5) {
                        scoreData[3]++;
                    } else {
                        scoreData[4]++;
                    }
                    
                    // Evaluator distribution
                    var role = '<?php echo ucwords(str_replace('_', ' ', $evaluation['evaluator_role'])); ?>';
                    if (!roleData[role]) {
                        roleData[role] = 1;
                    } else {
                        roleData[role]++;
                    }
                <?php endif; ?>
            <?php endforeach; ?>
            
            // Convert role data to arrays
            for (var role in roleData) {
                evaluatorRoles.push(role);
                evaluatorCounts.push(roleData[role]);
                evaluatorColors.push(getRandomColor());
            }
            
            // Score Distribution Chart
            var scoreDistributionCtx = document.getElementById('scoreDistributionChart');
            new Chart(scoreDistributionCtx, {
                type: 'bar',
                data: {
                    labels: scoreLabels,
                    datasets: [{
                        label: 'Number of Evaluations',
                        data: scoreData,
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(0, 123, 255, 0.7)',
                            'rgba(23, 162, 184, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(0, 123, 255, 1)',
                            'rgba(23, 162, 184, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    title: {
                        display: true,
                        text: 'Score Distribution'
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                stepSize: 1
                            }
                        }]
                    }
                }
            });
            
            // Evaluator Distribution Chart
            var evaluatorDistributionCtx = document.getElementById('evaluatorDistributionChart');
            new Chart(evaluatorDistributionCtx, {
                type: 'pie',
                data: {
                    labels: evaluatorRoles,
                    datasets: [{
                        data: evaluatorCounts,
                        backgroundColor: evaluatorColors,
                        borderColor: evaluatorColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    title: {
                        display: true,
                        text: 'Evaluations by Role'
                    }
                }
            });
        <?php endif; ?>
        
        // Function to generate random colors
        function getRandomColor() {
            var letters = '0123456789ABCDEF';
            var color = '#';
            for (var i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }
    });
</script>

<?php
// Include footer
include_once $GLOBALS['BASE_PATH'] . '/includes/footer_management.php';
?>

<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - Deans Management
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

// Get all deans in the college
$deans = [];
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM evaluations e WHERE e.evaluator_id = u.user_id) as evaluation_count,
        (SELECT AVG(e.total_score) FROM evaluations e WHERE e.evaluatee_id = u.user_id) as avg_score
        FROM users u 
        WHERE u.role = 'dean' AND u.college_id = ? 
        ORDER BY u.full_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $deans[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Deans in <?php echo $college_name; ?></h1>
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

        <!-- Deans Overview -->
        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Deans Overview</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($deans) > 0): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="chart-area">
                                        <canvas id="deanPerformanceChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="chart-area">
                                        <canvas id="deanEvaluationsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No deans found in your college.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deans Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">All Deans</h6>
            </div>
            <div class="card-body">
                <?php if (count($deans) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="deansTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Evaluations</th>
                                    <th>Avg. Performance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deans as $dean): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($dean['profile_image'])): ?>
                                                <img class="img-profile rounded-circle mr-2" src="<?php echo $base_url; ?>uploads/profiles/<?php echo $dean['profile_image']; ?>" alt="Profile" style="width: 30px; height: 30px;">
                                            <?php else: ?>
                                                <img class="img-profile rounded-circle mr-2" src="<?php echo $base_url; ?>assets/images/default-profile.png" alt="Default Profile" style="width: 30px; height: 30px;">
                                            <?php endif; ?>
                                            <?php echo $dean['full_name']; ?>
                                        </td>
                                        <td><?php echo $dean['email']; ?></td>
                                        <td><?php echo !empty($dean['phone']) ? $dean['phone'] : 'N/A'; ?></td>
                                        <td><?php echo $dean['evaluation_count']; ?></td>
                                        <td>
                                            <?php if ($dean['avg_score']): ?>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-theme" role="progressbar" 
                                                        style="width: <?php echo ($dean['avg_score'] / 5) * 100; ?>%" 
                                                        aria-valuenow="<?php echo $dean['avg_score']; ?>" 
                                                        aria-valuemin="0" aria-valuemax="5">
                                                        <?php echo number_format($dean['avg_score'], 2); ?>/5
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No data</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo ($dean['status'] == 1) ? 'success' : 'danger'; ?>">
                                                <?php echo ($dean['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>dean/view_dean.php?id=<?php echo $dean['user_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>dean/dean_evaluations.php?id=<?php echo $dean['user_id']; ?>" class="btn btn-sm btn-primary" title="View Evaluations">
                                                <i class="fas fa-clipboard-list"></i>
                                            </a>
                                            <?php if (count($active_periods) > 0): ?>
                                                <div class="dropdown d-inline">
                                                    <button class="btn btn-sm btn-success dropdown-toggle" type="button" id="evaluateDropdown<?php echo $dean['user_id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="evaluateDropdown<?php echo $dean['user_id']; ?>">
                                                        <?php foreach ($active_periods as $period): ?>
                                                            <a class="dropdown-item" href="<?php echo $base_url; ?>dean/evaluation_form.php?evaluatee_id=<?php echo $dean['user_id']; ?>&period_id=<?php echo $period['period_id']; ?>">
                                                                <?php echo $period['title']; ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No deans found in your college.</p>
                <?php endif; ?>
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
        // Initialize DataTables
        $('#deansTable').DataTable();
        
        <?php if (count($deans) > 0): ?>
            // Chart.js - Dean Performance
            var deanPerformanceCtx = document.getElementById('deanPerformanceChart');
            var deanEvaluationsCtx = document.getElementById('deanEvaluationsChart');
            
            // Prepare data for charts
            var deanNames = [];
            var avgScores = [];
            var evaluationCounts = [];
            var backgroundColors = [];
            
            <?php foreach ($deans as $index => $dean): ?>
                deanNames.push('<?php echo $dean['full_name']; ?>');
                avgScores.push(<?php echo $dean['avg_score'] ? $dean['avg_score'] : 0; ?>);
                evaluationCounts.push(<?php echo $dean['evaluation_count']; ?>);
                backgroundColors.push(getRandomColor());
            <?php endforeach; ?>
            
            // Create performance chart
            if (deanPerformanceCtx) {
                new Chart(deanPerformanceCtx, {
                    type: 'bar',
                    data: {
                        labels: deanNames,
                        datasets: [{
                            label: 'Average Performance Score',
                            data: avgScores,
                            backgroundColor: backgroundColors,
                            borderColor: backgroundColors,
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
                        },
                        title: {
                            display: true,
                            text: 'Dean Performance Scores'
                        }
                    }
                });
            }
            
            // Create evaluations chart
            if (deanEvaluationsCtx) {
                new Chart(deanEvaluationsCtx, {
                    type: 'pie',
                    data: {
                        labels: deanNames,
                        datasets: [{
                            data: evaluationCounts,
                            backgroundColor: backgroundColors,
                            borderColor: backgroundColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        title: {
                            display: true,
                            text: 'Number of Evaluations Conducted'
                        }
                    }
                });
            }
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

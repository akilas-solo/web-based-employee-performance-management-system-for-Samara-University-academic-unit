<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - View Dean
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

// Get dean profile information
$dean_profile = null;
$table_exists = false;

// Check if dean_profiles table exists
$result = $conn->query("SHOW TABLES LIKE 'dean_profiles'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    $sql = "SELECT * FROM dean_profiles WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $dean_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $dean_profile = $result->fetch_assoc();
    }
}

// Get evaluations for this dean
$evaluations = [];
$sql = "SELECT e.*, p.title as period_title, p.academic_year, p.semester,
        u.full_name as evaluator_name, u.role as evaluator_role
        FROM evaluations e
        JOIN evaluation_periods p ON e.period_id = p.period_id
        JOIN users u ON e.evaluator_id = u.user_id
        WHERE e.evaluatee_id = ?
        ORDER BY e.created_at DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dean_id);
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
            <h1 class="h3 mb-0 text-gray-800">Dean Profile</h1>
            <div>
                <a href="<?php echo $base_url; ?>dean/deans.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Deans
                </a>
                <a href="<?php echo $base_url; ?>dean/dean_evaluations.php?id=<?php echo $dean_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-chart-bar fa-sm text-white-50 mr-1"></i> View Evaluations
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

        <div class="row">
            <!-- Dean Profile Card -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Dean Profile</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($dean['profile_image'])): ?>
                                <img class="img-profile rounded-circle mb-3" src="<?php echo $base_url; ?>uploads/profiles/<?php echo $dean['profile_image']; ?>" alt="Profile Image" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px; font-size: 3rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <h4 class="text-theme"><?php echo $dean['full_name']; ?></h4>
                            <p class="text-muted"><?php echo $dean['position'] ?? 'Dean'; ?></p>
                            <p>
                                <span class="badge badge-primary">Dean</span>
                                <span class="badge badge-<?php echo ($dean['status'] == 1) ? 'success' : 'danger'; ?>">
                                    <?php echo ($dean['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">Contact Information</h6>
                            <p>
                                <i class="fas fa-envelope mr-2 text-theme"></i> <?php echo $dean['email']; ?><br>
                                <?php if (!empty($dean['phone'])): ?>
                                    <i class="fas fa-phone mr-2 text-theme"></i> <?php echo $dean['phone']; ?><br>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">College</h6>
                            <p>
                                <i class="fas fa-university mr-2 text-theme"></i> <?php echo $dean['college_name'] ?? 'Not assigned'; ?>
                            </p>
                        </div>

                        <?php if ($dean_profile): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Academic Information</h6>
                                <p>
                                    <?php if (!empty($dean_profile['academic_rank'])): ?>
                                        <i class="fas fa-graduation-cap mr-2 text-theme"></i> <?php echo $dean_profile['academic_rank']; ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($dean_profile['specialization'])): ?>
                                        <i class="fas fa-book mr-2 text-theme"></i> <?php echo $dean_profile['specialization']; ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($dean_profile['years_of_experience'])): ?>
                                        <i class="fas fa-history mr-2 text-theme"></i> <?php echo $dean_profile['years_of_experience']; ?> years of experience<br>
                                    <?php endif; ?>
                                    <?php if (!empty($dean_profile['appointment_date'])): ?>
                                        <i class="fas fa-calendar-alt mr-2 text-theme"></i> Appointed: <?php echo date('M d, Y', strtotime($dean_profile['appointment_date'])); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($dean['bio'])): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Bio</h6>
                                <p><?php echo $dean['bio']; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Performance Summary Card -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Performance Summary</h6>
                    </div>
                    <div class="card-body">
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
                        ?>
                        <div class="row">
                            <div class="col-md-6 text-center mb-4">
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
                            <div class="col-md-6 text-center mb-4">
                                <h1 class="text-theme"><?php echo $total_evaluations; ?></h1>
                                <p>Total Evaluations</p>
                                <div class="chart-pie">
                                    <canvas id="evaluationStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="chart-area">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Evaluations Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Recent Evaluations</h6>
                        <a href="<?php echo $base_url; ?>dean/dean_evaluations.php?id=<?php echo $dean_id; ?>" class="btn btn-sm btn-theme">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($evaluations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Evaluator</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($evaluations as $evaluation): ?>
                                            <tr>
                                                <td><?php echo $evaluation['period_title']; ?></td>
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
        // Chart.js - Performance Chart
        var performanceCtx = document.getElementById('performanceChart');
        var evaluationStatusCtx = document.getElementById('evaluationStatusChart');
        
        <?php if (count($evaluations) > 0): ?>
            // Prepare data for performance chart
            var periods = [];
            var scores = [];
            
            <?php 
            // Reverse array to show oldest to newest
            $reversed_evals = array_reverse($evaluations);
            foreach ($reversed_evals as $evaluation): 
            ?>
                periods.push('<?php echo $evaluation['period_title']; ?>');
                scores.push(<?php echo $evaluation['total_score']; ?>);
            <?php endforeach; ?>
            
            // Create performance chart
            if (performanceCtx) {
                new Chart(performanceCtx, {
                    type: 'line',
                    data: {
                        labels: periods,
                        datasets: [{
                            label: 'Performance Score',
                            data: scores,
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                            borderWidth: 2,
                            fill: true
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
                            text: 'Performance Trend'
                        }
                    }
                });
            }
            
            // Count evaluations by status
            var statusCounts = {
                'completed': 0,
                'in_progress': 0,
                'pending': 0
            };
            
            <?php foreach ($evaluations as $evaluation): ?>
                statusCounts['<?php echo $evaluation['status']; ?>']++;
            <?php endforeach; ?>
            
            // Create evaluation status chart
            if (evaluationStatusCtx) {
                new Chart(evaluationStatusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed', 'In Progress', 'Pending'],
                        datasets: [{
                            data: [
                                statusCounts['completed'],
                                statusCounts['in_progress'],
                                statusCounts['pending']
                            ],
                            backgroundColor: [
                                '#1cc88a',
                                '#f6c23e',
                                '#858796'
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

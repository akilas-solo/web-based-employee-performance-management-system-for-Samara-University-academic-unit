<?php
/**
 * Samara University Academic Performance Evaluation System
 * College - View Dean
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

// Get active evaluation periods
$active_periods = [];
$sql = "SELECT * FROM evaluation_periods WHERE status = 'active' ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $active_periods[] = $row;
    }
}

// Get performance history
$performance_history = [];
$sql = "SELECT p.period_id, p.title, p.academic_year, p.semester,
        AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count
        FROM evaluation_periods p
        LEFT JOIN evaluations e ON p.period_id = e.period_id AND e.evaluatee_id = ?
        GROUP BY p.period_id
        ORDER BY p.start_date DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dean_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $performance_history[] = $row;
    }
}

// Get recent evaluations
$recent_evaluations = [];
$sql = "SELECT e.*, u.full_name as evaluator_name, u.role as evaluator_role,
        p.title as period_title, p.academic_year, p.semester
        FROM evaluations e
        JOIN users u ON e.evaluator_id = u.user_id
        JOIN evaluation_periods p ON e.period_id = p.period_id
        WHERE e.evaluatee_id = ?
        ORDER BY e.created_at DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dean_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_evaluations[] = $row;
    }
}

// Calculate overall average score
$overall_avg = 0;
$total_evaluations = 0;
foreach ($performance_history as $period) {
    if ($period['avg_score']) {
        $overall_avg += $period['avg_score'] * $period['evaluation_count'];
        $total_evaluations += $period['evaluation_count'];
    }
}
if ($total_evaluations > 0) {
    $overall_avg = $overall_avg / $total_evaluations;
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
                <a href="<?php echo $base_url; ?>college/deans.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Deans
                </a>
                <a href="<?php echo $base_url; ?>college/dean_evaluations.php?id=<?php echo $dean_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-chart-bar fa-sm text-white-50 mr-1"></i> View Evaluations
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

        <!-- Dean Info Card -->
        <div class="row">
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Dean Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($dean['profile_image'])): ?>
                                <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $dean['profile_image']; ?>" alt="<?php echo $dean['full_name']; ?>" class="img-profile rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; font-size: 3rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h4 class="text-center text-theme mb-3"><?php echo $dean['full_name']; ?></h4>
                        <p class="text-center mb-4">
                            <span class="badge badge-primary"><?php echo ucwords(str_replace('_', ' ', $dean['role'])); ?></span>
                            <span class="badge badge-<?php echo ($dean['status'] == 1) ? 'success' : 'danger'; ?>">
                                <?php echo ($dean['status'] == 1) ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
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
                                <i class="fas fa-university mr-2 text-theme"></i> <?php echo $dean['college_name']; ?>
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
                                        <i class="fas fa-calendar-check mr-2 text-theme"></i> Appointed: <?php echo date('M d, Y', strtotime($dean_profile['appointment_date'])); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($dean['position'])): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Position</h6>
                                <p><?php echo $dean['position']; ?></p>
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
            <div class="col-xl-8 col-lg-7">
                <!-- Performance Summary Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Performance Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-center mb-4">
                                    <div class="h1 mb-0 font-weight-bold text-theme"><?php echo number_format($overall_avg, 2); ?></div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Overall Score (out of 5.00)</div>
                                    <div class="mt-2">
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar bg-theme" role="progressbar" style="width: <?php echo ($overall_avg / 5) * 100; ?>%" aria-valuenow="<?php echo $overall_avg; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                        </div>
                                    </div>
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
                                <div class="mt-3">
                                    <div class="row">
                                        <div class="col-6 text-center">
                                            <div class="h4 mb-0 font-weight-bold text-theme"><?php echo count($performance_history); ?></div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Periods</div>
                                        </div>
                                        <div class="col-6 text-center">
                                            <div class="h4 mb-0 font-weight-bold text-theme"><?php echo $total_evaluations; ?></div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Evaluations</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php if (count($performance_history) > 0): ?>
                                    <div class="chart-container" style="position: relative; height:200px;">
                                        <canvas id="performanceHistoryChart"></canvas>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">No performance history available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Evaluations Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Recent Evaluations</h6>
                        <a href="<?php echo $base_url; ?>college/dean_evaluations.php?id=<?php echo $dean_id; ?>" class="btn btn-sm btn-theme">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_evaluations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
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
                                        <?php foreach ($recent_evaluations as $evaluation): ?>
                                            <tr>
                                                <td><?php echo $evaluation['period_title']; ?> (<?php echo $evaluation['academic_year']; ?>, Sem <?php echo $evaluation['semester']; ?>)</td>
                                                <td>
                                                    <?php echo $evaluation['evaluator_name']; ?><br>
                                                    <span class="badge badge-info"><?php echo ucwords(str_replace('_', ' ', $evaluation['evaluator_role'])); ?></span>
                                                </td>
                                                <td><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</td>
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
                                                <td><?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo $base_url; ?>college/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
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
        // Chart.js - Performance History
        var performanceHistoryCtx = document.getElementById('performanceHistoryChart');
        
        <?php if (count($performance_history) > 0): ?>
            // Prepare data for chart
            var periods = [];
            var scores = [];
            
            <?php foreach ($performance_history as $period): ?>
                periods.push('<?php echo $period['title']; ?>');
                scores.push(<?php echo $period['avg_score'] ? $period['avg_score'] : 0; ?>);
            <?php endforeach; ?>
            
            new Chart(performanceHistoryCtx, {
                type: 'line',
                data: {
                    labels: periods,
                    datasets: [{
                        label: 'Average Score',
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

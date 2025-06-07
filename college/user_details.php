<?php
/**
 * Samara University Academic Performance Evaluation System
 * College - User Details
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
$target_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if target_user_id is provided
if ($target_user_id <= 0) {
    redirect($base_url . 'college/dashboard.php');
}

// Get user information
$target_user = null;
$sql = "SELECT u.*, d.name as department_name, d.department_id, c.name as college_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN colleges c ON u.college_id = c.college_id
        WHERE u.user_id = ? AND u.college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $target_user_id, $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $target_user = $result->fetch_assoc();
} else {
    // Try without college restriction for admin view
    $sql = "SELECT u.*, d.name as department_name, d.department_id, c.name as college_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON u.college_id = c.college_id
            WHERE u.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $target_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $target_user = $result->fetch_assoc();
    } else {
        redirect($base_url . 'college/dashboard.php');
    }
}

// Get user's role-specific profile information
$role_profile = null;
$profile_table = '';

switch ($target_user['role']) {
    case 'dean':
        $profile_table = 'dean_profiles';
        break;
    case 'head_of_department':
        $profile_table = 'head_profiles';
        break;
    case 'college':
        $profile_table = 'college_profiles';
        break;
    case 'hrm':
        $profile_table = 'hrm_profiles';
        break;
    default:
        $profile_table = 'staff_profiles';
}

// Check if profile table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE '$profile_table'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Get profile data if table exists
if ($table_exists) {
    $sql = "SELECT * FROM $profile_table WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $target_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $role_profile = $result->fetch_assoc();
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

// Get recent evaluations
$recent_evaluations = [];
$sql = "SELECT e.*, 
        u1.full_name as evaluator_name, 
        u2.full_name as evaluatee_name,
        p.title as period_title,
        p.academic_year,
        p.semester
        FROM evaluations e 
        JOIN users u1 ON e.evaluator_id = u1.user_id 
        JOIN users u2 ON e.evaluatee_id = u2.user_id 
        JOIN evaluation_periods p ON e.period_id = p.period_id 
        WHERE (e.evaluator_id = ? OR e.evaluatee_id = ?)
        ORDER BY e.created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $target_user_id, $target_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_evaluations[] = $row;
    }
}

// Get performance history
$performance_history = [];
$sql = "SELECT p.period_id, p.title, p.academic_year, p.semester, AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as eval_count
        FROM evaluation_periods p
        LEFT JOIN evaluations e ON p.period_id = e.period_id AND e.evaluatee_id = ? AND e.status IN ('approved', 'reviewed')
        GROUP BY p.period_id
        ORDER BY p.start_date DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $performance_history[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">User Details</h1>
            <div>
                <a href="javascript:history.back()" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back
                </a>
                <?php if ($target_user['role'] == 'dean'): ?>
                    <a href="<?php echo $base_url; ?>college/dean_evaluations.php?id=<?php echo $target_user_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-chart-bar fa-sm text-white-50 mr-1"></i> View Evaluations
                    </a>
                <?php endif; ?>
                <?php if (count($active_periods) > 0 && in_array($target_user['role'], ['dean', 'head_of_department'])): ?>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-warning dropdown-toggle shadow-sm" type="button" id="evaluateDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-clipboard-check fa-sm text-white-50 mr-1"></i> Evaluate
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="evaluateDropdown">
                            <?php foreach ($active_periods as $period): ?>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>college/evaluation_form.php?evaluatee_id=<?php echo $target_user_id; ?>&period_id=<?php echo $period['period_id']; ?>">
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- User Profile Card -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">User Profile</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($target_user['profile_image']) && file_exists($GLOBALS['BASE_PATH'] . '/uploads/profiles/' . $target_user['profile_image'])): ?>
                                <img class="img-profile rounded-circle mb-3" src="<?php echo $base_url; ?>uploads/profiles/<?php echo $target_user['profile_image']; ?>" alt="Profile Image" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-theme text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px; font-size: 3rem;">
                                    <?php if ($target_user['role'] == 'dean'): ?>
                                        <i class="fas fa-user-tie"></i>
                                    <?php elseif ($target_user['role'] == 'head_of_department'): ?>
                                        <i class="fas fa-user-graduate"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <h4 class="font-weight-bold"><?php echo $target_user['full_name']; ?></h4>
                            <p class="text-muted mb-0"><?php echo ucwords(str_replace('_', ' ', $target_user['role'])); ?></p>
                            <?php if (!empty($target_user['position'])): ?>
                                <p class="text-primary"><?php echo $target_user['position']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="font-weight-bold">Contact Information</h6>
                            <div class="mb-2">
                                <i class="fas fa-envelope text-theme mr-2"></i> <?php echo $target_user['email']; ?>
                            </div>
                            <?php if (!empty($target_user['phone'])): ?>
                                <div class="mb-2">
                                    <i class="fas fa-phone text-theme mr-2"></i> <?php echo $target_user['phone']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="font-weight-bold">Affiliation</h6>
                            <div class="mb-2">
                                <i class="fas fa-university text-theme mr-2"></i> <?php echo $target_user['college_name']; ?>
                            </div>
                            <?php if (!empty($target_user['department_name'])): ?>
                                <div class="mb-2">
                                    <i class="fas fa-building text-theme mr-2"></i> <?php echo $target_user['department_name']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-0">
                            <h6 class="font-weight-bold">Account Status</h6>
                            <div class="mb-2">
                                <i class="fas fa-circle <?php echo ($target_user['status'] == 1) ? 'text-success' : 'text-danger'; ?> mr-2"></i>
                                <?php echo ($target_user['status'] == 1) ? 'Active' : 'Inactive'; ?>
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-calendar-alt text-theme mr-2"></i> Joined: <?php echo date('M d, Y', strtotime($target_user['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance and Evaluations -->
            <div class="col-xl-8 col-lg-7">
                <!-- Performance History Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Performance History</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($performance_history) > 0 && array_sum(array_column($performance_history, 'eval_count')) > 0): ?>
                            <div class="chart-area">
                                <canvas id="performanceHistoryChart"></canvas>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No performance history available.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Evaluations Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Recent Evaluations</h6>
                        <?php if (count($recent_evaluations) > 0): ?>
                            <a href="<?php echo $base_url; ?>college/evaluations.php?user_id=<?php echo $target_user_id; ?>" class="btn btn-sm btn-theme">
                                View All
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_evaluations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_evaluations as $evaluation): ?>
                                            <tr>
                                                <td><?php echo $evaluation['period_title']; ?> (<?php echo $evaluation['academic_year']; ?>, Semester <?php echo $evaluation['semester']; ?>)</td>
                                                <td>
                                                    <?php if ($evaluation['evaluator_id'] == $target_user_id): ?>
                                                        <span class="badge badge-info">Evaluator</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-primary">Evaluatee</span>
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
                            <p class="text-center">No evaluations found for this user.</p>
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
        
        <?php if (count($performance_history) > 0 && array_sum(array_column($performance_history, 'eval_count')) > 0): ?>
            var periods = [<?php echo implode(', ', array_map(function($period) { return "'" . $period['title'] . "'"; }, $performance_history)); ?>];
            var scores = [<?php echo implode(', ', array_map(function($period) { return $period['avg_score'] ? $period['avg_score'] : 0; }, $performance_history)); ?>];
            
            new Chart(performanceHistoryCtx, {
                type: 'line',
                data: {
                    labels: periods,
                    datasets: [{
                        label: 'Average Score',
                        data: scores,
                        backgroundColor: 'rgba(34, 174, 154, 0.1)',
                        borderColor: 'rgba(34, 174, 154, 1)',
                        pointBackgroundColor: 'rgba(34, 174, 154, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(34, 174, 154, 1)',
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

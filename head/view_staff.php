<?php
/**
 * Samara University Academic Performance Evaluation System
 * Head of Department - View Staff
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has head_of_department role
if (!is_logged_in() || !has_role('head_of_department')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if staff_id is provided
if ($staff_id <= 0) {
    redirect($base_url . 'head/staff.php');
}

// Get staff information
$staff = null;
$sql = "SELECT u.*, d.name as department_name, c.name as college_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        LEFT JOIN colleges c ON u.college_id = c.college_id 
        WHERE u.user_id = ? AND u.department_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $staff_id, $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $staff = $result->fetch_assoc();
} else {
    redirect($base_url . 'head/staff.php');
}

// Get evaluation statistics
$evaluation_stats = [];
$sql = "SELECT 
            COUNT(*) as total_evaluations,
            AVG(total_score) as avg_score,
            MAX(total_score) as max_score,
            MIN(total_score) as min_score
        FROM evaluations 
        WHERE evaluatee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $evaluation_stats = $result->fetch_assoc();
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
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_evaluations[] = $row;
    }
}

// Get performance history (scores by period)
$performance_history = [];
$sql = "SELECT p.period_id, p.title, p.academic_year, p.semester, 
        AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count 
        FROM evaluation_periods p 
        LEFT JOIN evaluations e ON p.period_id = e.period_id AND e.evaluatee_id = ? 
        GROUP BY p.period_id 
        ORDER BY p.start_date DESC 
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $performance_history[] = $row;
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
include_once BASE_PATH . '/includes/header_management.php';

// Include sidebar
include_once BASE_PATH . '/includes/sidebar.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Staff Profile</h1>
            <div>
                <a href="<?php echo $base_url; ?>head/staff.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Staff
                </a>
                <a href="<?php echo $base_url; ?>head/staff_evaluations.php?id=<?php echo $staff_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-chart-bar fa-sm text-white-50 mr-1"></i> View Evaluations
                </a>
                <?php if (count($active_periods) > 0): ?>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-theme dropdown-toggle shadow-sm" type="button" id="evaluateDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-clipboard-check fa-sm text-white-50 mr-1"></i> Evaluate
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="evaluateDropdown">
                            <?php foreach ($active_periods as $period): ?>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>head/evaluation_form.php?evaluatee_id=<?php echo $staff_id; ?>&period_id=<?php echo $period['period_id']; ?>">
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Staff Info Card -->
        <div class="row">
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Staff Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($staff['profile_image'])): ?>
                                <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $staff['profile_image']; ?>" alt="<?php echo $staff['full_name']; ?>" class="img-profile rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; font-size: 3rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <h4 class="mt-3 text-theme"><?php echo $staff['full_name']; ?></h4>
                            <p class="text-muted"><?php echo $staff['position'] ?? 'Staff Member'; ?></p>
                            <p>
                                <span class="badge badge-<?php echo ($staff['status'] == 1) ? 'success' : 'danger'; ?>">
                                    <?php echo ($staff['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                        </div>
                        
                        <hr>
                        
                        <div class="staff-details">
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-envelope mr-2"></i> Email:</div>
                                <div class="detail-value"><?php echo $staff['email']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-phone mr-2"></i> Phone:</div>
                                <div class="detail-value"><?php echo $staff['phone'] ?? 'N/A'; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-building mr-2"></i> Department:</div>
                                <div class="detail-value"><?php echo $staff['department_name']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-university mr-2"></i> College:</div>
                                <div class="detail-value"><?php echo $staff['college_name']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-calendar-alt mr-2"></i> Joined:</div>
                                <div class="detail-value"><?php echo isset($staff['created_at']) ? date('M d, Y', strtotime($staff['created_at'])) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-8 col-lg-7">
                <!-- Evaluation Statistics -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Evaluation Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Evaluations</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $evaluation_stats['total_evaluations'] ?? 0; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Average Score</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo isset($evaluation_stats['avg_score']) ? number_format($evaluation_stats['avg_score'], 2) : 'N/A'; ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-star fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Highest Score</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo isset($evaluation_stats['max_score']) ? number_format($evaluation_stats['max_score'], 2) : 'N/A'; ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-trophy fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="card border-left-warning shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Lowest Score</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo isset($evaluation_stats['min_score']) ? number_format($evaluation_stats['min_score'], 2) : 'N/A'; ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Performance History Chart -->
                        <div class="chart-area mt-4">
                            <canvas id="performanceHistoryChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Evaluations -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Recent Evaluations</h6>
                        <a href="<?php echo $base_url; ?>head/staff_evaluations.php?id=<?php echo $staff_id; ?>" class="btn btn-sm btn-theme">
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
                                                <td><?php echo $evaluation['period_title']; ?> (<?php echo $evaluation['academic_year']; ?>, Semester <?php echo $evaluation['semester']; ?>)</td>
                                                <td>
                                                    <?php echo $evaluation['evaluator_name']; ?>
                                                    <br>
                                                    <span class="badge badge-secondary">
                                                        <?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluator_role'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        if ($evaluation['status'] === 'completed') {
                                                            echo 'success';
                                                        } elseif ($evaluation['status'] === 'submitted') {
                                                            echo 'warning';
                                                        } elseif ($evaluation['status'] === 'in_progress') {
                                                            echo 'info';
                                                        } else {
                                                            echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $evaluation['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo $base_url; ?>head/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No evaluations found for this staff member.</p>
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
            var periods = [<?php echo implode(', ', array_map(function($period) { return "'" . $period['title'] . "'"; }, $performance_history)); ?>];
            var scores = [<?php echo implode(', ', array_map(function($period) { return $period['avg_score'] ? $period['avg_score'] : 0; }, $performance_history)); ?>];
            
            new Chart(performanceHistoryCtx, {
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
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: true
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
                                beginAtZero: true,
                                max: 5,
                                callback: function(value) {
                                    return value.toFixed(1);
                                }
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }]
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

<style>
    .staff-details {
        font-size: 0.9rem;
    }
    
    .detail-item {
        display: flex;
        margin-bottom: 10px;
    }
    
    .detail-label {
        font-weight: bold;
        width: 120px;
    }
    
    .detail-value {
        flex: 1;
    }
</style>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

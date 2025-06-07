<?php
/**
 * Samara University Academic Performance Evaluation System
 * College - Evaluations
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration file
require_once BASE_PATH . '/includes/config.php';

// Check if user is logged in and has college role
if (!is_logged_in() || !has_role('college')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$college_id = $_SESSION['college_id'];
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Check for success message in URL
if (isset($_GET['success'])) {
    $success_message = urldecode($_GET['success']);
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

// Get all departments in the college
$departments = [];
$sql = "SELECT * FROM departments WHERE college_id = ? ORDER BY name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Get evaluations conducted by the college
$evaluations = [];
$sql = "SELECT e.*, 
        u.full_name as evaluatee_name, 
        u.email as evaluatee_email,
        u.position as evaluatee_position,
        d.name as department_name,
        p.title as period_title,
        p.academic_year,
        p.semester
        FROM evaluations e 
        JOIN users u ON e.evaluatee_id = u.user_id 
        JOIN departments d ON u.department_id = d.department_id
        JOIN evaluation_periods p ON e.period_id = p.period_id 
        WHERE e.evaluator_id = ?";

// Add filters if provided
$params = [$user_id];
$types = "i";

if ($period_id > 0) {
    $sql .= " AND e.period_id = ?";
    $params[] = $period_id;
    $types .= "i";
}

if ($department_id > 0) {
    $sql .= " AND u.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

if (!empty($status)) {
    $sql .= " AND e.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY e.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
    }
}

// Get staff who haven't been evaluated in the current active period
$pending_evaluations = [];
if ($period_id > 0) {
    $sql = "SELECT u.*, d.name as department_name 
            FROM users u 
            JOIN departments d ON u.department_id = d.department_id 
            WHERE u.role = 'staff' AND d.college_id = ? AND u.status = 1";
    
    if ($department_id > 0) {
        $sql .= " AND u.department_id = ?";
    }
    
    $sql .= " AND NOT EXISTS (
                SELECT 1 FROM evaluations e 
                WHERE e.evaluatee_id = u.user_id 
                AND e.evaluator_id = ? 
                AND e.period_id = ?
            )
            ORDER BY d.name ASC, u.full_name ASC";
    
    if ($department_id > 0) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $college_id, $department_id, $user_id, $period_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $college_id, $user_id, $period_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pending_evaluations[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Evaluations</h1>
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
                    <div class="form-group mb-2 mr-2">
                        <label for="period_id" class="mr-2">Period:</label>
                        <select class="form-control" id="period_id" name="period_id">
                            <option value="0">All Periods</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-2 mr-2">
                        <label for="department_id" class="mr-2">Department:</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>" <?php echo ($department_id == $department['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo $department['name']; ?>
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
                    <?php if ($period_id > 0 || $department_id > 0 || !empty($status)): ?>
                        <a href="<?php echo $base_url; ?>college/evaluations.php" class="btn btn-secondary mb-2 ml-2">
                            <i class="fas fa-times mr-1"></i> Clear Filter
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($period_id > 0 && count($pending_evaluations) > 0): ?>
            <!-- Pending Evaluations Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-warning text-white">
                    <h6 class="m-0 font-weight-bold">Pending Evaluations</h6>
                </div>
                <div class="card-body">
                    <p>The following staff members have not been evaluated for the selected period:</p>
                    <div class="row">
                        <?php foreach ($pending_evaluations as $staff): ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-warning shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    Staff Member</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $staff['full_name']; ?></div>
                                                <div class="text-xs text-gray-600"><?php echo $staff['department_name']; ?> Department</div>
                                            </div>
                                            <div class="col-auto">
                                                <a href="<?php echo $base_url; ?>college/evaluation_form.php?evaluatee_id=<?php echo $staff['user_id']; ?>&period_id=<?php echo $period_id; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-clipboard-check"></i> Evaluate
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Evaluations Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">
                    <?php 
                    if ($period_id > 0) {
                        foreach ($periods as $period) {
                            if ($period['period_id'] == $period_id) {
                                echo 'Evaluations for ' . $period['title'] . ' (' . $period['academic_year'] . ', Semester ' . $period['semester'] . ')';
                                break;
                            }
                        }
                    } else {
                        echo 'All Evaluations';
                    }
                    
                    if ($department_id > 0) {
                        foreach ($departments as $department) {
                            if ($department['department_id'] == $department_id) {
                                echo ' - ' . $department['name'] . ' Department';
                                break;
                            }
                        }
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
                                    <th>Staff Member</th>
                                    <th>Department</th>
                                    <th>Period</th>
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
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3">
                                                    <div class="icon-circle bg-theme">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold"><?php echo $evaluation['evaluatee_name']; ?></div>
                                                    <div class="small text-gray-600"><?php echo $evaluation['evaluatee_position'] ?? 'Staff'; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $evaluation['department_name']; ?></td>
                                        <td>
                                            <?php echo $evaluation['period_title']; ?><br>
                                            <span class="small text-gray-600">
                                                <?php echo $evaluation['academic_year']; ?>, Semester <?php echo $evaluation['semester']; ?>
                                            </span>
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
                                            <a href="<?php echo $base_url; ?>college/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($evaluation['status'] === 'draft'): ?>
                                                <a href="<?php echo $base_url; ?>college/evaluation_form.php?evaluation_id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
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
                    <p class="text-center">No evaluations found for the selected filters.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluation Statistics -->
        <div class="row">
            <!-- Evaluation Status -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Evaluation Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie">
                            <canvas id="evaluationStatusChart"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <span class="mr-2">
                                <i class="fas fa-circle text-secondary"></i> Draft
                            </span>
                            <span class="mr-2">
                                <i class="fas fa-circle text-info"></i> Submitted
                            </span>
                            <span class="mr-2">
                                <i class="fas fa-circle text-warning"></i> Reviewed
                            </span>
                            <span class="mr-2">
                                <i class="fas fa-circle text-success"></i> Approved
                            </span>
                            <span class="mr-2">
                                <i class="fas fa-circle text-danger"></i> Rejected
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Distribution -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Evaluations by Department</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie">
                            <canvas id="departmentDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
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
        
        // Chart.js - Evaluation Status
        var statusCtx = document.getElementById('evaluationStatusChart');
        var departmentCtx = document.getElementById('departmentDistributionChart');
        
        // Count evaluations by status
        var draftCount = 0;
        var submittedCount = 0;
        var reviewedCount = 0;
        var approvedCount = 0;
        var rejectedCount = 0;
        
        <?php foreach ($evaluations as $evaluation): ?>
            <?php if ($evaluation['status'] === 'draft'): ?>
                draftCount++;
            <?php elseif ($evaluation['status'] === 'submitted'): ?>
                submittedCount++;
            <?php elseif ($evaluation['status'] === 'reviewed'): ?>
                reviewedCount++;
            <?php elseif ($evaluation['status'] === 'approved'): ?>
                approvedCount++;
            <?php elseif ($evaluation['status'] === 'rejected'): ?>
                rejectedCount++;
            <?php endif; ?>
        <?php endforeach; ?>
        
        // Create Evaluation Status Chart
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Draft', 'Submitted', 'Reviewed', 'Approved', 'Rejected'],
                    datasets: [{
                        data: [draftCount, submittedCount, reviewedCount, approvedCount, rejectedCount],
                        backgroundColor: ['#858796', '#36b9cc', '#f6c23e', '#1cc88a', '#e74a3b'],
                        hoverBackgroundColor: ['#717384', '#2c9faf', '#dda20a', '#17a673', '#be2617'],
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
                    cutoutPercentage: 80,
                },
            });
        }
        
        // Count evaluations by department
        var departmentCounts = {};
        var departmentNames = [];
        var departmentData = [];
        var departmentColors = [];
        
        <?php
        $dept_counts = [];
        foreach ($evaluations as $evaluation) {
            $dept_id = $evaluation['department_id'];
            $dept_name = $evaluation['department_name'];
            
            if (!isset($dept_counts[$dept_id])) {
                $dept_counts[$dept_id] = [
                    'name' => $dept_name,
                    'count' => 0
                ];
            }
            $dept_counts[$dept_id]['count']++;
        }
        
        foreach ($dept_counts as $dept_id => $dept) {
            echo "departmentNames.push('" . $dept['name'] . "');\n";
            echo "departmentData.push(" . $dept['count'] . ");\n";
            echo "departmentColors.push(getRandomColor());\n";
        }
        ?>
        
        // Function to generate random colors
        function getRandomColor() {
            var letters = '0123456789ABCDEF';
            var color = '#';
            for (var i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }
        
        // Create Department Distribution Chart
        if (departmentCtx && departmentNames.length > 0) {
            new Chart(departmentCtx, {
                type: 'doughnut',
                data: {
                    labels: departmentNames,
                    datasets: [{
                        data: departmentData,
                        backgroundColor: departmentColors,
                        hoverBackgroundColor: departmentColors,
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
                        display: true,
                        position: 'right'
                    },
                    cutoutPercentage: 80,
                },
            });
        }
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

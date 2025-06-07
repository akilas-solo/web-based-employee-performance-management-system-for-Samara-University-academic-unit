<?php
/**
 * Samara University Academic Performance Evaluation System
 * College - Staff Management
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
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
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

// Get staff members in the college
$staff = [];
$sql = "SELECT u.*, d.name as department_name, 
        (SELECT COUNT(*) FROM evaluations e WHERE e.evaluatee_id = u.user_id) as evaluation_count
        FROM users u 
        JOIN departments d ON u.department_id = d.department_id 
        WHERE d.college_id = ? AND u.role = 'staff'";

// Add department filter if selected
if ($department_id > 0) {
    $sql .= " AND u.department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $college_id, $department_id);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
}

$sql .= " ORDER BY d.name ASC, u.full_name ASC";
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
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

// Get pending evaluations (staff who haven't been evaluated in active periods)
$pending_evaluations = [];
if (count($active_periods) > 0) {
    foreach ($active_periods as $period) {
        $sql = "SELECT u.*, d.name as department_name 
                FROM users u 
                JOIN departments d ON u.department_id = d.department_id 
                WHERE u.role = 'staff' AND d.college_id = ? 
                AND NOT EXISTS (
                    SELECT 1 FROM evaluations e 
                    WHERE e.evaluatee_id = u.user_id 
                    AND e.evaluator_id = ? 
                    AND e.period_id = ?
                )";
        
        if ($department_id > 0) {
            $sql .= " AND u.department_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $college_id, $user_id, $period['period_id'], $department_id);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $college_id, $user_id, $period['period_id']);
        }
        
        $sql .= " ORDER BY d.name ASC, u.full_name ASC";
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pending_evaluations[$period['period_id']][] = $row;
            }
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
            <h1 class="h3 mb-0 text-gray-800">Staff Management</h1>
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

        <!-- College Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">College Information</h6>
            </div>
            <div class="card-body">
                <h4 class="text-theme"><?php echo $college_name; ?> College</h4>
                <p class="mb-0">Total Staff: <?php echo count($staff); ?></p>
                
                <?php if (count($active_periods) > 0): ?>
                    <div class="mt-3">
                        <h5>Active Evaluation Periods</h5>
                        <ul class="list-group">
                            <?php foreach ($active_periods as $period): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                    <span class="badge badge-primary badge-pill">
                                        <?php echo date('M d, Y', strtotime($period['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($period['end_date'])); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i> No active evaluation periods. Please contact the administrator.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Filter Staff</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
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
                    <button type="submit" class="btn btn-theme mb-2">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <?php if ($department_id > 0): ?>
                        <a href="<?php echo $base_url; ?>college/staff.php" class="btn btn-secondary mb-2 ml-2">
                            <i class="fas fa-times mr-1"></i> Clear Filter
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Pending Evaluations -->
        <?php if (!empty($pending_evaluations) && count($active_periods) > 0): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-warning text-white">
                    <h6 class="m-0 font-weight-bold">Pending Evaluations</h6>
                </div>
                <div class="card-body">
                    <div class="accordion" id="pendingEvaluationsAccordion">
                        <?php foreach ($active_periods as $period): ?>
                            <?php if (isset($pending_evaluations[$period['period_id']]) && !empty($pending_evaluations[$period['period_id']])): ?>
                                <div class="card">
                                    <div class="card-header" id="heading<?php echo $period['period_id']; ?>">
                                        <h2 class="mb-0">
                                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse<?php echo $period['period_id']; ?>" aria-expanded="true" aria-controls="collapse<?php echo $period['period_id']; ?>">
                                                <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                                <span class="badge badge-warning ml-2"><?php echo count($pending_evaluations[$period['period_id']]); ?> pending</span>
                                            </button>
                                        </h2>
                                    </div>

                                    <div id="collapse<?php echo $period['period_id']; ?>" class="collapse" aria-labelledby="heading<?php echo $period['period_id']; ?>" data-parent="#pendingEvaluationsAccordion">
                                        <div class="card-body">
                                            <div class="row">
                                                <?php foreach ($pending_evaluations[$period['period_id']] as $staff_member): ?>
                                                    <div class="col-xl-4 col-md-6 mb-4">
                                                        <div class="card border-left-warning shadow h-100 py-2">
                                                            <div class="card-body">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                                            Staff Member</div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $staff_member['full_name']; ?></div>
                                                                        <div class="text-xs text-gray-600"><?php echo $staff_member['department_name']; ?> Department</div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <a href="<?php echo $base_url; ?>college/evaluation_form.php?evaluatee_id=<?php echo $staff_member['user_id']; ?>&period_id=<?php echo $period['period_id']; ?>" class="btn btn-sm btn-warning">
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
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Staff Members Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Staff Members</h6>
            </div>
            <div class="card-body">
                <?php if (count($staff) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="staffTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Evaluations</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff as $member): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($member['profile_image'])): ?>
                                                    <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $member['profile_image']; ?>" alt="<?php echo $member['full_name']; ?>" class="img-profile rounded-circle mr-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2" style="width: 40px; height: 40px; font-size: 1.2rem;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <?php echo $member['full_name']; ?>
                                            </div>
                                        </td>
                                        <td><?php echo $member['department_name']; ?></td>
                                        <td><?php echo $member['email']; ?></td>
                                        <td><?php echo $member['position'] ?? 'N/A'; ?></td>
                                        <td><?php echo $member['evaluation_count']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($member['status'] == 1) ? 'success' : 'danger'; ?>">
                                                <?php echo ($member['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>college/view_staff.php?id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (count($active_periods) > 0): ?>
                                                <div class="dropdown d-inline">
                                                    <button class="btn btn-sm btn-theme dropdown-toggle" type="button" id="evaluateDropdown<?php echo $member['user_id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Evaluate">
                                                        <i class="fas fa-clipboard-check"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="evaluateDropdown<?php echo $member['user_id']; ?>">
                                                        <?php foreach ($active_periods as $period): ?>
                                                            <a class="dropdown-item" href="<?php echo $base_url; ?>college/evaluation_form.php?evaluatee_id=<?php echo $member['user_id']; ?>&period_id=<?php echo $period['period_id']; ?>">
                                                                <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <a href="<?php echo $base_url; ?>college/staff_evaluations.php?id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-primary" title="View Evaluations">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No staff members found in your college.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Staff Performance Overview -->
        <div class="row">
            <!-- Performance by Department -->
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Staff Performance by Department</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($staff) > 0): ?>
                            <div class="chart-bar">
                                <canvas id="departmentPerformanceChart"></canvas>
                            </div>
                            <div class="mt-4 text-center small">
                                <span class="mr-2">
                                    <i class="fas fa-circle text-primary"></i> Current Semester
                                </span>
                                <span class="mr-2">
                                    <i class="fas fa-circle text-success"></i> Previous Semester
                                </span>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No staff performance data available.</p>
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
    'assets/js/datatables/jquery.dataTables.min.js',
    'assets/js/datatables/dataTables.bootstrap4.min.js',
    'assets/js/chart.js/Chart.min.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        $('#staffTable').DataTable();
        
        // Chart.js - Department Performance
        var departmentPerformanceCtx = document.getElementById('departmentPerformanceChart');
        
        <?php if (count($staff) > 0): ?>
            // Get unique departments
            var departments = [];
            var departmentNames = [];
            
            <?php
            $dept_map = [];
            foreach ($staff as $member) {
                if (!isset($dept_map[$member['department_id']])) {
                    $dept_map[$member['department_id']] = [
                        'name' => $member['department_name'],
                        'count' => 0
                    ];
                }
                $dept_map[$member['department_id']]['count']++;
            }
            
            foreach ($dept_map as $dept_id => $dept) {
                echo "departments.push({id: " . $dept_id . ", name: '" . $dept['name'] . "', count: " . $dept['count'] . "});\n";
                echo "departmentNames.push('" . $dept['name'] . "');\n";
            }
            ?>
            
            // Prepare data for chart
            var currentScores = [];
            var previousScores = [];
            
            // Simulate scores for demonstration
            // In a real application, you would fetch actual evaluation scores
            departments.forEach(function(dept) {
                currentScores.push(<?php echo rand(30, 50) / 10; ?>);
                previousScores.push(<?php echo rand(30, 50) / 10; ?>);
            });
            
            // Create Department Performance Chart
            if (departmentPerformanceCtx) {
                new Chart(departmentPerformanceCtx, {
                    type: 'bar',
                    data: {
                        labels: departmentNames,
                        datasets: [
                            {
                                label: 'Current Semester',
                                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                                borderColor: 'rgba(78, 115, 223, 1)',
                                data: currentScores
                            },
                            {
                                label: 'Previous Semester',
                                backgroundColor: 'rgba(28, 200, 138, 0.8)',
                                borderColor: 'rgba(28, 200, 138, 1)',
                                data: previousScores
                            }
                        ]
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
                        }
                    }
                });
            }
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

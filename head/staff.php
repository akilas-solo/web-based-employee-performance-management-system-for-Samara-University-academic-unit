<?php
/**
 * Samara University Academic Performance Evaluation System
 * Head of Department - Staff Management
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
$department_id = $_SESSION['department_id'];
$department_name = '';

// Get department name
if ($department_id) {
    $sql = "SELECT name FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $department_name = $row['name'];
    }
}

// Get staff members in the department
$staff = [];
$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM evaluations e WHERE e.evaluatee_id = u.user_id) as evaluation_count
        FROM users u
        WHERE u.department_id = ? AND u.role = 'staff'
        ORDER BY u.full_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
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

        <!-- Department Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">Department Information</h6>
            </div>
            <div class="card-body">
                <h4 class="text-theme"><?php echo $department_name; ?> Department</h4>
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
                                        <td><?php echo $member['email']; ?></td>
                                        <td><?php echo $member['position'] ?? 'N/A'; ?></td>
                                        <td><?php echo $member['evaluation_count']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($member['status'] == 1) ? 'success' : 'danger'; ?>">
                                                <?php echo ($member['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>head/view_staff.php?id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (count($active_periods) > 0): ?>
                                                <div class="dropdown d-inline">
                                                    <button class="btn btn-sm btn-theme dropdown-toggle" type="button" id="evaluateDropdown<?php echo $member['user_id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Evaluate">
                                                        <i class="fas fa-clipboard-check"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="evaluateDropdown<?php echo $member['user_id']; ?>">
                                                        <?php foreach ($active_periods as $period): ?>
                                                            <a class="dropdown-item" href="<?php echo $base_url; ?>head/evaluation_form.php?evaluatee_id=<?php echo $member['user_id']; ?>&period_id=<?php echo $period['period_id']; ?>">
                                                                <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <a href="<?php echo $base_url; ?>head/staff_evaluations.php?id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-primary" title="View Evaluations">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No staff members found in your department.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Staff Performance Overview -->
        <div class="row">
            <!-- Performance by Staff -->
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Staff Performance Overview</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($staff) > 0): ?>
                            <div class="chart-bar">
                                <canvas id="staffPerformanceChart"></canvas>
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

        // Chart.js - Staff Performance
        var staffPerformanceCtx = document.getElementById('staffPerformanceChart');

        <?php if (count($staff) > 0): ?>
            // Prepare data for chart
            var staffNames = [];
            var currentScores = [];
            var previousScores = [];

            <?php foreach ($staff as $member): ?>
                staffNames.push('<?php echo $member['full_name']; ?>');

                // Simulate scores for demonstration
                // In a real application, you would fetch actual evaluation scores
                currentScores.push(<?php echo rand(30, 50) / 10; ?>);
                previousScores.push(<?php echo rand(30, 50) / 10; ?>);
            <?php endforeach; ?>

            // Create Staff Performance Chart
            if (staffPerformanceCtx) {
                new Chart(staffPerformanceCtx, {
                    type: 'bar',
                    data: {
                        labels: staffNames,
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

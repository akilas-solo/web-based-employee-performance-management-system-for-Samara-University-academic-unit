<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Departments
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has hrm role
if (!is_logged_in() || !has_role('hrm')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$college_id = isset($_GET['college_id']) ? (int)$_GET['college_id'] : 0;
$college_name = '';

// Get college name if college_id is provided
if ($college_id > 0) {
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

// Get all departments with statistics
$departments = [];
$sql = "SELECT d.*, c.name as college_name,
        (SELECT COUNT(*) FROM users u WHERE u.department_id = d.department_id) as user_count,
        (SELECT AVG(e.total_score) FROM evaluations e
         JOIN users u ON e.evaluatee_id = u.user_id
         WHERE u.department_id = d.department_id) as avg_score
        FROM departments d
        JOIN colleges c ON d.college_id = c.college_id";

if ($college_id > 0) {
    $sql .= " WHERE d.college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">
                <?php echo ($college_id > 0) ? 'Departments in ' . $college_name : 'All Departments'; ?>
            </h1>
            <?php if ($college_id > 0): ?>
                <a href="<?php echo $base_url; ?>hrm/colleges.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Colleges
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Departments Overview -->
        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Departments Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="departmentPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Departments Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">
                    <?php echo ($college_id > 0) ? 'Departments in ' . $college_name : 'All Departments'; ?>
                </h6>
            </div>
            <div class="card-body">
                <?php if (count($departments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="departmentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>College</th>
                                    <th>Staff</th>
                                    <th>Avg. Performance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departments as $department): ?>
                                    <tr>
                                        <td><?php echo $department['name']; ?></td>
                                        <td><?php echo $department['code']; ?></td>
                                        <td><?php echo $department['college_name']; ?></td>
                                        <td><?php echo $department['user_count']; ?></td>
                                        <td>
                                            <?php if ($department['avg_score']): ?>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-theme" role="progressbar"
                                                        style="width: <?php echo ($department['avg_score'] / 5) * 100; ?>%"
                                                        aria-valuenow="<?php echo $department['avg_score']; ?>"
                                                        aria-valuemin="0" aria-valuemax="5">
                                                        <?php echo number_format($department['avg_score'], 2); ?>/5
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No data</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>hrm/department_details.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>hrm/staff.php?department_id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-primary" title="View Staff">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>hrm/department_report.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-success" title="Generate Report">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No departments found.</p>
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
        // Initialize DataTable
        $('#departmentsTable').DataTable();

        // Chart.js - Department Performance
        var departmentPerformanceCtx = document.getElementById('departmentPerformanceChart');

        // Prepare data for charts
        var departmentNames = [];
        var avgScores = [];
        var backgroundColors = [];

        <?php foreach ($departments as $index => $department): ?>
            departmentNames.push('<?php echo $department['name'] . ' (' . $department['college_name'] . ')'; ?>');
            avgScores.push(<?php echo $department['avg_score'] ? $department['avg_score'] : 0; ?>);
            backgroundColors.push(getRandomColor());
        <?php endforeach; ?>

        // Create chart
        if (departmentPerformanceCtx) {
            new Chart(departmentPerformanceCtx, {
                type: 'bar',
                data: {
                    labels: departmentNames,
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
                    }
                }
            });
        }

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
include_once BASE_PATH . '/includes/footer_management.php';
?>

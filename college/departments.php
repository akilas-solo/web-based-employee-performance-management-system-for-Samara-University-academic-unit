<?php
/**
 * Samara University Academic Performance Evaluation System
 * College - Departments
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

// Get all departments in the college with statistics
$departments = [];
$sql = "SELECT d.*,
        (SELECT COUNT(*) FROM users u WHERE u.department_id = d.department_id) as user_count,
        (SELECT COUNT(*) FROM users u WHERE u.department_id = d.department_id AND u.role = 'head_of_department') as head_count,
        (SELECT AVG(e.total_score) FROM evaluations e
         JOIN users u ON e.evaluatee_id = u.user_id
         WHERE u.department_id = d.department_id) as avg_score
        FROM departments d
        WHERE d.college_id = ?
        ORDER BY d.name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Departments in <?php echo $college_name; ?></h1>
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
                <h6 class="m-0 font-weight-bold text-theme">All Departments</h6>
            </div>
            <div class="card-body">
                <?php if (count($departments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="departmentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Staff</th>
                                    <th>Head</th>
                                    <th>Avg. Performance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departments as $department): ?>
                                    <tr>
                                        <td><?php echo $department['name']; ?></td>
                                        <td><?php echo $department['code']; ?></td>
                                        <td><?php echo $department['user_count']; ?></td>
                                        <td>
                                            <?php if ($department['head_count'] > 0): ?>
                                                <span class="badge badge-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">No</span>
                                            <?php endif; ?>
                                        </td>
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
                                            <a href="<?php echo $base_url; ?>college/department_details.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>college/staff.php?department_id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-primary" title="View Staff">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>college/department_report.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-success" title="Generate Report">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No departments found in your college.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Department Heads -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Department Heads</h6>
            </div>
            <div class="card-body">
                <?php
                // Get department heads
                $heads = [];
                $sql = "SELECT u.*, d.name as department_name, d.code as department_code
                        FROM users u
                        JOIN departments d ON u.department_id = d.department_id
                        WHERE u.role = 'head_of_department' AND d.college_id = ?
                        ORDER BY d.name ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $college_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $heads[] = $row;
                    }
                }
                ?>

                <?php if (count($heads) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="headsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($heads as $head): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($head['profile_image'])): ?>
                                                <img class="img-profile rounded-circle mr-2" src="<?php echo $base_url; ?>uploads/profile_images/<?php echo $head['profile_image']; ?>" alt="Profile" style="width: 30px; height: 30px;">
                                            <?php else: ?>
                                                <img class="img-profile rounded-circle mr-2" src="<?php echo $base_url; ?>assets/images/default-profile.png" alt="Default Profile" style="width: 30px; height: 30px;">
                                            <?php endif; ?>
                                            <?php echo $head['full_name']; ?>
                                        </td>
                                        <td><?php echo $head['department_name']; ?> (<?php echo $head['department_code']; ?>)</td>
                                        <td><?php echo $head['email']; ?></td>
                                        <td><?php echo !empty($head['phone']) ? $head['phone'] : 'N/A'; ?></td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>college/user_details.php?id=<?php echo $head['user_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php
                                            // Check if there's an active evaluation period
                                            $active_periods = [];
                                            $sql = "SELECT * FROM evaluation_periods WHERE status = 'active' ORDER BY start_date DESC";
                                            $result = $conn->query($sql);
                                            if ($result && $result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $active_periods[] = $row;
                                                }
                                            }
                                            ?>
                                            <?php if (count($active_periods) > 0): ?>
                                                <div class="dropdown d-inline">
                                                    <button class="btn btn-sm btn-warning dropdown-toggle" type="button" id="evaluateDropdown<?php echo $head['user_id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-clipboard-check"></i> Evaluate
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="evaluateDropdown<?php echo $head['user_id']; ?>">
                                                        <?php foreach ($active_periods as $period): ?>
                                                            <a class="dropdown-item" href="<?php echo $base_url; ?>college/evaluation_form.php?evaluatee_id=<?php echo $head['user_id']; ?>&period_id=<?php echo $period['period_id']; ?>">
                                                                <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
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
                    <p class="text-center">No department heads found in your college.</p>
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
        $('#departmentsTable').DataTable();
        $('#headsTable').DataTable();

        // Chart.js - Department Performance
        var departmentPerformanceCtx = document.getElementById('departmentPerformanceChart');

        // Prepare data for charts
        var departmentNames = [];
        var avgScores = [];
        var backgroundColors = [];

        <?php foreach ($departments as $index => $department): ?>
            departmentNames.push('<?php echo $department['name']; ?>');
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
include_once $GLOBALS['BASE_PATH'] . '/includes/footer_management.php';
?>

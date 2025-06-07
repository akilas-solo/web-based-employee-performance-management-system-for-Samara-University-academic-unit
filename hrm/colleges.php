<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Colleges
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

// Get all colleges with statistics
$colleges = [];
$sql = "SELECT c.*,
        (SELECT COUNT(*) FROM departments d WHERE d.college_id = c.college_id) as department_count,
        (SELECT COUNT(*) FROM users u WHERE u.college_id = c.college_id) as user_count,
        (SELECT AVG(e.total_score) FROM evaluations e
         JOIN users u ON e.evaluatee_id = u.user_id
         WHERE u.college_id = c.college_id) as avg_score
        FROM colleges c
        ORDER BY c.name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Colleges</h1>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Colleges Overview -->
        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Colleges Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="collegePerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colleges Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">All Colleges</h6>
            </div>
            <div class="card-body">
                <?php if (count($colleges) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="collegesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Departments</th>
                                    <th>Staff</th>
                                    <th>Avg. Performance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($colleges as $college): ?>
                                    <tr>
                                        <td><?php echo $college['name']; ?></td>
                                        <td><?php echo $college['code']; ?></td>
                                        <td><?php echo $college['department_count']; ?></td>
                                        <td><?php echo $college['user_count']; ?></td>
                                        <td>
                                            <?php if ($college['avg_score']): ?>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-theme" role="progressbar"
                                                        style="width: <?php echo ($college['avg_score'] / 5) * 100; ?>%"
                                                        aria-valuenow="<?php echo $college['avg_score']; ?>"
                                                        aria-valuemin="0" aria-valuemax="5">
                                                        <?php echo number_format($college['avg_score'], 2); ?>/5
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No data</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>hrm/college_details.php?id=<?php echo $college['college_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>hrm/departments.php?college_id=<?php echo $college['college_id']; ?>" class="btn btn-sm btn-primary" title="View Departments">
                                                <i class="fas fa-building"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>hrm/college_report.php?id=<?php echo $college['college_id']; ?>" class="btn btn-sm btn-success" title="Generate Report">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No colleges found.</p>
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
        $('#collegesTable').DataTable();

        // Chart.js - College Performance
        var collegePerformanceCtx = document.getElementById('collegePerformanceChart');

        // Prepare data for charts
        var collegeNames = [];
        var avgScores = [];
        var backgroundColors = [];

        <?php foreach ($colleges as $index => $college): ?>
            collegeNames.push('<?php echo $college['name']; ?>');
            avgScores.push(<?php echo $college['avg_score'] ? $college['avg_score'] : 0; ?>);
            backgroundColors.push(getRandomColor());
        <?php endforeach; ?>

        // Create chart
        if (collegePerformanceCtx) {
            new Chart(collegePerformanceCtx, {
                type: 'bar',
                data: {
                    labels: collegeNames,
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

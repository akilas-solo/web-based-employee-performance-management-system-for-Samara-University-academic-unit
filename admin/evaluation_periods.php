<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Evaluation Periods Management
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration file
require_once BASE_PATH . '/includes/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';

// Handle period deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $period_id = (int)$_GET['delete'];
    
    // Check if period exists
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Check if period has evaluations
        $sql = "SELECT COUNT(*) as count FROM evaluations WHERE period_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $period_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error_message = "Cannot delete evaluation period. There are evaluations associated with this period.";
        } else {
            // Delete period
            $sql = "DELETE FROM evaluation_periods WHERE period_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $period_id);
            
            if ($stmt->execute()) {
                $success_message = "Evaluation period deleted successfully.";
            } else {
                $error_message = "Error deleting evaluation period: " . $conn->error;
            }
        }
    } else {
        $error_message = "Evaluation period not found.";
    }
}

// Handle period activation/deactivation
if (isset($_GET['toggle_status']) && !empty($_GET['toggle_status'])) {
    $period_id = (int)$_GET['toggle_status'];
    
    // Get current status
    $sql = "SELECT status FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $new_status = ($row['status'] == 'active') ? 'inactive' : 'active';
        
        // Update status
        $sql = "UPDATE evaluation_periods SET status = ? WHERE period_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $period_id);
        
        if ($stmt->execute()) {
            $status_text = ($new_status == 'active') ? 'activated' : 'deactivated';
            $success_message = "Evaluation period {$status_text} successfully.";
        } else {
            $error_message = "Error updating evaluation period status: " . $conn->error;
        }
    } else {
        $error_message = "Evaluation period not found.";
    }
}

// Get all evaluation periods
$periods = [];
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM evaluations e WHERE e.period_id = p.period_id) as evaluation_count
        FROM evaluation_periods p 
        ORDER BY p.academic_year DESC, p.semester DESC, p.start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Evaluation Periods Management</h1>
            <a href="<?php echo $base_url; ?>admin/add_period.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Add New Period
            </a>
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

        <!-- Evaluation Periods Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">All Evaluation Periods</h6>
            </div>
            <div class="card-body">
                <?php if (count($periods) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="periodsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Academic Year</th>
                                    <th>Semester</th>
                                    <th>Period</th>
                                    <th>Evaluations</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periods as $period): ?>
                                    <tr>
                                        <td><?php echo $period['title']; ?></td>
                                        <td><?php echo $period['academic_year']; ?></td>
                                        <td><?php echo $period['semester']; ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($period['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($period['end_date'])); ?>
                                        </td>
                                        <td><?php echo $period['evaluation_count']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($period['status'] == 'active') ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($period['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>admin/view_period.php?id=<?php echo $period['period_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/edit_period.php?id=<?php echo $period['period_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/evaluation_periods.php?toggle_status=<?php echo $period['period_id']; ?>" class="btn btn-sm btn-<?php echo ($period['status'] == 'active') ? 'warning' : 'success'; ?>" title="<?php echo ($period['status'] == 'active') ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas fa-<?php echo ($period['status'] == 'active') ? 'ban' : 'check'; ?>"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/evaluation_periods.php?delete=<?php echo $period['period_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this evaluation period? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No evaluation periods found. <a href="<?php echo $base_url; ?>admin/add_period.php">Add an evaluation period</a> to get started.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluation Period Statistics -->
        <div class="row">
            <!-- Evaluations by Period -->
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Evaluations by Period</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="evaluationsByPeriodChart"></canvas>
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
        $('#periodsTable').DataTable();
        
        // Chart.js - Evaluations by Period
        var evaluationsByPeriodCtx = document.getElementById('evaluationsByPeriodChart');
        
        // Prepare data for charts
        var periodLabels = [];
        var evaluationCounts = [];
        var backgroundColors = [];
        
        <?php foreach ($periods as $index => $period): ?>
            periodLabels.push('<?php echo $period['title'] . ' (' . $period['academic_year'] . ', Semester ' . $period['semester'] . ')'; ?>');
            evaluationCounts.push(<?php echo $period['evaluation_count']; ?>);
            backgroundColors.push(getRandomColor());
        <?php endforeach; ?>
        
        // Function to generate random colors
        function getRandomColor() {
            var letters = '0123456789ABCDEF';
            var color = '#';
            for (var i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }
        
        // Create Evaluations by Period Chart
        if (evaluationsByPeriodCtx) {
            new Chart(evaluationsByPeriodCtx, {
                type: 'bar',
                data: {
                    labels: periodLabels,
                    datasets: [{
                        label: 'Number of Evaluations',
                        data: evaluationCounts,
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
                                stepSize: 1
                            }
                        }]
                    }
                }
            });
        }
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

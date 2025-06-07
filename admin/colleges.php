<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - College Management
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

// Handle college deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $college_id = (int)$_GET['delete'];
    
    // Check if college exists
    $sql = "SELECT * FROM colleges WHERE college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Check if college has departments
        $sql = "SELECT COUNT(*) as count FROM departments WHERE college_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $college_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error_message = "Cannot delete college. Please delete all departments associated with this college first.";
        } else {
            // Delete college
            $sql = "DELETE FROM colleges WHERE college_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $college_id);
            
            if ($stmt->execute()) {
                $success_message = "College deleted successfully.";
            } else {
                $error_message = "Error deleting college: " . $conn->error;
            }
        }
    } else {
        $error_message = "College not found.";
    }
}

// Get all colleges
$colleges = [];
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM departments d WHERE d.college_id = c.college_id) as department_count,
        (SELECT COUNT(*) FROM users u WHERE u.college_id = c.college_id) as user_count
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
            <h1 class="h3 mb-0 text-gray-800">College Management</h1>
            <a href="<?php echo $base_url; ?>admin/add_college.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Add New College
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

        <!-- Colleges Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">All Colleges</h6>
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
                                    <th>Users</th>
                                    <th>Created</th>
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
                                        <td><?php echo date('M d, Y', strtotime($college['created_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>admin/view_college.php?id=<?php echo $college['college_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/edit_college.php?id=<?php echo $college['college_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/colleges.php?delete=<?php echo $college['college_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this college? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No colleges found. <a href="<?php echo $base_url; ?>admin/add_college.php">Add a college</a> to get started.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- College Statistics -->
        <div class="row">
            <!-- Departments by College -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Departments by College</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="departmentsByCollegeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users by College -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Users by College</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="usersByCollegeChart"></canvas>
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
        $('#collegesTable').DataTable();
        
        // Chart.js - Departments by College
        var departmentsCtx = document.getElementById('departmentsByCollegeChart');
        var usersByCollegeCtx = document.getElementById('usersByCollegeChart');
        
        // Prepare data for charts
        var collegeNames = [];
        var departmentCounts = [];
        var userCounts = [];
        var backgroundColors = [];
        
        <?php foreach ($colleges as $index => $college): ?>
            collegeNames.push('<?php echo $college['name']; ?>');
            departmentCounts.push(<?php echo $college['department_count']; ?>);
            userCounts.push(<?php echo $college['user_count']; ?>);
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
        
        // Create Departments by College Chart
        if (departmentsCtx) {
            new Chart(departmentsCtx, {
                type: 'bar',
                data: {
                    labels: collegeNames,
                    datasets: [{
                        label: 'Number of Departments',
                        data: departmentCounts,
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
        
        // Create Users by College Chart
        if (usersByCollegeCtx) {
            new Chart(usersByCollegeCtx, {
                type: 'bar',
                data: {
                    labels: collegeNames,
                    datasets: [{
                        label: 'Number of Users',
                        data: userCounts,
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

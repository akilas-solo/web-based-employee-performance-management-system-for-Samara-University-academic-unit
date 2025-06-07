<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Department Management
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
$college_id = isset($_GET['college_id']) ? (int)$_GET['college_id'] : 0;

// Handle department deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $department_id = (int)$_GET['delete'];
    
    // Check if department exists
    $sql = "SELECT * FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Check if department has users
        $sql = "SELECT COUNT(*) as count FROM users WHERE department_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error_message = "Cannot delete department. Please reassign all users associated with this department first.";
        } else {
            // Delete department
            $sql = "DELETE FROM departments WHERE department_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $department_id);
            
            if ($stmt->execute()) {
                $success_message = "Department deleted successfully.";
            } else {
                $error_message = "Error deleting department: " . $conn->error;
            }
        }
    } else {
        $error_message = "Department not found.";
    }
}

// Get all colleges for filter
$colleges = [];
$sql = "SELECT * FROM colleges ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

// Get departments with college names
$departments = [];
$sql = "SELECT d.*, c.name as college_name, 
        (SELECT COUNT(*) FROM users u WHERE u.department_id = d.department_id) as user_count
        FROM departments d 
        JOIN colleges c ON d.college_id = c.college_id";

// Add college filter if selected
if ($college_id > 0) {
    $sql .= " WHERE d.college_id = " . $college_id;
}

$sql .= " ORDER BY c.name ASC, d.name ASC";
$result = $conn->query($sql);
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
            <h1 class="h3 mb-0 text-gray-800">Department Management</h1>
            <a href="<?php echo $base_url; ?>admin/add_department.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Add New Department
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

        <!-- Filter Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filter Departments</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                    <div class="form-group mb-2 mr-2">
                        <label for="college_id" class="mr-2">College:</label>
                        <select class="form-control" id="college_id" name="college_id">
                            <option value="0">All Colleges</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo $college['college_id']; ?>" <?php echo ($college_id == $college['college_id']) ? 'selected' : ''; ?>>
                                    <?php echo $college['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mb-2">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <?php if ($college_id > 0): ?>
                        <a href="<?php echo $base_url; ?>admin/departments.php" class="btn btn-secondary mb-2 ml-2">
                            <i class="fas fa-times mr-1"></i> Clear Filter
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Departments Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <?php echo ($college_id > 0) ? 'Departments in ' . $departments[0]['college_name'] : 'All Departments'; ?>
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
                                    <th>Users</th>
                                    <th>Created</th>
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
                                        <td><?php echo date('M d, Y', strtotime($department['created_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>admin/view_department.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/edit_department.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/departments.php?delete=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this department? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No departments found. <a href="<?php echo $base_url; ?>admin/add_department.php">Add a department</a> to get started.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Department Statistics -->
        <div class="row">
            <!-- Users by Department -->
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Users by Department</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="usersByDepartmentChart"></canvas>
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
        $('#departmentsTable').DataTable();
        
        // Chart.js - Users by Department
        var usersByDepartmentCtx = document.getElementById('usersByDepartmentChart');
        
        // Prepare data for charts
        var departmentNames = [];
        var userCounts = [];
        var backgroundColors = [];
        
        <?php foreach ($departments as $index => $department): ?>
            departmentNames.push('<?php echo $department['name'] . ' (' . $department['college_name'] . ')'; ?>');
            userCounts.push(<?php echo $department['user_count']; ?>);
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
        
        // Create Users by Department Chart
        if (usersByDepartmentCtx) {
            new Chart(usersByDepartmentCtx, {
                type: 'horizontalBar',
                data: {
                    labels: departmentNames,
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
                        xAxes: [{
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

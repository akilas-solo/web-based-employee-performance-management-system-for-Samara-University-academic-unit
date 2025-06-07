<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Reports
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
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$college_id = isset($_GET['college_id']) ? (int)$_GET['college_id'] : 0;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$role = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : 'summary';

// Get all evaluation periods
$periods = [];
$sql = "SELECT * FROM evaluation_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row;
    }
}

// Get all colleges
$colleges = [];
$sql = "SELECT * FROM colleges ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

// Get departments based on selected college
$departments = [];
if ($college_id > 0) {
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
}

// Get report data
$report_data = [];
$college_performance = [];
$department_performance = [];
$role_performance = [];
$individual_performance = [];

if ($period_id > 0) {
    // Get period details
    $period_details = null;
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $period_details = $result->fetch_assoc();
    }
    
    // Get college performance
    $sql = "SELECT c.college_id, c.name, AVG(e.total_score) as avg_score, COUNT(DISTINCT e.evaluation_id) as total_evaluations 
            FROM colleges c 
            LEFT JOIN users u ON c.college_id = u.college_id 
            LEFT JOIN evaluations e ON u.user_id = e.evaluatee_id 
            WHERE e.period_id = ? 
            GROUP BY c.college_id 
            ORDER BY avg_score DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $college_performance[] = $row;
        }
    }
    
    // Get department performance if college is selected
    if ($college_id > 0) {
        $sql = "SELECT d.department_id, d.name, AVG(e.total_score) as avg_score, COUNT(DISTINCT e.evaluation_id) as total_evaluations 
                FROM departments d 
                LEFT JOIN users u ON d.department_id = u.department_id 
                LEFT JOIN evaluations e ON u.user_id = e.evaluatee_id 
                WHERE e.period_id = ? AND d.college_id = ? 
                GROUP BY d.department_id 
                ORDER BY avg_score DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $period_id, $college_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $department_performance[] = $row;
            }
        }
    }
    
    // Get role performance
    $sql = "SELECT u.role, AVG(e.total_score) as avg_score, COUNT(DISTINCT e.evaluation_id) as total_evaluations 
            FROM users u 
            JOIN evaluations e ON u.user_id = e.evaluatee_id 
            WHERE e.period_id = ? 
            GROUP BY u.role 
            ORDER BY avg_score DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $role_performance[] = $row;
        }
    }
    
    // Get individual performance based on filters
    $sql = "SELECT u.user_id, u.full_name, u.role, d.name as department_name, c.name as college_name, 
            AVG(e.total_score) as avg_score, COUNT(DISTINCT e.evaluation_id) as total_evaluations 
            FROM users u 
            LEFT JOIN departments d ON u.department_id = d.department_id 
            LEFT JOIN colleges c ON u.college_id = c.college_id 
            JOIN evaluations e ON u.user_id = e.evaluatee_id 
            WHERE e.period_id = ?";
    
    $params = [$period_id];
    $types = "i";
    
    if ($college_id > 0) {
        $sql .= " AND u.college_id = ?";
        $params[] = $college_id;
        $types .= "i";
    }
    
    if ($department_id > 0) {
        $sql .= " AND u.department_id = ?";
        $params[] = $department_id;
        $types .= "i";
    }
    
    if (!empty($role)) {
        $sql .= " AND u.role = ?";
        $params[] = $role;
        $types .= "s";
    }
    
    $sql .= " GROUP BY u.user_id ORDER BY avg_score DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $individual_performance[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Performance Reports</h1>
            <?php if ($period_id > 0 && !empty($individual_performance)): ?>
                <a href="<?php echo $base_url; ?>admin/export_report.php?period_id=<?php echo $period_id; ?>&college_id=<?php echo $college_id; ?>&department_id=<?php echo $department_id; ?>&role=<?php echo $role; ?>&report_type=<?php echo $report_type; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-download fa-sm text-white-50 mr-1"></i> Export Report
                </a>
            <?php endif; ?>
        </div>

        <!-- Report Filters Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Report Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="reportForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="period_id">Evaluation Period <span class="text-danger">*</span></label>
                                <select class="form-control" id="period_id" name="period_id" required>
                                    <option value="">Select Period</option>
                                    <?php foreach ($periods as $period): ?>
                                        <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                            <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="college_id">College</label>
                                <select class="form-control" id="college_id" name="college_id">
                                    <option value="">All Colleges</option>
                                    <?php foreach ($colleges as $college): ?>
                                        <option value="<?php echo $college['college_id']; ?>" <?php echo ($college_id == $college['college_id']) ? 'selected' : ''; ?>>
                                            <?php echo $college['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="department_id">Department</label>
                                <select class="form-control" id="department_id" name="department_id" <?php echo (empty($departments)) ? 'disabled' : ''; ?>>
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['department_id']; ?>" <?php echo ($department_id == $department['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo $department['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select class="form-control" id="role" name="role">
                                    <option value="">All Roles</option>
                                    <option value="head_of_department" <?php echo ($role === 'head_of_department') ? 'selected' : ''; ?>>Head of Department</option>
                                    <option value="dean" <?php echo ($role === 'dean') ? 'selected' : ''; ?>>Dean</option>
                                    <option value="college" <?php echo ($role === 'college') ? 'selected' : ''; ?>>College</option>
                                    <option value="hrm" <?php echo ($role === 'hrm') ? 'selected' : ''; ?>>HRM</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="report_type">Report Type</label>
                                <select class="form-control" id="report_type" name="report_type">
                                    <option value="summary" <?php echo ($report_type === 'summary') ? 'selected' : ''; ?>>Summary</option>
                                    <option value="detailed" <?php echo ($report_type === 'detailed') ? 'selected' : ''; ?>>Detailed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter mr-1"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($period_id > 0): ?>
            <?php if ($report_type === 'summary'): ?>
                <!-- Summary Report -->
                <div class="row">
                    <!-- College Performance -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">College Performance</h6>
                            </div>
                            <div class="card-body">
                                <?php if (count($college_performance) > 0): ?>
                                    <?php foreach ($college_performance as $college): ?>
                                        <h4 class="small font-weight-bold">
                                            <?php echo $college['name']; ?>
                                            <span class="float-right"><?php echo number_format($college['avg_score'], 2); ?>/5.00</span>
                                        </h4>
                                        <div class="progress mb-4">
                                            <div class="progress-bar bg-<?php 
                                                $score_percent = ($college['avg_score'] / 5) * 100;
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
                                                aria-valuenow="<?php echo $college['avg_score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center">No college performance data available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Role Performance -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Role Performance</h6>
                            </div>
                            <div class="card-body">
                                <?php if (count($role_performance) > 0): ?>
                                    <?php foreach ($role_performance as $role_data): ?>
                                        <h4 class="small font-weight-bold">
                                            <?php echo ucwords(str_replace('_', ' ', $role_data['role'])); ?>
                                            <span class="float-right"><?php echo number_format($role_data['avg_score'], 2); ?>/5.00</span>
                                        </h4>
                                        <div class="progress mb-4">
                                            <div class="progress-bar bg-<?php 
                                                $score_percent = ($role_data['avg_score'] / 5) * 100;
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
                                                aria-valuenow="<?php echo $role_data['avg_score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center">No role performance data available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($college_id > 0 && count($department_performance) > 0): ?>
                    <!-- Department Performance -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Department Performance</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($department_performance as $department): ?>
                                    <div class="col-lg-6">
                                        <h4 class="small font-weight-bold">
                                            <?php echo $department['name']; ?>
                                            <span class="float-right"><?php echo number_format($department['avg_score'], 2); ?>/5.00</span>
                                        </h4>
                                        <div class="progress mb-4">
                                            <div class="progress-bar bg-<?php 
                                                $score_percent = ($department['avg_score'] / 5) * 100;
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
                                                aria-valuenow="<?php echo $department['avg_score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Individual Performance -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Individual Performance</h6>
                    <?php if (!empty($individual_performance)): ?>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                <div class="dropdown-header">Export Options:</div>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>admin/export_report.php?period_id=<?php echo $period_id; ?>&college_id=<?php echo $college_id; ?>&department_id=<?php echo $department_id; ?>&role=<?php echo $role; ?>&report_type=<?php echo $report_type; ?>&format=pdf">
                                    <i class="fas fa-file-pdf fa-sm fa-fw mr-2 text-gray-400"></i> Export as PDF
                                </a>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>admin/export_report.php?period_id=<?php echo $period_id; ?>&college_id=<?php echo $college_id; ?>&department_id=<?php echo $department_id; ?>&role=<?php echo $role; ?>&report_type=<?php echo $report_type; ?>&format=excel">
                                    <i class="fas fa-file-excel fa-sm fa-fw mr-2 text-gray-400"></i> Export as Excel
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (count($individual_performance) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="performanceTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Department/College</th>
                                        <th>Average Score</th>
                                        <th>Rating</th>
                                        <th>Total Evaluations</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($individual_performance as $individual): ?>
                                        <tr>
                                            <td><?php echo $individual['full_name']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    switch ($individual['role']) {
                                                        case 'head_of_department':
                                                            echo 'info';
                                                            break;
                                                        case 'dean':
                                                            echo 'purple';
                                                            break;
                                                        case 'college':
                                                            echo 'success';
                                                            break;
                                                        case 'hrm':
                                                            echo 'warning';
                                                            break;
                                                        default:
                                                            echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $individual['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($individual['department_name'])) {
                                                    echo $individual['department_name'];
                                                } elseif (!empty($individual['college_name'])) {
                                                    echo $individual['college_name'];
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo number_format($individual['avg_score'], 2); ?>/5.00</td>
                                            <td>
                                                <?php 
                                                $score = $individual['avg_score'];
                                                if ($score >= 4.5) {
                                                    echo '<span class="badge badge-success">Excellent</span>';
                                                } elseif ($score >= 3.5) {
                                                    echo '<span class="badge badge-primary">Very Good</span>';
                                                } elseif ($score >= 2.5) {
                                                    echo '<span class="badge badge-info">Good</span>';
                                                } elseif ($score >= 1.5) {
                                                    echo '<span class="badge badge-warning">Fair</span>';
                                                } else {
                                                    echo '<span class="badge badge-danger">Poor</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo $individual['total_evaluations']; ?></td>
                                            <td>
                                                <a href="<?php echo $base_url; ?>admin/view_evaluations.php?user_id=<?php echo $individual['user_id']; ?>&period_id=<?php echo $period_id; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No individual performance data available for the selected filters.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <p>Please select an evaluation period to view reports.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/datatables/jquery.dataTables.min.js',
    'assets/js/datatables/dataTables.bootstrap4.min.js',
    'assets/js/demo/datatables-demo.js',
    'assets/js/chart.js/Chart.min.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update departments when college is selected
        const collegeSelect = document.getElementById('college_id');
        const departmentSelect = document.getElementById('department_id');
        
        collegeSelect.addEventListener('change', function() {
            const collegeId = this.value;
            
            // Reset department select
            departmentSelect.innerHTML = '<option value="">All Departments</option>';
            
            if (collegeId) {
                // Enable department select
                departmentSelect.removeAttribute('disabled');
                
                // Fetch departments for selected college
                fetch('<?php echo $base_url; ?>admin/get_departments.php?college_id=' + collegeId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            data.forEach(department => {
                                const option = document.createElement('option');
                                option.value = department.department_id;
                                option.textContent = department.name;
                                departmentSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching departments:', error);
                    });
            } else {
                // Disable department select
                departmentSelect.setAttribute('disabled', 'disabled');
            }
        });
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

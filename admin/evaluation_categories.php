<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Evaluation Categories Management
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

// Handle category deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // Check if category exists
    $sql = "SELECT * FROM evaluation_categories WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Check if there are criteria using this category
        $sql = "SELECT COUNT(*) as count FROM evaluation_criteria WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error_message = "Cannot delete category. Please delete all criteria in this category first.";
        } else {
            // Delete category
            $sql = "DELETE FROM evaluation_categories WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $category_id);
            
            if ($stmt->execute()) {
                $success_message = "Evaluation category deleted successfully.";
            } else {
                $error_message = "Error deleting evaluation category: " . $conn->error;
            }
        }
    } else {
        $error_message = "Evaluation category not found.";
    }
}

// Get all categories
$categories = [];
$sql = "SELECT * FROM evaluation_categories ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Evaluation Categories Management</h1>
            <a href="<?php echo $base_url; ?>admin/add_category.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Add New Category
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

        <!-- Categories Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">All Evaluation Categories</h6>
            </div>
            <div class="card-body">
                <?php if (count($categories) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="categoriesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Weight</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['category_id']; ?></td>
                                        <td><?php echo $category['name']; ?></td>
                                        <td><?php echo $category['description']; ?></td>
                                        <td><?php echo isset($category['weight']) ? $category['weight'] : '1.00'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>admin/edit_category.php?id=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/evaluation_categories.php?delete=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category? This will also delete all criteria in this category.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No evaluation categories found. <a href="<?php echo $base_url; ?>admin/add_category.php">Add a category</a> to get started.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category Statistics -->
        <div class="row">
            <!-- Category Distribution -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Category Distribution</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie">
                            <canvas id="categoryDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Weights -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Category Weights</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="categoryWeightsChart"></canvas>
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
        $('#categoriesTable').DataTable();
        
        <?php if (count($categories) > 0): ?>
            // Chart.js - Category Distribution
            var categoryDistributionCtx = document.getElementById('categoryDistributionChart');
            var categoryWeightsCtx = document.getElementById('categoryWeightsChart');
            
            // Prepare data for charts
            var categoryLabels = [];
            var categoryWeights = [];
            var backgroundColors = [];
            
            <?php foreach ($categories as $index => $category): ?>
                categoryLabels.push('<?php echo $category['name']; ?>');
                categoryWeights.push(<?php echo isset($category['weight']) ? $category['weight'] : 1.00; ?>);
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
            
            // Create Category Distribution Chart
            if (categoryDistributionCtx) {
                new Chart(categoryDistributionCtx, {
                    type: 'pie',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            data: categoryWeights,
                            backgroundColor: backgroundColors,
                            hoverBackgroundColor: backgroundColors,
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
                            position: 'bottom'
                        },
                        cutoutPercentage: 0,
                    },
                });
            }
            
            // Create Category Weights Chart
            if (categoryWeightsCtx) {
                new Chart(categoryWeightsCtx, {
                    type: 'bar',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            label: 'Weight',
                            data: categoryWeights,
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
                                    beginAtZero: true
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

<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Evaluation Criteria Management
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

// Handle delete criteria
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $criteria_id = (int)$_GET['delete'];

    // Check if criteria exists
    $sql = "SELECT * FROM evaluation_criteria WHERE criteria_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $criteria_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Delete criteria
        $sql = "DELETE FROM evaluation_criteria WHERE criteria_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $criteria_id);

        if ($stmt->execute()) {
            $success_message = "Evaluation criteria deleted successfully.";
        } else {
            $error_message = "Error deleting evaluation criteria: " . $conn->error;
        }
    } else {
        $error_message = "Evaluation criteria not found.";
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

// Get all criteria with category names
$criteria = [];
$sql = "SELECT ec.*, cat.name as category_name
        FROM evaluation_criteria ec
        JOIN evaluation_categories cat ON ec.category_id = cat.category_id
        ORDER BY cat.name ASC, ec.name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $criteria[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Evaluation Criteria Management</h1>
            <a href="<?php echo $base_url; ?>admin/add_criteria.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Add New Criteria
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
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Evaluation Categories</h6>
                <a href="<?php echo $base_url; ?>admin/add_category.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus fa-sm mr-1"></i> Add Category
                </a>
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
                                        <td>
                                            <a href="<?php echo $base_url; ?>admin/edit_category.php?id=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/evaluation_categories.php?delete=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category? This will also delete all criteria in this category.')">
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

        <!-- Criteria Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Evaluation Criteria</h6>
                <a href="<?php echo $base_url; ?>admin/add_criteria.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus fa-sm mr-1"></i> Add Criteria
                </a>
            </div>
            <div class="card-body">
                <?php if (count($criteria) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="criteriaTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Weight</th>
                                    <th>Evaluator Role</th>
                                    <th>Target Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criteria as $criterion): ?>
                                    <tr>
                                        <td><?php echo $criterion['criteria_id']; ?></td>
                                        <td><?php echo $criterion['name']; ?></td>
                                        <td><?php echo $criterion['category_name']; ?></td>
                                        <td><?php echo $criterion['description']; ?></td>
                                        <td><?php echo $criterion['weight']; ?></td>
                                        <td>
                                            <?php if (isset($criterion['evaluator_role']) && !empty($criterion['evaluator_role'])): ?>
                                                <span class="badge badge-<?php
                                                    switch ($criterion['evaluator_role']) {
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
                                                    <?php echo ucwords(str_replace('_', ' ', $criterion['evaluator_role'])); ?>
                                                </span>
                                            <?php elseif (isset($criterion['evaluator_roles']) && !empty($criterion['evaluator_roles'])): ?>
                                                <span class="badge badge-secondary">
                                                    <?php echo ucwords(str_replace('_', ' ', $criterion['evaluator_roles'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">All</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($criterion['target_role']) && !empty($criterion['target_role'])): ?>
                                                <span class="badge badge-<?php
                                                    switch ($criterion['target_role']) {
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
                                                    <?php echo ucwords(str_replace('_', ' ', $criterion['target_role'])); ?>
                                                </span>
                                            <?php elseif (isset($criterion['evaluatee_roles']) && !empty($criterion['evaluatee_roles'])): ?>
                                                <span class="badge badge-secondary">
                                                    <?php echo ucwords(str_replace('_', ' ', $criterion['evaluatee_roles'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">All</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>admin/edit_criteria.php?id=<?php echo $criterion['criteria_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/evaluation_criteria.php?delete=<?php echo $criterion['criteria_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this criteria?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No evaluation criteria found. <a href="<?php echo $base_url; ?>admin/add_criteria.php">Add criteria</a> to get started.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/datatables/jquery.dataTables.min.js',
    'assets/js/datatables/dataTables.bootstrap4.min.js',
    'assets/js/demo/datatables-demo.js'
];

// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

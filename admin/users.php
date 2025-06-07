<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - User Management
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

// Handle user activation/deactivation
if (isset($_GET['toggle_status']) && !empty($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];

    // Get current status
    $sql = "SELECT status FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $new_status = ($row['status'] == 1) ? 0 : 1;

        // Update status
        $sql = "UPDATE users SET status = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $new_status, $user_id);

        if ($stmt->execute()) {
            $status_text = ($new_status == 1) ? 'activated' : 'deactivated';
            $success_message = "User {$status_text} successfully.";
        } else {
            $error_message = "Error updating user status: " . $conn->error;
        }
    } else {
        $error_message = "User not found.";
    }
}

// Handle user deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];

    // Check if user exists and is not admin
    $sql = "SELECT * FROM users WHERE user_id = ? AND role != 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Delete user
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $success_message = "User deleted successfully.";
        } else {
            $error_message = "Error deleting user: " . $conn->error;
        }
    } else {
        $error_message = "User not found or cannot delete admin user.";
    }
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
    $bulk_action = sanitize_input($_POST['bulk_action']);
    $selected_users = $_POST['selected_users'];
    $success_count = 0;
    $error_count = 0;

    foreach ($selected_users as $user_id) {
        $user_id = (int)$user_id;

        // Skip admin users for bulk actions
        $sql = "SELECT role FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if ($row['role'] === 'admin') {
                $error_count++;
                continue;
            }
        }

        switch ($bulk_action) {
            case 'activate':
                $sql = "UPDATE users SET status = 1 WHERE user_id = ?";
                break;
            case 'deactivate':
                $sql = "UPDATE users SET status = 0 WHERE user_id = ?";
                break;
            case 'delete':
                $sql = "DELETE FROM users WHERE user_id = ?";
                break;
            default:
                continue 2;
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
    }

    if ($success_count > 0) {
        $action_text = ucfirst($bulk_action);
        if ($bulk_action === 'delete') {
            $action_text = 'deleted';
        } elseif ($bulk_action === 'activate') {
            $action_text = 'activated';
        } elseif ($bulk_action === 'deactivate') {
            $action_text = 'deactivated';
        }
        $success_message = "{$success_count} user(s) {$action_text} successfully.";
    }

    if ($error_count > 0) {
        $error_message = "{$error_count} user(s) could not be processed (admin users are protected).";
    }
}

// Get all users with department and college names
$users = [];
$sql = "SELECT u.*, d.name as department_name, c.name as college_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN colleges c ON u.college_id = c.college_id
        ORDER BY u.role ASC, u.full_name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">User Management</h1>
            <a href="<?php echo $base_url; ?>admin/add_user.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-user-plus fa-sm text-white-50 mr-1"></i> Add New User
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

        <!-- Users Statistics -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($users); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo count(array_filter($users, function($user) { return $user['status'] == 1; })); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Departments</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo count(array_unique(array_filter(array_column($users, 'department_name')))); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-building fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Colleges</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo count(array_unique(array_filter(array_column($users, 'college_name')))); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-university fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">All Users (<?php echo count($users); ?>)</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Export Options:</div>
                        <a class="dropdown-item" href="#" onclick="exportTableToCSV('users.csv')">
                            <i class="fas fa-file-csv fa-sm fa-fw mr-2 text-gray-400"></i>
                            Export to CSV
                        </a>
                        <a class="dropdown-item" href="#" onclick="window.print()">
                            <i class="fas fa-print fa-sm fa-fw mr-2 text-gray-400"></i>
                            Print
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Bulk Actions Form -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="bulkActionsForm">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <select class="form-control" name="bulk_action" id="bulkAction">
                                <option value="">Select Bulk Action</option>
                                <option value="activate">Activate Selected</option>
                                <option value="deactivate">Deactivate Selected</option>
                                <option value="delete">Delete Selected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary" id="applyBulkAction" disabled>
                                <i class="fas fa-check mr-1"></i> Apply
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted">
                                <span id="selectedCount">0</span> user(s) selected
                            </small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="30">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="selectAll">
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Department/College</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input user-checkbox"
                                                       id="user_<?php echo $user['user_id']; ?>"
                                                       name="selected_users[]"
                                                       value="<?php echo $user['user_id']; ?>">
                                                <label class="custom-control-label" for="user_<?php echo $user['user_id']; ?>"></label>
                                            </div>
                                        <?php else: ?>
                                            <i class="fas fa-lock text-muted" title="Admin users are protected"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <div class="icon-circle bg-primary">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                <div class="small text-gray-600"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                            switch ($user['role']) {
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
                                                case 'admin':
                                                    echo 'dark';
                                                    break;
                                                default:
                                                    echo 'secondary';
                                            }
                                        ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($user['department_name'])) {
                                            echo '<i class="fas fa-building text-info mr-1"></i>' . htmlspecialchars($user['department_name']);
                                        } elseif (!empty($user['college_name'])) {
                                            echo '<i class="fas fa-university text-success mr-1"></i>' . htmlspecialchars($user['college_name']);
                                        } else {
                                            echo '<span class="text-muted">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo !empty($user['position']) ? htmlspecialchars($user['position']) : '<span class="text-muted">N/A</span>'; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($user['status'] == 1) ? 'success' : 'danger'; ?>">
                                            <i class="fas fa-<?php echo ($user['status'] == 1) ? 'check' : 'times'; ?> mr-1"></i>
                                            <?php echo ($user['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['created_at'])): ?>
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo $base_url; ?>admin/view_user.php?id=<?php echo $user['user_id']; ?>"
                                               class="btn btn-sm btn-primary" title="View User Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>admin/edit_user.php?id=<?php echo $user['user_id']; ?>"
                                               class="btn btn-sm btn-info" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <a href="<?php echo $base_url; ?>admin/users.php?toggle_status=<?php echo $user['user_id']; ?>"
                                                   class="btn btn-sm btn-<?php echo ($user['status'] == 1) ? 'warning' : 'success'; ?>"
                                                   onclick="return confirm('Are you sure you want to <?php echo ($user['status'] == 1) ? 'deactivate' : 'activate'; ?> this user?')"
                                                   title="<?php echo ($user['status'] == 1) ? 'Deactivate' : 'Activate'; ?> User">
                                                    <i class="fas fa-<?php echo ($user['status'] == 1) ? 'ban' : 'check'; ?>"></i>
                                                </a>
                                                <a href="<?php echo $base_url; ?>admin/users.php?delete=<?php echo $user['user_id']; ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"
                                                   title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-secondary disabled" title="Admin users cannot be modified">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/datatables/jquery.dataTables.min.js',
    'assets/js/datatables/dataTables.bootstrap4.min.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable with enhanced features
        $('#usersTable').DataTable({
            "pageLength": 25,
            "order": [[ 7, "desc" ]], // Sort by created date (adjusted for checkbox column)
            "columnDefs": [
                { "orderable": false, "targets": [0, 8] } // Disable sorting on checkbox and Actions columns
            ],
            "language": {
                "search": "Search users:",
                "lengthMenu": "Show _MENU_ users per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ users",
                "infoEmpty": "No users found",
                "infoFiltered": "(filtered from _MAX_ total users)"
            },
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                   '<"row"<"col-sm-12"tr>>' +
                   '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            "responsive": true
        });

        // Add role filter
        addRoleFilter();

        // Add status filter
        addStatusFilter();

        // Handle bulk actions
        initializeBulkActions();
    });

    // Function to add role filter
    function addRoleFilter() {
        var table = $('#usersTable').DataTable();

        // Create role filter dropdown
        var roleFilter = $('<select class="form-control form-control-sm ml-2" style="width: auto; display: inline-block;"><option value="">All Roles</option></select>');

        // Get unique roles
        var roles = [];
        table.column(2).data().unique().each(function(d) {
            var roleText = $(d).text();
            if (roles.indexOf(roleText) === -1) {
                roles.push(roleText);
            }
        });

        // Add options to dropdown
        roles.sort().forEach(function(role) {
            roleFilter.append('<option value="' + role + '">' + role + '</option>');
        });

        // Add filter to page
        $('.dataTables_filter').append('<label class="ml-3">Role: </label>').append(roleFilter);

        // Apply filter
        roleFilter.on('change', function() {
            table.column(3).search(this.value).draw(); // Adjusted for checkbox column
        });
    }

    // Function to add status filter
    function addStatusFilter() {
        var table = $('#usersTable').DataTable();

        // Create status filter dropdown
        var statusFilter = $('<select class="form-control form-control-sm ml-2" style="width: auto; display: inline-block;"><option value="">All Status</option><option value="Active">Active</option><option value="Inactive">Inactive</option></select>');

        // Add filter to page
        $('.dataTables_filter').append('<label class="ml-3">Status: </label>').append(statusFilter);

        // Apply filter
        statusFilter.on('change', function() {
            table.column(6).search(this.value).draw(); // Adjusted for checkbox column
        });
    }

    // Function to initialize bulk actions
    function initializeBulkActions() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        const selectedCountSpan = document.getElementById('selectedCount');
        const bulkActionSelect = document.getElementById('bulkAction');
        const applyBulkActionBtn = document.getElementById('applyBulkAction');
        const bulkActionsForm = document.getElementById('bulkActionsForm');

        // Handle select all checkbox
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelectedCount();
        });

        // Handle individual checkboxes
        userCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();

                // Update select all checkbox state
                const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === userCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < userCheckboxes.length;
            });
        });

        // Update selected count and button state
        function updateSelectedCount() {
            const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
            selectedCountSpan.textContent = checkedCount;

            // Enable/disable bulk action button
            const hasSelection = checkedCount > 0;
            const hasAction = bulkActionSelect.value !== '';
            applyBulkActionBtn.disabled = !(hasSelection && hasAction);
        }

        // Handle bulk action select change
        bulkActionSelect.addEventListener('change', function() {
            updateSelectedCount();
        });

        // Handle form submission
        bulkActionsForm.addEventListener('submit', function(e) {
            const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
            const action = bulkActionSelect.value;

            if (checkedCount === 0) {
                e.preventDefault();
                alert('Please select at least one user.');
                return;
            }

            if (!action) {
                e.preventDefault();
                alert('Please select a bulk action.');
                return;
            }

            // Confirm destructive actions
            if (action === 'delete') {
                if (!confirm(`Are you sure you want to delete ${checkedCount} user(s)? This action cannot be undone.`)) {
                    e.preventDefault();
                    return;
                }
            } else if (action === 'deactivate') {
                if (!confirm(`Are you sure you want to deactivate ${checkedCount} user(s)?`)) {
                    e.preventDefault();
                    return;
                }
            }
        });
    }

    // Function to export table to CSV
    function exportTableToCSV(filename) {
        var csv = [];
        var rows = document.querySelectorAll("#usersTable tr");

        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");

            for (var j = 0; j < cols.length - 1; j++) { // Exclude actions column
                var cellText = cols[j].innerText.replace(/"/g, '""');
                row.push('"' + cellText + '"');
            }

            csv.push(row.join(","));
        }

        // Download CSV file
        downloadCSV(csv.join("\n"), filename);
    }

    // Function to download CSV
    function downloadCSV(csv, filename) {
        var csvFile;
        var downloadLink;

        // CSV file
        csvFile = new Blob([csv], {type: "text/csv"});

        // Download link
        downloadLink = document.createElement("a");

        // File name
        downloadLink.download = filename;

        // Create a link to the file
        downloadLink.href = window.URL.createObjectURL(csvFile);

        // Hide download link
        downloadLink.style.display = "none";

        // Add the link to DOM
        document.body.appendChild(downloadLink);

        // Click download link
        downloadLink.click();
    }

    // Print styles
    window.addEventListener('beforeprint', function() {
        // Hide action buttons when printing
        var actionCells = document.querySelectorAll('#usersTable td:last-child, #usersTable th:last-child');
        actionCells.forEach(function(cell) {
            cell.style.display = 'none';
        });
    });

    window.addEventListener('afterprint', function() {
        // Show action buttons after printing
        var actionCells = document.querySelectorAll('#usersTable td:last-child, #usersTable th:last-child');
        actionCells.forEach(function(cell) {
            cell.style.display = '';
        });
    });
</script>

<style>
    @media print {
        .btn-group, .dropdown, .card-header .dropdown {
            display: none !important;
        }

        .table td:last-child, .table th:last-child {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }

    .icon-circle {
        height: 2rem;
        width: 2rem;
        border-radius: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>

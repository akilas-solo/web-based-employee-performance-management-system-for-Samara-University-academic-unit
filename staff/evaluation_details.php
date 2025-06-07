<?php
/**
 * Samara University Academic Performance Evaluation System
 * Staff - Evaluation Details
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has staff role
if (!is_logged_in() || !has_role('staff')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evaluation = null;
$criteria = [];
$comments = [];

// Check if evaluation_id is provided
if ($evaluation_id <= 0) {
    redirect($base_url . 'staff/evaluations.php');
}

// Get evaluation details
$sql = "SELECT e.*, ep.title as period_name, ep.description as period_description,
        u1.full_name as evaluator_name, u1.role as evaluator_role, u1.profile_image as evaluator_image,
        u2.full_name as evaluatee_name, u2.role as evaluatee_role, u2.profile_image as evaluatee_image
        FROM evaluations e
        JOIN evaluation_periods ep ON e.period_id = ep.period_id
        JOIN users u1 ON e.evaluator_id = u1.user_id
        JOIN users u2 ON e.evaluatee_id = u2.user_id
        WHERE e.evaluation_id = ? AND e.evaluatee_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $evaluation_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $evaluation = $result->fetch_assoc();
    } else {
        redirect($base_url . 'staff/evaluations.php');
    }
} else {
    // Handle error - query preparation failed
    $error_message = "Error preparing query: " . $conn->error;
    redirect($base_url . 'staff/evaluations.php');
}

// Get evaluation criteria and scores
$sql = "SELECT er.*, ec.name as criteria_name, ec.description as criteria_description,
        ec.weight as criteria_weight, ec.max_rating as criteria_max_score
        FROM evaluation_responses er
        JOIN evaluation_criteria ec ON er.criteria_id = ec.criteria_id
        WHERE er.evaluation_id = ?
        ORDER BY ec.criteria_id ASC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $criteria[] = $row;
        }
    }
} else {
    // Handle error - query preparation failed
    error_log("Error preparing criteria query: " . $conn->error);
}

// Get evaluation comments
$sql = "SELECT ec.*, u.full_name, u.role, u.profile_image
        FROM evaluation_comments ec
        JOIN users u ON ec.user_id = u.user_id
        WHERE ec.evaluation_id = ?
        ORDER BY ec.created_at ASC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
    }
} else {
    // Handle error - query preparation failed
    error_log("Error preparing comments query: " . $conn->error);
}

// Process comment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment_text = sanitize_input($_POST['comment']);

    if (!empty($comment_text)) {
        // First check if the evaluation_comments table exists
        $table_exists = false;
        $check_table = $conn->query("SHOW TABLES LIKE 'evaluation_comments'");
        if ($check_table && $check_table->num_rows > 0) {
            $table_exists = true;
        }

        // Create the table if it doesn't exist
        if (!$table_exists) {
            $create_table = "CREATE TABLE IF NOT EXISTS evaluation_comments (
                comment_id INT AUTO_INCREMENT PRIMARY KEY,
                evaluation_id INT NOT NULL,
                user_id INT NOT NULL,
                comment TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            )";
            $conn->query($create_table);
        }

        $sql = "INSERT INTO evaluation_comments (evaluation_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iis", $evaluation_id, $user_id, $comment_text);

            if ($stmt->execute()) {
                $success_message = 'Comment added successfully.';

                // Get the new comment
                $comment_id = $conn->insert_id;
                $sql = "SELECT ec.*, u.full_name, u.role, u.profile_image
                        FROM evaluation_comments ec
                        JOIN users u ON ec.user_id = u.user_id
                        WHERE ec.comment_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $comment_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result && $result->num_rows === 1) {
                        $comments[] = $result->fetch_assoc();
                    }
                }
            } else {
                $error_message = 'Error adding comment: ' . $conn->error;
            }
        } else {
            $error_message = 'Error preparing query: ' . $conn->error;
        }
    } else {
        $error_message = 'Comment cannot be empty.';
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
            <h1 class="h3 mb-0 text-gray-800">Evaluation Details</h1>
            <a href="<?php echo $base_url; ?>staff/evaluations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Evaluations
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

        <!-- Evaluation Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Evaluation Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Evaluation Period:</h6>
                            <p><?php echo $evaluation['period_name']; ?></p>
                        </div>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Evaluation Date:</h6>
                            <p><?php echo date('F d, Y', strtotime($evaluation['updated_at'])); ?></p>
                        </div>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Category:</h6>
                            <p>Performance Evaluation</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Evaluator:</h6>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($evaluation['evaluator_image'])): ?>
                                    <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $evaluation['evaluator_image']; ?>" alt="<?php echo $evaluation['evaluator_name']; ?>" class="img-profile rounded-circle mr-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2" style="width: 40px; height: 40px; font-size: 1rem;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="mb-0"><?php echo $evaluation['evaluator_name']; ?></p>
                                    <small class="text-muted"><?php echo ucfirst($evaluation['evaluator_role']); ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Status:</h6>
                            <p>
                                <span class="badge badge-<?php echo get_status_color($evaluation['status']); ?>">
                                    <?php echo ucfirst($evaluation['status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Total Score:</h6>
                            <p class="font-weight-bold text-primary"><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</p>
                        </div>
                    </div>
                </div>
                <?php if (!empty($evaluation['comments'])): ?>
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Evaluator Notes:</h6>
                        <p><?php echo nl2br($evaluation['comments']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluation Criteria Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Evaluation Criteria</h6>
            </div>
            <div class="card-body">
                <?php if (count($criteria) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Criteria</th>
                                    <th>Description</th>
                                    <th>Weight</th>
                                    <th>Score</th>
                                    <th>Weighted Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criteria as $criterion): ?>
                                    <tr>
                                        <td><?php echo $criterion['criteria_name']; ?></td>
                                        <td><?php echo $criterion['criteria_description']; ?></td>
                                        <td><?php echo $criterion['criteria_weight']; ?>%</td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($criterion['rating'] / $criterion['criteria_max_score']) * 100; ?>%;" aria-valuenow="<?php echo $criterion['rating']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $criterion['criteria_max_score']; ?>">
                                                    <?php echo $criterion['rating']; ?>/<?php echo $criterion['criteria_max_score']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php
                                            // Calculate weighted score
                                            $weighted_score = ($criterion['rating'] / $criterion['criteria_max_score']) * $criterion['criteria_weight'] / 100 * 5;
                                            echo number_format($weighted_score, 2);
                                        ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="4" class="text-right">Total Score:</th>
                                    <th><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No criteria found for this evaluation.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Comments</h6>
            </div>
            <div class="card-body">
                <?php if (count($comments) > 0): ?>
                    <div class="comments-section mb-4">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment mb-3">
                                <div class="d-flex">
                                    <?php if (!empty($comment['profile_image'])): ?>
                                        <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $comment['profile_image']; ?>" alt="<?php echo $comment['full_name']; ?>" class="img-profile rounded-circle mr-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2" style="width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <strong><?php echo $comment['full_name']; ?></strong>
                                            <small class="text-muted ml-2"><?php echo ucfirst($comment['role']); ?></small>
                                            <small class="text-muted ml-2"><?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?></small>
                                        </div>
                                        <div class="comment-body">
                                            <p class="mb-0"><?php echo nl2br($comment['comment']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center mb-4">No comments yet.</p>
                <?php endif; ?>

                <!-- Comment Form -->
                <form action="<?php echo $base_url; ?>staff/evaluation_details.php?id=<?php echo $evaluation_id; ?>" method="post">
                    <div class="form-group">
                        <label for="comment">Add Comment</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Submit Comment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';

/**
 * Get status color for badges
 *
 * @param string $status Status string
 * @return string Bootstrap color class
 */
function get_status_color($status) {
    switch ($status) {
        case 'draft':
            return 'secondary';
        case 'submitted':
            return 'primary';
        case 'reviewed':
            return 'info';
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

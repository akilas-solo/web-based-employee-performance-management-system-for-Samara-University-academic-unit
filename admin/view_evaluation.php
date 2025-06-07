<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - View Evaluation
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
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evaluation = null;
$evaluator = null;
$evaluatee = null;
$period = null;
$responses_by_category = [];

// Check if evaluation_id is provided
if ($evaluation_id <= 0) {
    redirect($base_url . 'admin/evaluation_periods.php');
}

// Get evaluation information
$sql = "SELECT e.*,
        p.title as period_title, p.academic_year, p.semester, p.start_date, p.end_date,
        ee.full_name as evaluatee_name, ee.email as evaluatee_email, ee.role as evaluatee_role,
        ee.profile_image as evaluatee_image, ee.position as evaluatee_position,
        er.full_name as evaluator_name, er.email as evaluator_email, er.role as evaluator_role,
        er.profile_image as evaluator_image, er.position as evaluator_position,
        d.name as department_name, d.code as department_code,
        c.name as college_name, c.code as college_code
        FROM evaluations e
        JOIN evaluation_periods p ON e.period_id = p.period_id
        JOIN users ee ON e.evaluatee_id = ee.user_id
        JOIN users er ON e.evaluator_id = er.user_id
        LEFT JOIN departments d ON ee.department_id = d.department_id
        LEFT JOIN colleges c ON ee.college_id = c.college_id
        WHERE e.evaluation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $evaluation_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $evaluation = $result->fetch_assoc();

    // Extract period information
    $period = [
        'period_id' => $evaluation['period_id'],
        'title' => $evaluation['period_title'],
        'academic_year' => $evaluation['academic_year'],
        'semester' => $evaluation['semester'],
        'start_date' => $evaluation['start_date'],
        'end_date' => $evaluation['end_date']
    ];
} else {
    redirect($base_url . 'admin/evaluation_periods.php');
}

// Get evaluation responses by category
$sql = "SELECT er.*, ec.name as criteria_name, ec.description as criteria_description,
        ec.weight, ec.min_rating, ec.max_rating, cat.name as category_name, cat.category_id
        FROM evaluation_responses er
        JOIN evaluation_criteria ec ON er.criteria_id = ec.criteria_id
        JOIN evaluation_categories cat ON ec.category_id = cat.category_id
        WHERE er.evaluation_id = ?
        ORDER BY cat.name ASC, ec.name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $evaluation_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $category_id = $row['category_id'];
        if (!isset($responses_by_category[$category_id])) {
            $responses_by_category[$category_id] = [
                'name' => $row['category_name'],
                'responses' => [],
                'total_weight' => 0,
                'average_score' => 0
            ];
        }

        $responses_by_category[$category_id]['responses'][] = [
            'criteria_name' => $row['criteria_name'],
            'criteria_description' => $row['criteria_description'],
            'rating' => $row['rating'],
            'comment' => $row['comment'],
            'weight' => $row['weight'],
            'min_rating' => $row['min_rating'],
            'max_rating' => $row['max_rating']
        ];

        $responses_by_category[$category_id]['total_weight'] += $row['weight'];
    }

    // Calculate average score for each category
    foreach ($responses_by_category as $category_id => &$category) {
        $weighted_sum = 0;
        foreach ($category['responses'] as $response) {
            $normalized_score = ($response['rating'] / $response['max_rating']) * $response['weight'];
            $weighted_sum += $normalized_score;
        }
        $category['average_score'] = ($weighted_sum / $category['total_weight']) * 5;
    }
}

// Process form submission for status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitize_input($_POST['status']);
    $comments = sanitize_input($_POST['admin_comments']);

    // Update evaluation status
    $sql = "UPDATE evaluations SET status = ?, comments = ? WHERE evaluation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $new_status, $comments, $evaluation_id);

    if ($stmt->execute()) {
        $success_message = "Evaluation status updated successfully.";

        // Refresh evaluation data
        $sql = "SELECT * FROM evaluations WHERE evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $evaluation_data = $result->fetch_assoc();
            $evaluation['status'] = $evaluation_data['status'];
            $evaluation['comments'] = $evaluation_data['comments'];
        }
    } else {
        $error_message = "Error updating evaluation status: " . $conn->error;
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
            <div>
                <a href="<?php echo $base_url; ?>admin/view_period.php?id=<?php echo $evaluation['period_id']; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Period
                </a>
                <a href="<?php echo $base_url; ?>admin/evaluation_periods.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-list fa-sm text-white-50 mr-1"></i> All Periods
                </a>
            </div>
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

        <div class="row">
            <!-- Evaluation Summary Card -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Evaluation Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h4 class="font-weight-bold text-primary"><?php echo $period['title']; ?></h4>
                            <p class="text-muted">
                                <?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?><br>
                                <?php echo date('M d, Y', strtotime($period['start_date'])); ?> -
                                <?php echo date('M d, Y', strtotime($period['end_date'])); ?>
                            </p>
                            <div class="mt-3">
                                <h1 class="text-primary"><?php echo number_format($evaluation['total_score'], 2); ?></h1>
                                <p>Overall Score (out of 5.00)</p>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-primary" role="progressbar"
                                        style="width: <?php echo ($evaluation['total_score'] / 5) * 100; ?>%"
                                        aria-valuenow="<?php echo $evaluation['total_score']; ?>"
                                        aria-valuemin="0" aria-valuemax="5">
                                        <?php echo number_format($evaluation['total_score'], 2); ?>/5
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">Status</h6>
                            <p>
                                <span class="badge badge-<?php
                                    echo ($evaluation['status'] == 'submitted' || $evaluation['status'] == 'reviewed' || $evaluation['status'] == 'approved') ? 'success' :
                                        (($evaluation['status'] == 'draft') ? 'warning' : 'secondary');
                                ?>">
                                    <?php echo ucfirst($evaluation['status']); ?>
                                </span>
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">Dates</h6>
                            <p>
                                <strong>Created:</strong> <?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?><br>
                                <?php if (!empty($evaluation['submission_date'])): ?>
                                    <strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($evaluation['submission_date'])); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($evaluation['review_date'])): ?>
                                    <strong>Reviewed:</strong> <?php echo date('M d, Y', strtotime($evaluation['review_date'])); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($evaluation['approval_date'])): ?>
                                    <strong>Approved:</strong> <?php echo date('M d, Y', strtotime($evaluation['approval_date'])); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($evaluation['rejection_date'])): ?>
                                    <strong>Rejected:</strong> <?php echo date('M d, Y', strtotime($evaluation['rejection_date'])); ?><br>
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php if (!empty($evaluation['comments'])): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Comments</h6>
                                <p><?php echo $evaluation['comments']; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($evaluation['rejection_reason'])): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Rejection Reason</h6>
                                <p><?php echo $evaluation['rejection_reason']; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Update Status Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Update Status</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $evaluation_id); ?>">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="draft" <?php echo ($evaluation['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                    <option value="submitted" <?php echo ($evaluation['status'] == 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                                    <option value="reviewed" <?php echo ($evaluation['status'] == 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="approved" <?php echo ($evaluation['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo ($evaluation['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="admin_comments">Admin Comments</label>
                                <textarea class="form-control" id="admin_comments" name="admin_comments" rows="3"><?php echo $evaluation['comments']; ?></textarea>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Evaluator and Evaluatee Info -->
            <div class="col-xl-8 col-lg-7">
                <div class="row">
                    <!-- Evaluator Card -->
                    <div class="col-md-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-info text-white">
                                <h6 class="m-0 font-weight-bold">Evaluator</h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <?php if (!empty($evaluation['evaluator_image'])): ?>
                                        <img class="img-profile rounded-circle mb-3" src="<?php echo $base_url; ?>uploads/profiles/<?php echo $evaluation['evaluator_image']; ?>" alt="Profile Image" style="width: 100px; height: 100px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h5 class="font-weight-bold"><?php echo $evaluation['evaluator_name']; ?></h5>
                                    <p class="text-muted">
                                        <?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluator_role'])); ?>
                                        <?php if (!empty($evaluation['evaluator_position'])): ?>
                                            <br><?php echo $evaluation['evaluator_position']; ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <p>
                                        <i class="fas fa-envelope mr-2 text-info"></i> <?php echo $evaluation['evaluator_email']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Evaluatee Card -->
                    <div class="col-md-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-success text-white">
                                <h6 class="m-0 font-weight-bold">Evaluatee</h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <?php if (!empty($evaluation['evaluatee_image'])): ?>
                                        <img class="img-profile rounded-circle mb-3" src="<?php echo $base_url; ?>uploads/profiles/<?php echo $evaluation['evaluatee_image']; ?>" alt="Profile Image" style="width: 100px; height: 100px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="img-profile rounded-circle bg-success text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h5 class="font-weight-bold"><?php echo $evaluation['evaluatee_name']; ?></h5>
                                    <p class="text-muted">
                                        <?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluatee_role'])); ?>
                                        <?php if (!empty($evaluation['evaluatee_position'])): ?>
                                            <br><?php echo $evaluation['evaluatee_position']; ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <p>
                                        <i class="fas fa-envelope mr-2 text-success"></i> <?php echo $evaluation['evaluatee_email']; ?><br>
                                        <?php if (!empty($evaluation['department_name'])): ?>
                                            <i class="fas fa-building mr-2 text-success"></i> <?php echo $evaluation['department_name']; ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($evaluation['college_name'])): ?>
                                            <i class="fas fa-university mr-2 text-success"></i> <?php echo $evaluation['college_name']; ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Scores Chart -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Category Scores</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="categoryScoresChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Evaluation Responses -->
                <?php if (count($responses_by_category) > 0): ?>
                    <?php foreach ($responses_by_category as $category_id => $category): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary"><?php echo $category['name']; ?></h6>
                                <div>
                                    <span class="badge badge-primary">
                                        Average: <?php echo number_format($category['average_score'], 2); ?>/5
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Criteria</th>
                                                <th>Rating</th>
                                                <th>Weight</th>
                                                <th>Comment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($category['responses'] as $response): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $response['criteria_name']; ?></strong>
                                                        <?php if (!empty($response['criteria_description'])): ?>
                                                            <p class="small text-muted mb-0"><?php echo $response['criteria_description']; ?></p>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="text-center">
                                                            <h5><?php echo $response['rating']; ?></h5>
                                                            <div class="progress" style="height: 10px;">
                                                                <div class="progress-bar bg-primary" role="progressbar"
                                                                    style="width: <?php echo ($response['rating'] / $response['max_rating']) * 100; ?>%"
                                                                    aria-valuenow="<?php echo $response['rating']; ?>"
                                                                    aria-valuemin="<?php echo $response['min_rating']; ?>"
                                                                    aria-valuemax="<?php echo $response['max_rating']; ?>">
                                                                </div>
                                                            </div>
                                                            <small class="text-muted"><?php echo $response['min_rating']; ?>-<?php echo $response['max_rating']; ?></small>
                                                        </div>
                                                    </td>
                                                    <td class="text-center"><?php echo $response['weight']; ?></td>
                                                    <td><?php echo !empty($response['comment']) ? $response['comment'] : '<em class="text-muted">No comment</em>'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p class="mb-0">No evaluation responses found. This evaluation may be in draft status or responses have not been recorded.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/chart.js/Chart.min.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart.js - Category Scores
        var categoryScoresCtx = document.getElementById('categoryScoresChart');

        <?php if (count($responses_by_category) > 0): ?>
            // Prepare data for chart
            var categoryLabels = [];
            var categoryScores = [];
            var backgroundColors = [];

            <?php foreach ($responses_by_category as $category_id => $category): ?>
                categoryLabels.push('<?php echo $category['name']; ?>');
                categoryScores.push(<?php echo number_format($category['average_score'], 2); ?>);
                backgroundColors.push(getRandomColor());
            <?php endforeach; ?>

            // Create category scores chart
            if (categoryScoresCtx) {
                new Chart(categoryScoresCtx, {
                    type: 'bar',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            label: 'Category Scores',
                            data: categoryScores,
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
        <?php endif; ?>

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

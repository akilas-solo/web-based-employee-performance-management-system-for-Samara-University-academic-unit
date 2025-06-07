<?php
/**
 * Samara University Academic Performance Evaluation System
 * Head of Department - Print Evaluation
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has head_of_department role
if (!is_logged_in() || !has_role('head_of_department')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evaluation = null;
$evaluatee = null;
$evaluator = null;
$period = null;
$responses_by_category = [];
$department = null;

// Get evaluation details
if ($evaluation_id > 0) {
    $sql = "SELECT e.* FROM evaluations e WHERE e.evaluation_id = ? AND e.evaluator_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $evaluation_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $evaluation = $result->fetch_assoc();

        // Get evaluatee details
        $sql = "SELECT u.*, d.name as department_name, c.name as college_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.department_id
                LEFT JOIN colleges c ON u.college_id = c.college_id
                WHERE u.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation['evaluatee_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $evaluatee = $result->fetch_assoc();
        }

        // Get evaluator details
        $sql = "SELECT u.*, d.name as department_name, c.name as college_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.department_id
                LEFT JOIN colleges c ON u.college_id = c.college_id
                WHERE u.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation['evaluator_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $evaluator = $result->fetch_assoc();
        }

        // Get period details
        $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation['period_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $period = $result->fetch_assoc();
        }

        // Get department details
        if ($evaluatee && $evaluatee['department_id']) {
            $sql = "SELECT d.*, c.name as college_name
                    FROM departments d
                    JOIN colleges c ON d.college_id = c.college_id
                    WHERE d.department_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $evaluatee['department_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $department = $result->fetch_assoc();
            }
        }

        // Get evaluation responses by category
        $sql = "SELECT er.*, ec.name as criteria_name, ec.description as criteria_description,
                ec.weight, ec.min_rating, ec.max_rating, cat.category_id, cat.name as category_name
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
                $category_name = $row['category_name'];

                if (!isset($responses_by_category[$category_id])) {
                    $responses_by_category[$category_id] = [
                        'name' => $category_name,
                        'responses' => [],
                        'average_score' => 0,
                        'total_weight' => 0
                    ];
                }

                $responses_by_category[$category_id]['responses'][] = $row;
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
    } else {
        die("Evaluation not found or you don't have permission to view it.");
    }
} else {
    die("Invalid evaluation ID.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Report - <?php echo $evaluatee['full_name']; ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }

        .university-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 5px;
        }

        .info-table .label {
            font-weight: bold;
            width: 150px;
        }

        .evaluation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .evaluation-table th, .evaluation-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .evaluation-table th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .category-header {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .signature-section {
            margin-top: 50px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            display: inline-block;
            margin-top: 50px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10pt;
            color: #666;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
                padding: 15mm;
            }

            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print Button -->
        <div class="no-print text-right mb-3">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print mr-1"></i> Print
            </button>
            <a href="<?php echo $base_url; ?>head/view_evaluation.php?id=<?php echo $evaluation_id; ?>" class="btn btn-secondary ml-2">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        <!-- Header -->
        <div class="header">
            <img src="<?php echo $base_url; ?>assets/images/logo.png" alt="Samara University Logo" class="logo">
            <div class="university-name">SAMARA UNIVERSITY</div>
            <div>Academic Performance Evaluation System</div>
            <div class="document-title">STAFF PERFORMANCE EVALUATION REPORT</div>
        </div>

        <!-- Evaluation Information -->
        <div class="row">
            <div class="col-md-6">
                <div class="section-title">Staff Information</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Name:</td>
                        <td><?php echo $evaluatee['full_name']; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Position:</td>
                        <td><?php echo $evaluatee['position'] ?? 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Department:</td>
                        <td><?php echo $evaluatee['department_name'] ?? 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td class="label">College:</td>
                        <td><?php echo $evaluatee['college_name'] ?? 'N/A'; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <div class="section-title">Evaluation Information</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Evaluation Period:</td>
                        <td><?php echo $period['title']; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Academic Year:</td>
                        <td><?php echo $period['academic_year']; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Semester:</td>
                        <td><?php echo $period['semester']; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Date:</td>
                        <td>
                            <?php
                            if (!empty($evaluation['submission_date'])) {
                                echo date('F d, Y', strtotime($evaluation['submission_date']));
                            } else {
                                echo date('F d, Y', strtotime($evaluation['created_at']));
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Overall Score -->
        <div class="section-title">Overall Performance</div>
        <div class="row">
            <div class="col-md-6">
                <table class="info-table">
                    <tr>
                        <td class="label">Overall Score:</td>
                        <td><strong><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Rating:</td>
                        <td>
                            <strong>
                                <?php
                                $score_percent = ($evaluation['total_score'] / 5) * 100;
                                if ($score_percent >= 90) {
                                    echo 'Excellent';
                                } elseif ($score_percent >= 80) {
                                    echo 'Very Good';
                                } elseif ($score_percent >= 70) {
                                    echo 'Good';
                                } elseif ($score_percent >= 60) {
                                    echo 'Satisfactory';
                                } elseif ($score_percent >= 50) {
                                    echo 'Fair';
                                } else {
                                    echo 'Needs Improvement';
                                }
                                ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Status:</td>
                        <td><strong><?php echo ucfirst($evaluation['status']); ?></strong></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="info-table">
                    <tr>
                        <td colspan="2"><strong>Category Scores:</strong></td>
                    </tr>
                    <?php foreach ($responses_by_category as $category): ?>
                        <tr>
                            <td class="label"><?php echo $category['name']; ?>:</td>
                            <td><?php echo number_format($category['average_score'], 2); ?>/5.00</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Detailed Evaluation -->
        <div class="section-title">Detailed Evaluation</div>
        <?php foreach ($responses_by_category as $category): ?>
            <div class="mb-4">
                <h5><?php echo $category['name']; ?> (Average: <?php echo number_format($category['average_score'], 2); ?>/5.00)</h5>
                <table class="evaluation-table">
                    <thead>
                        <tr>
                            <th width="40%">Criteria</th>
                            <th width="10%">Weight</th>
                            <th width="10%">Rating</th>
                            <th width="40%">Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category['responses'] as $response): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $response['criteria_name']; ?></strong>
                                    <?php if (!empty($response['criteria_description'])): ?>
                                        <div class="small text-muted"><?php echo $response['criteria_description']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $response['weight']; ?></td>
                                <td><?php echo $response['rating']; ?>/<?php echo $response['max_rating']; ?></td>
                                <td><?php echo nl2br(htmlspecialchars($response['comment'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <!-- Overall Comments -->
        <?php if (!empty($evaluation['comments'])): ?>
            <div class="section-title">Overall Comments</div>
            <div class="mb-4">
                <p><?php echo nl2br(htmlspecialchars($evaluation['comments'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="signature-section row">
            <div class="col-md-4 text-center">
                <div class="signature-line"></div>
                <div><strong>Evaluator</strong></div>
                <div><?php echo $evaluator['full_name']; ?></div>
                <div><?php echo ucwords(str_replace('_', ' ', $evaluator['role'])); ?></div>
            </div>
            <div class="col-md-4 text-center">
                <div class="signature-line"></div>
                <div><strong>Evaluatee</strong></div>
                <div><?php echo $evaluatee['full_name']; ?></div>
                <div><?php echo $evaluatee['position'] ?? 'Staff'; ?></div>
            </div>
            <div class="col-md-4 text-center">
                <div class="signature-line"></div>
                <div><strong>Department Head</strong></div>
                <div>_________________________</div>
                <div><?php echo $department['name'] ?? 'Department'; ?></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an official document of Samara University Academic Performance Evaluation System.</p>
            <p>Generated on: <?php echo date('F d, Y H:i:s'); ?></p>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

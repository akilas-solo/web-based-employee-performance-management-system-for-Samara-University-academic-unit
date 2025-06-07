<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - Print Evaluation
 */

// Include configuration file - use direct path to avoid memory issues
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has dean role
if (!is_logged_in() || !has_role('dean')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$college_id = $_SESSION['college_id'];
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evaluation = null;
$evaluatee = null;
$evaluator = null;
$period = null;
$responses_by_category = [];
$department = null;

// Check if evaluation_id is provided
if ($evaluation_id <= 0) {
    redirect($base_url . 'dean/evaluations.php');
}

// Get evaluation data
$sql = "SELECT e.*,
        u1.full_name as evaluator_name,
        u1.email as evaluator_email,
        u1.role as evaluator_role,
        u2.full_name as evaluatee_name,
        u2.email as evaluatee_email,
        u2.role as evaluatee_role,
        u2.position as evaluatee_position,
        d.name as department_name,
        d.code as department_code,
        p.title as period_title,
        p.academic_year,
        p.semester
        FROM evaluations e
        JOIN users u1 ON e.evaluator_id = u1.user_id
        JOIN users u2 ON e.evaluatee_id = u2.user_id
        LEFT JOIN departments d ON u2.department_id = d.department_id
        JOIN evaluation_periods p ON e.period_id = p.period_id
        WHERE e.evaluation_id = ? AND (e.evaluator_id = ? OR d.college_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $evaluation_id, $user_id, $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $evaluation = $result->fetch_assoc();
} else {
    redirect($base_url . 'dean/evaluations.php');
}

// Get evaluation responses by category
$sql = "SELECT er.*, ec.name as criteria_name, ec.description as criteria_description, ec.weight, ec.max_rating,
        cat.category_id, cat.name as category_name
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
                'avg_score' => 0,
                'total_weight' => 0
            ];
        }
        $responses_by_category[$category_id]['responses'][] = $row;
        $responses_by_category[$category_id]['total_weight'] += $row['weight'];
    }

    // Calculate average score for each category
    foreach ($responses_by_category as $category_id => $category) {
        $total_weighted_score = 0;
        foreach ($category['responses'] as $response) {
            $normalized_score = ($response['rating'] / $response['max_rating']) * $response['weight'];
            $total_weighted_score += $normalized_score;
        }
        if ($category['total_weight'] > 0) {
            $responses_by_category[$category_id]['avg_score'] = ($total_weighted_score / $category['total_weight']) * 5;
        }
    }
}

// Get university information
$university_name = "Samara University";
$university_logo = $base_url . "assets/images/logo.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Report - <?php echo $evaluation['evaluatee_name']; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
        .report-title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14pt;
            font-weight: bold;
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
        .criteria-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .criteria-table th, .criteria-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .criteria-table th {
            background-color: #f2f2f2;
            text-align: left;
        }
        .category-score {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            margin-bottom: 5px;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        @media print {
            .print-button {
                display: none;
            }
            @page {
                size: A4;
                margin: 1cm;
            }
            body {
                font-size: 12pt;
            }
        }
    </style>
</head>
<body>
    <button class="btn btn-primary print-button" onclick="window.print()">Print</button>

    <div class="container">
        <div class="header">
            <img src="<?php echo $university_logo; ?>" alt="University Logo" class="logo">
            <div class="university-name"><?php echo $university_name; ?></div>
            <div>Academic Performance Evaluation System</div>
            <div class="report-title">Evaluation Report</div>
        </div>

        <div class="section">
            <div class="section-title">Evaluation Information</div>
            <table class="info-table">
                <tr>
                    <td class="label">Evaluation Period:</td>
                    <td><?php echo $evaluation['period_title']; ?> (<?php echo $evaluation['academic_year']; ?>, Semester <?php echo $evaluation['semester']; ?>)</td>
                </tr>
                <tr>
                    <td class="label">Evaluation Date:</td>
                    <td><?php echo date('F d, Y', strtotime($evaluation['created_at'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Submission Date:</td>
                    <td><?php echo !empty($evaluation['submission_date']) ? date('F d, Y', strtotime($evaluation['submission_date'])) : 'Not submitted'; ?></td>
                </tr>
                <tr>
                    <td class="label">Status:</td>
                    <td><?php echo ucfirst($evaluation['status']); ?></td>
                </tr>
                <tr>
                    <td class="label">Total Score:</td>
                    <td><strong><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</strong></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Evaluatee Information</div>
            <table class="info-table">
                <tr>
                    <td class="label">Name:</td>
                    <td><?php echo $evaluation['evaluatee_name']; ?></td>
                </tr>
                <tr>
                    <td class="label">Position:</td>
                    <td><?php echo $evaluation['evaluatee_position'] ?? ucwords(str_replace('_', ' ', $evaluation['evaluatee_role'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Department:</td>
                    <td><?php echo $evaluation['department_name']; ?> (<?php echo $evaluation['department_code']; ?>)</td>
                </tr>
                <tr>
                    <td class="label">Email:</td>
                    <td><?php echo $evaluation['evaluatee_email']; ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Evaluator Information</div>
            <table class="info-table">
                <tr>
                    <td class="label">Name:</td>
                    <td><?php echo $evaluation['evaluator_name']; ?></td>
                </tr>
                <tr>
                    <td class="label">Position:</td>
                    <td><?php echo ucwords(str_replace('_', ' ', $evaluation['evaluator_role'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Email:</td>
                    <td><?php echo $evaluation['evaluator_email']; ?></td>
                </tr>
            </table>
        </div>

        <?php if (count($responses_by_category) > 0): ?>
            <div class="section">
                <div class="section-title">Evaluation Details</div>

                <?php foreach ($responses_by_category as $category_id => $category): ?>
                    <div class="category-section">
                        <h4><?php echo $category['name']; ?></h4>
                        <div class="category-score">
                            Category Score: <?php echo number_format($category['avg_score'], 2); ?>/5.00
                        </div>

                        <table class="criteria-table">
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
                                                <div><small><?php echo $response['criteria_description']; ?></small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $response['weight']; ?></td>
                                        <td><?php echo $response['rating']; ?>/5</td>
                                        <td><?php echo !empty($response['comment']) ? nl2br($response['comment']) : 'No comments'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="section">
                <div class="section-title">Evaluation Details</div>
                <p>No evaluation details available.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($evaluation['comments'])): ?>
            <div class="section">
                <div class="section-title">Overall Comments</div>
                <p><?php echo nl2br($evaluation['comments']); ?></p>
            </div>
        <?php endif; ?>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="text-center"><?php echo $evaluation['evaluator_name']; ?></div>
                <div class="text-center">Evaluator</div>
            </div>

            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="text-center"><?php echo $evaluation['evaluatee_name']; ?></div>
                <div class="text-center">Evaluatee</div>
            </div>
        </div>

        <div class="footer text-center mt-5">
            <p><small>Generated on <?php echo date('F d, Y'); ?> by Samara University Academic Performance Evaluation System</small></p>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto-print when page loads
            // Uncomment the line below to enable auto-print
            // window.print();
        };
    </script>
</body>
</html>

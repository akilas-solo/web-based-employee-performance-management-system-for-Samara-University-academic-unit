<?php
/**
 * Samara University Academic Performance Evaluation System
 * Home Page
 */

// Include configuration file
require_once 'includes/config.php';

// Check if user is already logged in
if (is_logged_in()) {
    // Redirect based on role
    if (is_admin()) {
        redirect($base_url . 'admin/dashboard.php');
    } else {
        $role = $_SESSION['role'];
        if ($role === 'head_of_department') {
            redirect($base_url . 'head/dashboard.php');
        } else {
            redirect($base_url . $role . '/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Performance
management system for SU
Academic Unit</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <style>
        /* College card hover effect */
        .hover-card {
            transition: all 0.3s ease;
        }

        .hover-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
        }

        .hover-card img {
            transition: all 0.3s ease;
        }

        .hover-card:hover img {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_url; ?>">
                <?php if (file_exists(BASE_PATH . '/assets/images/logo/samara-logo1.png')): ?>
                    <img src="<?php echo $base_url; ?>assets/images/logo/samara-logo1.png" alt="Samara University" height="40" class="mr-2">
                <?php else: ?>
                    <i class="fas fa-university text-white mr-2" style="font-size: 1.8rem;"></i>
                <?php endif; ?>
                Samara University
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item active">
                        <a class="nav-link" href="<?php echo $base_url; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ml-2" href="<?php echo $base_url; ?>login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" style="background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
        url('<?php echo file_exists(BASE_PATH . '/assets/images/devs/land.jpg') ? $base_url . 'assets/images/devs/land.jpg' : $base_url . 'assets/images/hero-image.svg'; ?>');
        background-size: cover; background-position: center; padding: 150px 0; color: white;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 text-white mb-4">web based employee performance
management system for Samara University
academic unit</h1>
                    <p class="lead mb-5">A comprehensive platform for evaluating and improving academic performance, peers, and supervisors.</p>
                    <div class="mt-4">
                        <a href="<?php echo $base_url; ?>login.php" class="btn btn-primary btn-lg mr-3 px-4 py-2">Get Started</a>
                        <a href="<?php echo $base_url; ?>about.php" class="btn btn-outline-light btn-lg px-4 py-2">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Key Features</h2>
                <hr class="divider my-4 mx-auto" style="max-width: 100px; border-width: 3px; border-color: var(--primary-color);">
                <p class="section-subtitle lead">Discover the powerful tools designed to enhance academic performance evaluation</p>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon mb-4 d-inline-block p-3 rounded-circle bg-primary-very-light">
                                <i class="fas fa-clipboard-check fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title mb-3">Comprehensive Evaluation</h4>
                            <p class="card-text text-muted">Multi-dimensional evaluation system that captures performance across various criteria and metrics.</p>
                            <a href="<?php echo $base_url; ?>about.php" class="btn btn-link text-primary mt-3">Learn More <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon mb-4 d-inline-block p-3 rounded-circle bg-primary-very-light">
                                <i class="fas fa-chart-line fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title mb-3">Detailed Analytics</h4>
                            <p class="card-text text-muted">Gain insights through detailed reports and analytics to track progress and identify areas for improvement.</p>
                            <a href="<?php echo $base_url; ?>about.php" class="btn btn-link text-primary mt-3">Learn More <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon mb-4 d-inline-block p-3 rounded-circle bg-primary-very-light">
                                <i class="fas fa-users-cog fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title mb-3">Role-Based Access</h4>
                            <p class="card-text text-muted">Secure role-based access control ensures that users can only access information relevant to their position.</p>
                            <a href="<?php echo $base_url; ?>about.php" class="btn btn-link text-primary mt-3">Learn More <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon mb-4 d-inline-block p-3 rounded-circle bg-primary-very-light">
                                <i class="fas fa-sync-alt fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title mb-3">Continuous Improvement</h4>
                            <p class="card-text text-muted">Regular evaluation cycles promote ongoing improvement and excellence in academic performance.</p>
                            <a href="<?php echo $base_url; ?>about.php" class="btn btn-link text-primary mt-3">Learn More <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon mb-4 d-inline-block p-3 rounded-circle bg-primary-very-light">
                                <i class="fas fa-file-alt fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title mb-3">Comprehensive Reports</h4>
                            <p class="card-text text-muted">Generate detailed reports for individuals, departments, and colleges to support data-driven decisions.</p>
                            <a href="<?php echo $base_url; ?>about.php" class="btn btn-link text-primary mt-3">Learn More <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon mb-4 d-inline-block p-3 rounded-circle bg-primary-very-light">
                                <i class="fas fa-shield-alt fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title mb-3">Secure & Reliable</h4>
                            <p class="card-text text-muted">Built with security in mind to protect sensitive evaluation data and ensure system reliability.</p>
                            <a href="<?php echo $base_url; ?>about.php" class="btn btn-link text-primary mt-3">Learn More <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Colleges Section -->
    <section class="colleges-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Our Colleges</h2>
                <hr class="divider my-4 mx-auto" style="max-width: 100px; border-width: 3px; border-color: var(--primary-color);">
                <p class="section-subtitle lead">Explore the academic colleges at Samara University</p>
            </div>

            <div class="row">
                <!-- College 1 -->
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm hover-card h-100">
                        <?php if (file_exists(BASE_PATH . '/assets/images/public/college_natural_science.jpg')): ?>
                            <img src="<?php echo $base_url; ?>assets/images/public/college_natural_science.jpg" alt="College of Natural Science" class="card-img-top p-3">
                        <?php else: ?>
                            <div class="bg-primary-very-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-flask fa-3x text-primary"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h5 class="card-title">College of Natural Science</h5>
                            <p class="card-text text-muted">Offering programs in Physics, Chemistry, Biology, Mathematics and more.</p>
                        </div>
                    </div>
                </div>

                <!-- College 2 -->
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm hover-card h-100">
                        <?php if (file_exists(BASE_PATH . '/assets/images/public/college_dry_land_agriculture.jpg')): ?>
                            <img src="<?php echo $base_url; ?>assets/images/public/college_dry_land_agriculture.jpg" alt="College of Dry Land Agriculture" class="card-img-top p-3">
                        <?php else: ?>
                            <div class="bg-primary-very-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-seedling fa-3x text-primary"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h5 class="card-title">College of Dry Land Agriculture</h5>
                            <p class="card-text text-muted">Specializing in agricultural techniques for arid and semi-arid regions.</p>
                        </div>
                    </div>
                </div>

                <!-- College 3 -->
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm hover-card h-100">
                        <?php if (file_exists(BASE_PATH . '/assets/images/public/college_business_economics.jpg')): ?>
                            <img src="<?php echo $base_url; ?>assets/images/public/college_business_economics.jpg" alt="College of Business and Economics" class="card-img-top p-3">
                        <?php else: ?>
                            <div class="bg-primary-very-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-chart-line fa-3x text-primary"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h5 class="card-title">College of Business and Economics</h5>
                            <p class="card-text text-muted">Providing education in Business Administration, Economics, Accounting and more.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="<?php echo $base_url; ?>about.php" class="btn btn-outline-primary">Learn More About Our Colleges <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section py-5" style="background-color: #f9f9f9;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="position-relative">
                        <?php if (file_exists(BASE_PATH . '/assets/images/devs/land.jpg')): ?>
                            <img src="<?php echo $base_url; ?>assets/images/devs/land.jpg" alt="About Samara University" class="img-fluid rounded shadow-lg" style="z-index: 1; position: relative;">
                        <?php else: ?>
                            <div class="bg-primary rounded shadow-lg d-flex align-items-center justify-content-center" style="height: 350px; z-index: 1; position: relative;">
                                <i class="fas fa-university fa-5x text-white"></i>
                            </div>
                        <?php endif; ?>
                        <div class="bg-primary position-absolute" style="width: 100%; height: 100%; bottom: -20px; right: -20px; z-index: 0; opacity: 0.1; border-radius: 0.35rem;"></div>
                    </div>
                </div>
                <div class="col-lg-6 pl-lg-5">
                    <div class="mb-4">
                        <span class="badge badge-primary px-3 py-2 mb-2 text-uppercase" style="letter-spacing: 1px;">About Us</span>
                        <h2 class="section-title mb-4">About Samara University</h2>
                        <hr class="divider my-4 ml-0" style="max-width: 100px; border-width: 3px; border-color: var(--primary-color);">
                    </div>
                    <p class="lead mb-4">Samara University is committed to excellence in education and research. Our academic performance evaluation system is designed to support continuous improvement and maintain high standards across all departments.</p>
                    <p class="mb-4">The system provides a structured framework for evaluating performance, identifying strengths and areas for development, and implementing targeted improvement strategies.</p>
                    <div class="d-flex align-items-center mb-4">
                        <div class="mr-4">
                            <i class="fas fa-graduation-cap fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Excellence in Education</h5>
                            <p class="mb-0 text-muted">Providing quality education to prepare students for success</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4">
                        <div class="mr-4">
                            <i class="fas fa-flask fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Innovative Research</h5>
                            <p class="mb-0 text-muted">Conducting cutting-edge research to address real-world challenges</p>
                        </div>
                    </div>
                    <a href="<?php echo $base_url; ?>about.php" class="btn btn-primary btn-lg mt-3">Learn More About Us <i class="fas fa-arrow-right ml-2"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Samara University</h5>
                    <p>Academic Performance Evaluation System</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo $base_url; ?>">Home</a></li>
                        <li><a href="<?php echo $base_url; ?>about.php">About</a></li>
                        <li><a href="<?php echo $base_url; ?>contact.php">Contact</a></li>
                        <li><a href="<?php echo $base_url; ?>login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt mr-2"></i> Samara University, Ethiopia</p>
                        <p><i class="fas fa-phone mr-2"></i> +251 123 456 789</p>
                        <p><i class="fas fa-envelope mr-2"></i> info@samara.edu.et</p>
                    </address>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Samara University. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

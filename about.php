<?php
/**
 * Samara University Academic Performance Evaluation System
 * About Page
 */

// Include configuration file
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Samara University Academic Performance Evaluation System</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <style>
        .about-header {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('<?php echo $base_url; ?>assets/images/university-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }

        .about-section {
            padding: 80px 0;
        }

        .about-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            height: 100%;
        }

        .about-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15);
        }

        .about-card .card-body {
            padding: 2rem;
        }

        .about-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }

        .team-member {
            text-align: center;
            margin-bottom: 2rem;
        }

        .team-member img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 5px solid #f8f9fa;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .timeline {
            position: relative;
            padding: 0;
            list-style: none;
        }

        .timeline:before {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 2px;
            margin-left: -1px;
            content: '';
            background-color: #e9ecef;
        }

        .timeline > li {
            position: relative;
            margin-bottom: 50px;
            min-height: 50px;
        }

        .timeline > li:after, .timeline > li:before {
            display: table;
            content: ' ';
        }

        .timeline > li:after {
            clear: both;
        }

        .timeline > li .timeline-panel {
            position: relative;
            float: left;
            width: 41%;
            padding: 20px;
            text-align: right;
        }

        .timeline > li .timeline-panel:before {
            right: -15px;
            border-top: 15px solid transparent;
            border-right: 0 solid #ccc;
            border-bottom: 15px solid transparent;
            border-left: 15px solid #ccc;
            content: ' ';
            display: inline-block;
            position: absolute;
            top: 26px;
        }

        .timeline > li .timeline-panel:after {
            right: -14px;
            border-top: 14px solid transparent;
            border-right: 0 solid #fff;
            border-bottom: 14px solid transparent;
            border-left: 14px solid #fff;
            content: ' ';
            display: inline-block;
            position: absolute;
            top: 27px;
        }

        .timeline > li .timeline-image {
            position: absolute;
            z-index: 100;
            left: 50%;
            width: 80px;
            height: 80px;
            margin-left: -40px;
            text-align: center;
            color: white;
            border: 7px solid #e9ecef;
            border-radius: 100%;
            background-color: var(--primary-color);
        }

        .timeline > li .timeline-image h4 {
            font-size: 10px;
            line-height: 14px;
            margin-top: 12px;
        }

        .timeline > li.timeline-inverted > .timeline-panel {
            float: right;
            text-align: left;
        }

        .timeline > li.timeline-inverted > .timeline-panel:before {
            right: auto;
            left: -15px;
            border-right-width: 15px;
            border-left-width: 0;
        }

        .timeline > li.timeline-inverted > .timeline-panel:after {
            right: auto;
            left: -14px;
            border-right-width: 14px;
            border-left-width: 0;
        }

        @media (max-width: 767px) {
            .timeline:before {
                left: 40px;
            }

            .timeline > li .timeline-panel {
                width: calc(100% - 90px);
                width: -moz-calc(100% - 90px);
                width: -webkit-calc(100% - 90px);
                float: right;
                text-align: left;
            }

            .timeline > li .timeline-image {
                left: 0;
                margin-left: 0;
                top: 0;
            }

            .timeline > li.timeline-inverted > .timeline-panel {
                float: right;
                text-align: left;
            }

            .timeline > li.timeline-inverted > .timeline-panel:before {
                border-right-width: 15px;
                border-left-width: 0;
            }

            .timeline > li.timeline-inverted > .timeline-panel:after {
                border-right-width: 14px;
                border-left-width: 0;
            }

            .timeline > li .timeline-panel:before,
            .timeline > li.timeline-inverted > .timeline-panel:before {
                right: auto;
                left: -15px;
                border-right-width: 15px;
                border-left-width: 0;
            }

            .timeline > li .timeline-panel:after,
            .timeline > li.timeline-inverted > .timeline-panel:after {
                right: auto;
                left: -14px;
                border-right-width: 14px;
                border-left-width: 0;
            }
        }

    /* Gallery Image Hover Effect */
    .hover-img {
        transition: all 0.3s ease;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .hover-img:hover {
        transform: scale(1.03);
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.2);
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
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>">Home</a>
                    </li>
                    <li class="nav-item active">
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

    <!-- About Header -->
    <header class="about-header">
        <div class="container">
            <h1 class="display-4">About Samara University</h1>
            <p class="lead">Academic Performance Evaluation System</p>
        </div>
    </header>

    <!-- About Section -->
    <section class="about-section bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="section-heading">Our Mission</h2>
                    <hr class="my-4">
                    <p class="lead">Samara University is committed to excellence in education and research. Our academic performance evaluation system is designed to support continuous improvement and maintain high standards across all departments.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="about-card card h-100">
                        <div class="card-body text-center">
                            <div class="about-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h4 class="card-title">Excellence in Education</h4>
                            <p class="card-text">We strive to provide the highest quality education to our students, preparing them for successful careers and lifelong learning.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="about-card card h-100">
                        <div class="card-body text-center">
                            <div class="about-icon">
                                <i class="fas fa-flask"></i>
                            </div>
                            <h4 class="card-title">Innovative Research</h4>
                            <p class="card-text">Our faculty and students engage in cutting-edge research that addresses real-world challenges and contributes to the advancement of knowledge.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="about-card card h-100">
                        <div class="card-body text-center">
                            <div class="about-icon">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <h4 class="card-title">Community Service</h4>
                            <p class="card-text">We are committed to serving our community through outreach programs, partnerships, and initiatives that promote social and economic development.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Colleges Section -->
    <section class="about-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="section-heading">Our Academic Colleges</h2>
                    <hr class="my-4">
                    <p class="lead">Samara University offers diverse academic programs through specialized colleges</p>
                </div>
            </div>

            <!-- College of Natural Science -->
            <div class="row align-items-center mb-5">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="text-center">
                        <?php if (file_exists(BASE_PATH . '/assets/images/public/college_natural_science.jpg')): ?>
                            <img src="<?php echo $base_url; ?>assets/images/public/college_natural_science.jpg" alt="College of Natural Science" class="img-fluid rounded shadow-sm" style="max-width: 250px;">
                        <?php else: ?>
                            <div class="bg-primary-very-light d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 200px; height: 200px;">
                                <i class="fas fa-flask fa-4x text-primary"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-8">
                    <h3 class="mb-3">College of Natural Science</h3>
                    <p class="lead">The College of Natural Science is dedicated to advancing knowledge in fundamental sciences.</p>
                    <p>Our college offers comprehensive programs in Physics, Chemistry, Biology, Mathematics, and Statistics. With state-of-the-art laboratories and experienced faculty, we provide students with both theoretical knowledge and practical skills needed for scientific research and innovation.</p>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-graduation-cap text-primary mr-2"></i> Departments</h5>
                            <ul>
                                <li>Physics</li>
                                <li>Chemistry</li>
                                <li>Biology</li>
                                <li>Mathematics</li>
                                <li>Statistics</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-flask text-primary mr-2"></i> Research Areas</h5>
                            <ul>
                                <li>Environmental Science</li>
                                <li>Biotechnology</li>
                                <li>Applied Mathematics</li>
                                <li>Computational Physics</li>
                                <li>Analytical Chemistry</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- College of Dry Land Agriculture -->
            <div class="row align-items-center mb-5">
                <div class="col-lg-8 order-lg-1 order-2">
                    <h3 class="mb-3">College of Dry Land Agriculture</h3>
                    <p class="lead">Specializing in agricultural techniques and research for arid and semi-arid regions.</p>
                    <p>The College of Dry Land Agriculture focuses on developing sustainable agricultural practices suitable for dry land ecosystems. Our programs combine traditional knowledge with modern technology to address challenges in food security, natural resource management, and rural development in arid regions.</p>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-seedling text-primary mr-2"></i> Departments</h5>
                            <ul>
                                <li>Plant Science</li>
                                <li>Animal Science</li>
                                <li>Natural Resource Management</li>
                                <li>Agricultural Economics</li>
                                <li>Rural Development</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-leaf text-primary mr-2"></i> Research Areas</h5>
                            <ul>
                                <li>Drought-Resistant Crops</li>
                                <li>Water Conservation</li>
                                <li>Sustainable Farming</li>
                                <li>Livestock Management</li>
                                <li>Soil Conservation</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0 order-lg-2 order-1">
                    <div class="text-center">
                        <?php if (file_exists(BASE_PATH . '/assets/images/public/college_dry_land_agriculture.jpg')): ?>
                            <img src="<?php echo $base_url; ?>assets/images/public/college_dry_land_agriculture.jpg" alt="College of Dry Land Agriculture" class="img-fluid rounded shadow-sm" style="max-width: 250px;">
                        <?php else: ?>
                            <div class="bg-primary-very-light d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 200px; height: 200px;">
                                <i class="fas fa-seedling fa-4x text-primary"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- College of Business and Economics -->
            <div class="row align-items-center">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="text-center">
                        <?php if (file_exists(BASE_PATH . '/assets/images/public/college_business_economics.jpg')): ?>
                            <img src="<?php echo $base_url; ?>assets/images/public/college_business_economics.jpg" alt="College of Business and Economics" class="img-fluid rounded shadow-sm" style="max-width: 250px;">
                        <?php else: ?>
                            <div class="bg-primary-very-light d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 200px; height: 200px;">
                                <i class="fas fa-chart-line fa-4x text-primary"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-8">
                    <h3 class="mb-3">College of Business and Economics</h3>
                    <p class="lead">Preparing future business leaders and economic analysts through quality education.</p>
                    <p>The College of Business and Economics offers comprehensive programs that blend theoretical knowledge with practical skills. Our curriculum is designed to develop critical thinking, problem-solving abilities, and ethical leadership necessary for success in the dynamic business environment.</p>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-briefcase text-primary mr-2"></i> Departments</h5>
                            <ul>
                                <li>Business Administration</li>
                                <li>Economics</li>
                                <li>Accounting and Finance</li>
                                <li>Management</li>
                                <li>Marketing</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-chart-bar text-primary mr-2"></i> Focus Areas</h5>
                            <ul>
                                <li>Entrepreneurship</li>
                                <li>Financial Analysis</li>
                                <li>Economic Development</li>
                                <li>Marketing Strategy</li>
                                <li>Business Ethics</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Evaluation System Section -->
    <section class="about-section bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="section-heading">Academic Performance Evaluation System</h2>
                    <hr class="my-4">
                    <p class="lead">Our comprehensive evaluation system is designed to assess and improve the performance of academic staff, departments, and colleges.</p>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="<?php echo $base_url; ?>assets/images/evaluation-system.jpg" alt="Evaluation System" class="img-fluid rounded shadow">
                </div>
                <div class="col-lg-6">
                    <h3>Key Features</h3>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-check-circle text-primary fa-2x"></i>
                                </div>
                                <div>
                                    <h5>Comprehensive Evaluation</h5>
                                    <p>Multi-dimensional evaluation system that captures performance across various criteria and metrics.</p>
                                </div>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-chart-line text-primary fa-2x"></i>
                                </div>
                                <div>
                                    <h5>Detailed Analytics</h5>
                                    <p>Gain insights through detailed reports and analytics to track progress and identify areas for improvement.</p>
                                </div>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-users-cog text-primary fa-2x"></i>
                                </div>
                                <div>
                                    <h5>Role-Based Access</h5>
                                    <p>Secure role-based access control ensures that users can only access information relevant to their position.</p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-sync-alt text-primary fa-2x"></i>
                                </div>
                                <div>
                                    <h5>Continuous Improvement</h5>
                                    <p>Regular evaluation cycles promote ongoing improvement and excellence in academic performance.</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- History Timeline Section -->
    <section class="about-section bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="section-heading">Our History</h2>
                    <hr class="my-4">
                    <p class="lead">Samara University has a rich history of academic excellence and innovation.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <ul class="timeline">
                        <li>
                            <div class="timeline-image">
                                <h4>2000<br>Founding</h4>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4>2000</h4>
                                    <h4 class="subheading">University Founding</h4>
                                </div>
                                <div class="timeline-body">
                                    <p class="text-muted">Samara University was established with a vision to provide quality education and research opportunities.</p>
                                </div>
                            </div>
                        </li>
                        <li class="timeline-inverted">
                            <div class="timeline-image">
                                <h4>2005<br>Expansion</h4>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4>2005</h4>
                                    <h4 class="subheading">Campus Expansion</h4>
                                </div>
                                <div class="timeline-body">
                                    <p class="text-muted">The university expanded its campus and facilities to accommodate growing student enrollment.</p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="timeline-image">
                                <h4>2010<br>Programs</h4>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4>2010</h4>
                                    <h4 class="subheading">New Academic Programs</h4>
                                </div>
                                <div class="timeline-body">
                                    <p class="text-muted">Introduction of new academic programs and research initiatives to meet the evolving needs of students and society.</p>
                                </div>
                            </div>
                        </li>
                        <li class="timeline-inverted">
                            <div class="timeline-image">
                                <h4>2015<br>Research</h4>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4>2015</h4>
                                    <h4 class="subheading">Research Excellence</h4>
                                </div>
                                <div class="timeline-body">
                                    <p class="text-muted">Establishment of research centers and partnerships with industry and other academic institutions.</p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="timeline-image">
                                <h4>2020<br>Digital</h4>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4>2020</h4>
                                    <h4 class="subheading">Digital Transformation</h4>
                                </div>
                                <div class="timeline-body">
                                    <p class="text-muted">Implementation of digital systems, including the Academic Performance Evaluation System, to enhance efficiency and effectiveness.</p>
                                </div>
                            </div>
                        </li>
                        <li class="timeline-inverted">
                            <div class="timeline-image bg-primary">
                                <h4>Be Part<br>Of Our<br>Story!</h4>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Campus Gallery Section -->
    <section class="about-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="section-heading">Campus Gallery</h2>
                    <hr class="my-4">
                    <p class="lead">Explore our beautiful campus and facilities through these images</p>
                </div>
            </div>
            <div class="row">
                <!-- Gallery Image 1 -->
                <div class="col-md-4 mb-4">
                    <?php if (file_exists(BASE_PATH . '/assets/images/public/campus1.jpg')): ?>
                        <img src="<?php echo $base_url; ?>assets/images/public/campus1.jpg" alt="Campus Building" class="img-fluid rounded shadow-sm hover-img">
                    <?php else: ?>
                        <div class="bg-primary-very-light d-flex align-items-center justify-content-center rounded" style="height: 250px;">
                            <i class="fas fa-image fa-3x text-primary"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gallery Image 2 -->
                <div class="col-md-4 mb-4">
                    <?php if (file_exists(BASE_PATH . '/assets/images/public/campus2.jpg')): ?>
                        <img src="<?php echo $base_url; ?>assets/images/public/campus2.jpg" alt="Library" class="img-fluid rounded shadow-sm hover-img">
                    <?php else: ?>
                        <div class="bg-primary-very-light d-flex align-items-center justify-content-center rounded" style="height: 250px;">
                            <i class="fas fa-image fa-3x text-primary"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gallery Image 3 -->
                <div class="col-md-4 mb-4">
                    <?php if (file_exists(BASE_PATH . '/assets/images/public/campus3.jpg')): ?>
                        <img src="<?php echo $base_url; ?>assets/images/public/campus3.jpg" alt="Laboratory" class="img-fluid rounded shadow-sm hover-img">
                    <?php else: ?>
                        <div class="bg-primary-very-light d-flex align-items-center justify-content-center rounded" style="height: 250px;">
                            <i class="fas fa-image fa-3x text-primary"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gallery Image 4 -->
                <div class="col-md-4 mb-4">
                    <?php if (file_exists(BASE_PATH . '/assets/images/public/campus4.jpg')): ?>
                        <img src="<?php echo $base_url; ?>assets/images/public/campus4.jpg" alt="Lecture Hall" class="img-fluid rounded shadow-sm hover-img">
                    <?php else: ?>
                        <div class="bg-primary-very-light d-flex align-items-center justify-content-center rounded" style="height: 250px;">
                            <i class="fas fa-image fa-3x text-primary"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gallery Image 5 -->
                <div class="col-md-4 mb-4">
                    <?php if (file_exists(BASE_PATH . '/assets/images/public/campus5.jpg')): ?>
                        <img src="<?php echo $base_url; ?>assets/images/public/campus5.jpg" alt="Student Center" class="img-fluid rounded shadow-sm hover-img">
                    <?php else: ?>
                        <div class="bg-primary-very-light d-flex align-items-center justify-content-center rounded" style="height: 250px;">
                            <i class="fas fa-image fa-3x text-primary"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gallery Image 6 -->
                <div class="col-md-4 mb-4">
                    <?php if (file_exists(BASE_PATH . '/assets/images/public/campus6.jpg')): ?>
                        <img src="<?php echo $base_url; ?>assets/images/public/campus6.jpg" alt="Sports Facility" class="img-fluid rounded shadow-sm hover-img">
                    <?php else: ?>
                        <div class="bg-primary-very-light d-flex align-items-center justify-content-center rounded" style="height: 250px;">
                            <i class="fas fa-image fa-3x text-primary"></i>
                        </div>
                    <?php endif; ?>
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

<?php

include("../config/database.php");
include("../controller/traitement.php");

$popularDocuments = getPopularDocuments($cnx); 
$featuredComments = getFeaturedComments($cnx);

$popularDocuments = array_slice($popularDocuments, 0, 5);
$featuredComments = array_slice($featuredComments, 0, 4);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSB7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>KnowShare-home</title>
    <script src="home.js"></script>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <header class="navbar">
        <img src="../assets/edulogo.jpeg" alt="KnowShare Logo" class="navbar-logo">
        <nav class="top-nav">
            <a href="home.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="#contact">Contact</a>
        </nav>
        <button class="login-btn" onclick="window.location.href='login.php';">Login</button>
    </header>

    <section class="hero">
        <video class="hero-video" autoplay muted loop poster="https://images.unsplash.com/photo-1523240795612-9a054b0db644">
            <source src="https://assets.mixkit.co/videos/preview/mixkit-students-walking-in-a-university-4516-large.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Learn Smarter, Grow Faster</h1>
            <p>Explore a world of knowledge with expertly crafted courses, interactive communities, and cutting-edge tools designed to empower your learning journey.</p>
            <a href="login.php" class="cta-btn">Join for Free</a>
        </div>
    </section>

    <section class="stats">
        <div class="stat-item"><strong>12k+</strong><span>Fresh Graduates</span></div>
        <div class="stat-item"><strong>9+</strong><span>Years of Experience</span></div>
        <div class="stat-item"><strong>358+</strong><span>Excellence Awards</span></div>
        <div class="stat-item"><strong>47+</strong><span>Brand Partners</span></div>
    </section>

    <div class="text">
        <h3>Popular Courses</h3>
    </div>
    <div class="feature" id="courses">
    <?php
    if (!empty($popularDocuments)) {
        foreach ($popularDocuments as $doc) {
            $imageUrl = "https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=80"; // Replace with dynamic image if available
            $docPath = htmlspecialchars($doc['path']);
            $docTitle = htmlspecialchars($doc['title']);
            echo '
            <div class="card" style="width: 18rem;" onclick="openDocument(\'' . $docPath . '\')">
                <img src="' . $imageUrl . '" class="card-img-top" alt="' . $docTitle . '">
                <div class="card-body">
                    <p class="card-text"><a href="#" onclick="openDocument(\'' . $docPath . '\')">' . $docTitle . '</a></p>
                </div>
            </div>';
        }
    } else {
        echo '<p>No popular documents available at the moment.</p>';
    }
    ?>
    </div>

    <div class="review">
        <h3>Best Review</h3>
        <p>Hear from our students—their trust keeps us motivated.</p>
        <div class="review-cards">
        <?php
        if (!empty($featuredComments)) {
            foreach ($featuredComments as $comment) {
                $commentContent = htmlspecialchars($comment['content']);
                $commentAuthor = !empty($comment['nom']) ? htmlspecialchars($comment['nom']) : 'Anonymous';
                echo '
                <div class="review-card">
                    <div class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p>"' . $commentContent . '"</p>
                    <h4>– ' . $commentAuthor . '</h4>
                </div>';
            }
        } else {
            echo '<p>No featured comments available at the moment.</p>';
        }
        ?>
        </div>
    </div>

    <footer class="footer" id="contact">
        <div class="footer-contact">
            <div class="footer-logo">
                <img src="../assets/edulogo.jpg" alt="KnowShare Logo" class="footer-logo-img">
            </div>
            <div class="footer-info">
                <h3>Contact Us</h3>
                <p><i class="fa fa-phone"></i> +216 96 657 248</p>
                <p><i class="fa fa-envelope"></i> Knowsharecontact@gmail.com</p>
            </div>
        </div>
        <div class="footer-social">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <a href="#"><i class="fa-brands fa-facebook"></i></a>
                <a href="#"><i class="fa-brands fa-twitter"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-linkedin"></i></a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 KnowShare. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
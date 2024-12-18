<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #00ff9d;
            --secondary-color: #1a1a1a;
            --accent-color: #00cc7d;
            --text-color: black;
            --dark-bg: #121212;
            --darker-bg: #0a0a0a;
            --card-bg: #1e1e1e;
            --white: #ffffff;
            --hover-bg: #2a2a2a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: futura, sans-serif;
            line-height: 1.6;
            color: black;
            background-color: #fef5e7;
        }

        /* Navigation */
        .navbar {
            background-color: #fef5e7;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 2px solid black;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: black;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links a {
            color: black;
            text-decoration: none;
            margin-left: 2rem;
            transition: color 0.3s;
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            background-color: #fef5e7;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: black;
        }

        .hero-content {
            max-width: 800px;
            padding: 0 2rem;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: black;
        }

        .cta-btn {
            background-color: black;
            color: #fef5e7;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
        }

        /* Features Section */
        .features {
            position: relative;
            margin: -180px;
            background-color: black;
            padding: 5rem 2rem;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #fef5e7;
            z-index: 0;
            transform: translateY(-50%);
        }

        .features-container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .feature-card {
            padding: 2rem;
            background-color: #fef5e7;
            border: 2px solid black;
            border-radius: 8px;
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
            cursor: pointer;
            animation: initialFadeInUp 0.6s ease forwards;
            animation-fill-mode: both;
            transform: translateY(0);
            transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1),
                box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: black;
            margin-bottom: 1rem;
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .feature-card:hover i {
            transform: scale(1.3) rotate(360deg);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #fef5e7, #fef5e7);
            opacity: 0;
            z-index: -1;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover::before {
            opacity: 0.1;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: black;
            transition: color 0.3s ease;
        }

        .feature-card p {
            color: black;
            line-height: 1.6;
            transition: color 0.3s ease;
        }

        /* Animation */
        @keyframes initialFadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-card:nth-child(1) {
            animation-delay: 0.2s;
        }

        .feature-card:nth-child(2) {
            animation-delay: 0.4s;
        }

        .feature-card:nth-child(3) {
            animation-delay: 0.6s;
        }

        /* Membership Benefits Styles */
        /* Membership Benefits Styles */
        .membership {
            margin-top: 50px;
        }

        .benefit-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .benefit-card {
            background-color: black;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            transition: transform 0.3s ease;
            color: #fef5e7;
        }

        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .benefit-card i {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #fef5e7;
        }

        .benefit-card h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #fef5e7;
        }

        .benefit-card p {
            font-size: 0.9rem;
            color: rgba(254, 245, 231, 0.8);
            line-height: 1.6;
        }

        .join-cta button {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .join-cta button:hover {
            transform: translateY(-2px);
        }

        /* Responsive Design Addition */
        @media (max-width: 1024px) {
            .membership .benefit-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .membership .benefit-cards {
                grid-template-columns: 1fr;
            }

            .join-cta div {
                flex-direction: column;
            }
        }

        /* Footer */
        .footer {
            background-color: black;
            color: #fef5e7;
            padding: 3rem 0 1rem;
            text-align: center;
        }


        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .features-container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">Classical Music Hub</div>
            <div class="nav-links">
                <a href="auth/login.php">Sign In</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <h1>Discover the World of Classical Music</h1>
            <p>Explore the lives and works of Mozart, Beethoven, Bach, and more</p>
            <br>
            <button class="cta-btn" onclick="window.location.href='auth/login.php'">Start Exploring</button>
        </div>
    </header>

    <!-- Features Section -->
    <section class="features" style="margin: -180px; border-bottom: 2px #fef5e7 solid;">
        <div class="features-container">
            <div class="feature-card">
                <i class="fas fa-music"></i>
                <h3>Extensive Library</h3>
                <p>Access thousands of classical compositions from renowned composers</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-clock"></i>
                <h3>Interactive Timeline</h3>
                <p>Explore the rich history of classical music through our interactive timeline</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-play-circle"></i>
                <h3>Custom Player</h3>
                <p>Listen to high-quality recordings with our specialized music player</p>
            </div>
        </div>
    </section>
    <!-- Membership Benefits Section -->
    <section class="membership" style="background-color: #fef5e7; color: black; padding: 6rem 2rem; margin-top: 8rem;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: black;">Join Our Musical
                Journey</h2>
            <div class="benefit-cards">
                <div class="benefit-card">
                    <i class="fas fa-headphones-alt"></i>
                    <h3>HD Streaming</h3>
                    <p>Experience crystal-clear audio quality with our high-definition streaming service</p>
                </div>

                <div class="benefit-card">
                    <i class="fas fa-book-open"></i>
                    <h3>Sheet Music</h3>
                    <p>Access original sheet music from the greatest classical compositions</p>
                </div>

                <div class="benefit-card">
                    <i class="fas fa-users"></i>
                    <h3>Community</h3>
                    <p>Connect with fellow classical music enthusiasts worldwide</p>
                </div>

                <div class="benefit-card">
                    <i class="fas fa-star"></i>
                    <h3>Exclusive Content</h3>
                    <p>Gain access to rare recordings and expert commentaries</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="join-cta" style="padding: 6rem 2rem; text-align: center; background-color: #fef5e7;">
        <div style="max-width: 800px; margin: 0 auto;">
            <h2 style="font-size: 2.8rem; margin-bottom: 1.5rem;">Ready to Begin?</h2>
            <p style="font-size: 1.2rem; margin-bottom: 2rem;">Join thousands of classical music lovers and start your
                musical journey today</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button class="cta-btn" style="background-color: black; color: #fef5e7;"
                    onclick="window.location.href='auth/choose_role.php'">Create Account</button>
                <button class="cta-btn" style="background-color: transparent; color: black; border: 2px solid black;"
                    onclick="window.location.href='auth/login.php'">Learn More</button>
            </div>
            <p style="margin-top: 2rem; font-size: 0.9rem;">No credit card required • Free 30-day trial</p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 Classical Music Hub. All rights reserved.</p>
    </footer>
</body>

</html>
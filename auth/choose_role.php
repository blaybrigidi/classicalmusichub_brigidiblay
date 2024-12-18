<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Account Type - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: futura, sans-serif;
            background: black;
            color: #fef5e7;
            min-height: 100vh;
            display: flex;
            perspective: 1000px;
            overflow-x: hidden;
        }

        .split-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .split {
            flex: 1;
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: all 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .split:hover {
            background: rgba(254, 245, 231, 0.05);
            transform: scale(1.02);
        }

        .split::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #fef5e7;
            transform: scaleX(0);
            transition: transform 0.5s ease;
        }

        .split:hover::before {
            transform: scaleX(1);
        }

        .content-wrapper {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s forwards;
        }

        .icon {
            font-size: 3rem;
            margin-bottom: 2rem;
            color: #fef5e7;
            transition: all 0.5s ease;
            animation: float 3s ease-in-out infinite;
        }

        .split:hover .icon {
            transform: rotateY(360deg);
        }

        h2 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            color: #fef5e7;
            opacity: 0;
            animation: fadeInUp 0.8s 0.1s forwards;
        }

        h3 {
            font-size: 3.5rem;
            margin-bottom: 2rem;
            color: #fef5e7;
            opacity: 0;
            animation: fadeInUp 0.8s 0.2s forwards;
        }

        .features-list {
            list-style: none;
            margin-bottom: 3rem;
        }

        .features-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.1rem;
            opacity: 0;
            transform: translateX(-20px);
            animation: fadeInLeft 0.5s forwards;
        }

        .features-list li:nth-child(1) { animation-delay: 0.3s; }
        .features-list li:nth-child(2) { animation-delay: 0.4s; }
        .features-list li:nth-child(3) { animation-delay: 0.5s; }
        .features-list li:nth-child(4) { animation-delay: 0.6s; }

        .features-list li::before {
            content: "•";
            color: #fef5e7;
            transition: transform 0.3s ease;
        }

        .features-list li:hover::before {
            transform: scale(1.5);
        }

        .register-button {
            display: inline-block;
            background: #fef5e7;
            color: black;
            padding: 1rem;
            width: 100%;
            border: none;
            text-align: center;
            text-decoration: none;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            opacity: 0;
            animation: fadeIn 0.5s 0.7s forwards;
        }

        .register-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            transition: transform 0.5s ease;
            transform: skewX(-15deg);
        }

        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(254, 245, 231, 0.2);
        }

        .register-button:hover::before {
            transform: translateX(200%) skewX(-15deg);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .split-container {
                flex-direction: column;
            }
            .split {
                padding: 2rem;
                min-height: 100vh;
            }
            .icon {
                font-size: 2.5rem;
            }
            h2 {
                font-size: 2.5rem;
            }
            h3 {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="split-container">
        <div class="split">
            <div class="content-wrapper">
                <i class="fas fa-music icon"></i>
                <h2>Music</h2>
                <h3>Enthusiast</h3>
                <ul class="features-list">
                    <li>Create Personal Playlists</li>
                    <li>Access Full Music Library</li>
                    <li>Join Community Discussions</li>
                    <li>Follow Favorite Composers</li>
                </ul>
                <a href="./register.php?type=regular" class="register-button">Continue as Enthusiast</a>
            </div>
        </div>
        
        <div class="split">
            <div class="content-wrapper">
                <i class="fas fa-chalkboard-teacher icon"></i>
                <h2>Music</h2>
                <h3>Educator</h3>
                <ul class="features-list">
                    <li>Create Student Groups</li>
                    <li>Access Teaching Materials</li>
                    <li>Track Student Progress</li>
                    <li>Educational Resources</li>
                </ul>
                <a href="./register.php?type=educator" class="register-button">Continue as Educator</a>
            </div>
        </div>
    </div>
</body>
</html>
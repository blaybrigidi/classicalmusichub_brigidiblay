<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

$type = isset($_GET['type']) ? $_GET['type'] : '';
if (!in_array($type, ['regular', 'educator'])) {
    header('Location: choose_role.php');
    exit();
}

$title = ($type === 'regular') ? 'Music Enthusiast' : 'Music Educator';
$error = isset($_SESSION['registration_error']) ? $_SESSION['registration_error'] : '';
unset($_SESSION['registration_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as <?php echo htmlspecialchars($title); ?> - Classical Music Hub</title>
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
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .registration-container {
            width: 100%;
            max-width: 500px;
            background: rgba(254, 245, 231, 0.05);
            padding: 2rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.5s ease;
        }

        .steps {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border: 2px solid #fef5e7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 1rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step.active {
            background: #fef5e7;
            color: black;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            opacity: 0;
            animation: fadeInUp 0.5s ease forwards;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            background: rgba(254, 245, 231, 0.1);
            border: 1px solid rgba(254, 245, 231, 0.2);
            border-radius: 4px;
            color: #fef5e7;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #fef5e7;
            background: rgba(254, 245, 231, 0.15);
        }

        textarea {
            width: 100%;
            padding: 0.8rem;
            background: rgba(254, 245, 231, 0.1);
            border: 1px solid rgba(254, 245, 231, 0.2);
            border-radius: 4px;
            color: #fef5e7;
            min-height: 100px;
            resize: vertical;
        }

        .composer-preferences {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .composer-preferences label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .composer-preferences input[type="checkbox"] {
            width: auto;
        }

        .error-message {
            color: #ff6b6b;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
        }

        .error-message:not(:empty) {
            padding: 0.5rem;
            border-radius: 4px;
            background: rgba(255, 107, 107, 0.1);
            animation: fadeIn 0.3s ease;
            display: block;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            min-width: 120px;
        }

        .btn-next {
            background: #fef5e7;
            color: black;
        }

        .btn-back {
            background: transparent;
            color: #fef5e7;
            border: 1px solid #fef5e7;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(254, 245, 231, 0.2);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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

        .form-group input.error {
            border-color: #ff6b6b;
            background: rgba(255, 107, 107, 0.1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group input.valid {
            border-color: #51cf66;
            background: rgba(81, 207, 102, 0.1);
        }
    </style>
</head>

<body>
    <div class="registration-container">
        <div class="steps">
            <div class="step active">1</div>
            <div class="step">2</div>
            <div class="step">3</div>
        </div>

        <div class="form-header">
            <h2>Create Your Account</h2>
            <p class="step-title">Basic Information</p>
        </div>

        <?php if ($error): ?>
            <div class="error-alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div id="error-message" class="error-message" style="display: none;"></div>

        <form id="registrationForm" action="process_registration.php" method="POST" enctype="multipart/form-data"
            onsubmit="return handleRegistration(event)">
            <input type="hidden" name="role" value="<?php echo $type === 'regular' ? 'enthusiast' : 'educator'; ?>">
            <div class="form-step active" data-step="1">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div class="error-message"></div>
                </div>
            </div>

            <!-- Step 2: Profile Information -->
            <div class="form-step" data-step="2">
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="4"></textarea>
                </div>
            </div>

            <!-- Step 3: Composer Preferences -->
            <div class="form-step" data-step="3">
                <div class="form-group">
                    <label>Favorite Composers</label>
                    <div class="composer-preferences">
                        <label><input type="checkbox" name="composer_preferences[]" value="mozart"> Mozart</label>
                        <label><input type="checkbox" name="composer_preferences[]" value="beethoven"> Beethoven</label>
                        <label><input type="checkbox" name="composer_preferences[]" value="bach"> Bach</label>
                        <label><input type="checkbox" name="composer_preferences[]" value="chopin"> Chopin</label>
                        <label><input type="checkbox" name="composer_preferences[]" value="tchaikovsky">
                            Tchaikovsky</label>
                        <label><input type="checkbox" name="composer_preferences[]" value="debussy"> Debussy</label>
                        <label><input type="checkbox" name="composer_preferences[]" value="brahms"> Brahms</label>
                        <label><input type="checkbox" name="composer_preferences[]" value="schubert"> Schubert</label>
                    </div>
                </div>
            </div>

            <div class="buttons">
                <button type="button" class="btn btn-back" onclick="previousStep()">Back</button>
                <button type="button" class="btn btn-next" onclick="nextStep()">Next</button>
            </div>
        </form>
    </div>

    <script>
        const form = document.getElementById('registrationForm');
        const steps = document.querySelectorAll('.form-step');
        const stepIndicators = document.querySelectorAll('.step');
        const stepTitle = document.querySelector('.step-title');
        let currentStep = 1;

        const stepTitles = {
            1: 'Basic Information',
            2: 'Profile Setup',
            3: 'Musical Preferences'
        };

        const validationRules = {
            1: async () => {
                let isValid = true;
                const username = document.getElementById('username').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                clearErrors(); // Clear any existing errors first

                // Basic validation
                if (!username || username.length < 3) {
                    showError('username', 'Username must be at least 3 characters');
                    isValid = false;
                }

                if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    showError('email', 'Valid email is required');
                    isValid = false;
                }

                if (password.length < 8) {
                    showError('password', 'Password must be at least 8 characters');
                    isValid = false;
                }

                if (password !== confirmPassword) {
                    showError('confirm_password', 'Passwords do not match');
                    isValid = false;
                }

                // Only check availability if basic validation passed
                if (isValid) {
                    try {
                        const response = await fetch('check_availability.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                username: username,
                                email: email
                            })
                        });
                        const data = await response.json();

                        if (!data.available) {
                            showError(data.field, data.message);
                            isValid = false;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        isValid = false;
                    }
                }

                return isValid;
            },
            2: () => true,
            3: () => true
        };

        async function nextStep() {
            clearErrors();
            const isValid = await validationRules[currentStep]();

            if (isValid) {
                if (currentStep === 3) {
                    form.submit();
                } else {
                    currentStep++;
                    updateForm();
                }
            }
        }

        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                updateForm();
            } else {
                window.location.href = 'choose_role.php';
            }
        }

        function updateForm() {
            steps.forEach(step => {
                step.classList.remove('active');
                if (step.dataset.step == currentStep) {
                    step.classList.add('active');
                }
            });

            stepIndicators.forEach(indicator => {
                indicator.classList.remove('active');
                if (indicator.textContent == currentStep) {
                    indicator.classList.add('active');
                }
            });

            stepTitle.textContent = stepTitles[currentStep];

            const nextBtn = document.querySelector('.btn-next');
            nextBtn.textContent = currentStep === 3 ? 'Create Account' : 'Next';

            document.querySelector('.btn-back').style.visibility =
                currentStep === 1 ? 'hidden' : 'visible';
        }

        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errorDiv = field.nextElementSibling;
            field.classList.add('error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(error => {
                error.style.display = 'none';
            });
            document.querySelectorAll('.error').forEach(field => {
                field.classList.remove('error');
            });
        }

        function handleRegistration(event) {
            event.preventDefault();
            const form = document.getElementById('registrationForm');
            const errorDiv = document.getElementById('error-message');

            fetch('register_handler.php', {
                method: 'POST',
                body: new FormData(form)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        errorDiv.textContent = data.error;
                        errorDiv.style.display = 'block';
                        errorDiv.style.animation = 'shake 0.5s';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorDiv.textContent = 'An unexpected error occurred. Please try again.';
                    errorDiv.style.display = 'block';
                });

            return false;
        }
    </script>
</body>

</html>
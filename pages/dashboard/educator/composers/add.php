<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../../../includes/config.php';
require_once '../../../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'educator') {
    header('Location: ../../../../auth/login.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $era = htmlspecialchars(trim($_POST['era']), ENT_QUOTES, 'UTF-8');
    $birth_date = $_POST['birth_date'];
    $death_date = !empty($_POST['death_date']) ? $_POST['death_date'] : null;
    $nationality = htmlspecialchars(trim($_POST['nationality']), ENT_QUOTES, 'UTF-8');
    $biography = htmlspecialchars(trim($_POST['biography']), ENT_QUOTES, 'UTF-8');

    if ($death_date === null) {
        $stmt = $conn->prepare("INSERT INTO composers (name, era, birth_date, nationality, biography) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $era, $birth_date, $nationality, $biography);
    } else {
        $stmt = $conn->prepare("INSERT INTO composers (name, era, birth_date, death_date, nationality, biography) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $era, $birth_date, $death_date, $nationality, $biography);
    }

    if ($stmt->execute()) {
        header('Location: manage.php');
        exit();
    } else {
        $message = "Error adding composer: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Composer - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #121212;
            --bg-light: #1e1e1e;
            --text-primary: #fef5e7;
            --text-secondary: #c4b69c;
            --accent: #8b7355;
            --border-color: #3a3a3a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Futura', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-dark) 0%, rgba(15, 15, 15, 0.95) 100%);
        }

        /* Include the same sidebar styles from manage.php */
        .sidebar {
            background: var(--bg-light);
            padding: 2rem;
            border-right: 1px solid var(--border-color);
        }

        .sidebar-logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text-primary);
            text-decoration: none;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu a {
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(254, 245, 231, 0.1);
        }

        .sidebar-menu i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            color: var(--accent);
        }

        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        /* Form specific styles */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(254, 245, 231, 0.05);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            background: rgba(254, 245, 231, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(254, 245, 231, 0.15);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn-container {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
        }

        .btn-primary {
            background: var(--accent);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #e74c3c;
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

        /* Add animation delay for form groups */
        .form-group:nth-child(1) {
            animation-delay: 0.1s;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.2s;
        }

        .form-group:nth-child(3) {
            animation-delay: 0.3s;
        }

        .form-group:nth-child(4) {
            animation-delay: 0.4s;
        }

        .form-group:nth-child(5) {
            animation-delay: 0.5s;
        }

        .form-group:nth-child(6) {
            animation-delay: 0.6s;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li><a href="../educators_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage.php" class="active"><i class="fas fa-music"></i> Manage Composers</a></li>
                <li><a href="../timeline/manage.php"><i class="fas fa-clock"></i> Timeline</a></li>
                <li><a href="../compositions/manage.php"><i class="fas fa-file-audio"></i> Compositions</a></li>
                <li><a href="../community/manage.php"><i class="fas fa-users"></i> Community</a></li>
                <li><a href="../../../settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="form-container">
                <div class="page-header">
                    <h2>Add New Composer</h2>
                    <p>Enter the details of the composer you want to add to the database</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Composer Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="era">Musical Era</label>
                        <select id="era" name="era" required>
                            <option value="">Select an era</option>
                            <option value="Medieval">Medieval</option>
                            <option value="Renaissance">Renaissance</option>
                            <option value="Baroque">Baroque</option>
                            <option value="Classical">Classical</option>
                            <option value="Romantic">Romantic</option>
                            <option value="Modern">Modern</option>
                            <option value="Contemporary">Contemporary</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="birth_date">Birth Date</label>
                        <input type="date" id="birth_date" name="birth_date" required>
                    </div>

                    <div class="form-group">
                        <label for="death_date">Death Date (leave empty if still alive)</label>
                        <input type="date" id="death_date" name="death_date">
                    </div>

                    <div class="form-group">
                        <label for="nationality">Nationality</label>
                        <input type="text" id="nationality" name="nationality" required>
                    </div>

                    <div class="form-group">
                        <label for="biography">Biography</label>
                        <textarea id="biography" name="biography" required></textarea>
                    </div>

                    <div class="btn-container">
                        <a href="manage.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Composer
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>
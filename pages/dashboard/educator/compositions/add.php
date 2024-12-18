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

// Fetch composers for the dropdown
$composers = $conn->query("SELECT composer_id, name FROM composers ORDER BY name ASC");

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
    $composer_id = !empty($_POST['composer_id']) ? (int) $_POST['composer_id'] : null;
    $period = htmlspecialchars(trim($_POST['period']), ENT_QUOTES, 'UTF-8');
    $genre = htmlspecialchars(trim($_POST['genre']), ENT_QUOTES, 'UTF-8');
    $difficulty_level = $_POST['difficulty_level'];
    $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');

    // Handle sheet music file upload
    $sheet_music_path = null;
    if (isset($_FILES['sheet_music']) && $_FILES['sheet_music']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf'];
        if (in_array($_FILES['sheet_music']['type'], $allowed_types)) {
            $filename = uniqid() . '_' . $_FILES['sheet_music']['name'];
            $upload_dir = '../../../../uploads/sheet_music/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $sheet_music_path = 'uploads/sheet_music/' . $filename;
            move_uploaded_file($_FILES['sheet_music']['tmp_name'], $upload_dir . $filename);
        } else {
            $message = "Invalid file type. Please upload a PDF file.";
            $messageType = 'error';
        }
    }

    // Handle preview file upload (optional)
    $preview_path = null;
    if (isset($_FILES['preview']) && $_FILES['preview']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['preview']['type'], $allowed_types)) {
            $filename = uniqid() . '_' . $_FILES['preview']['name'];
            $upload_dir = '../../../../uploads/previews/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $preview_path = 'uploads/previews/' . $filename;
            move_uploaded_file($_FILES['preview']['tmp_name'], $upload_dir . $filename);
        }
    }

    if (!$message) {
        $stmt = $conn->prepare("
            INSERT INTO compositions (
                title, composer_id, period, genre, difficulty_level, 
                description, sheet_music_file, preview_file
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sissssss",
            $title,
            $composer_id,
            $period,
            $genre,
            $difficulty_level,
            $description,
            $sheet_music_path,
            $preview_path
        );

        if ($stmt->execute()) {
            header('Location: manage.php');
            exit();
        } else {
            $message = "Error adding composition: " . $conn->error;
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Composition - Classical Music Hub</title>
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

        /* Sidebar styles */
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

        /* Main content styles */
        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .form-container {
            background: var(--bg-light);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .page-header p {
            color: var(--text-secondary);
        }

        .form-group {
            margin-bottom: 1.5rem;
            animation: slideIn 0.5s ease forwards;
            opacity: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            background: rgba(254, 245, 231, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(139, 115, 85, 0.2);
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(254, 245, 231, 0.05);
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            background: rgba(254, 245, 231, 0.1);
            border-color: var(--accent);
        }

        .file-upload input[type="file"] {
            position: absolute;
            left: -9999px;
        }

        .file-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
        }

        .btn-primary {
            background: var(--accent);
        }

        .btn-primary:hover {
            background: #9b8265;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(254, 245, 231, 0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: slideIn 0.3s ease;
        }

        .alert.success {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }

        .alert.error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #e74c3c;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Animation delays for form groups */
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

        .form-group:nth-child(7) {
            animation-delay: 0.7s;
        }

        .form-group:nth-child(8) {
            animation-delay: 0.8s;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Add the sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li><a href="../educators_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="../composers/manage.php"><i class="fas fa-music"></i> Manage Composers</a></li>
                <li><a href="../timeline/manage.php"><i class="fas fa-clock"></i> Timeline</a></li>
                <li><a href="manage.php" class="active"><i class="fas fa-file-audio"></i> Compositions</a></li>
                <li><a href="../community/manage.php"><i class="fas fa-users"></i> Community</a></li>
                <li><a href="../../../settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="form-container">
                <div class="page-header">
                    <h2>Add New Composition</h2>
                    <p>Upload sheet music and add composition details</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Composition Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="composer_id">Composer</label>
                        <select id="composer_id" name="composer_id">
                            <option value="">Select a composer</option>
                            <?php while ($composer = $composers->fetch_assoc()): ?>
                                <option value="<?php echo $composer['composer_id']; ?>">
                                    <?php echo htmlspecialchars($composer['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="period">Period</label>
                        <select id="period" name="period" required>
                            <option value="">Select a period</option>
                            <option value="Medieval">Medieval</option>
                            <option value="Renaissance">Renaissance</option>
                            <option value="Baroque">Baroque</option>
                            <option value="Classical">Classical</option>
                            <option value="Romantic">Romantic</option>
                            <option value="20th Century">20th Century</option>
                            <option value="Contemporary">Contemporary</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <input type="text" id="genre" name="genre" required>
                    </div>

                    <div class="form-group">
                        <label for="difficulty_level">Difficulty Level</label>
                        <select id="difficulty_level" name="difficulty_level" required>
                            <option value="">Select difficulty level</option>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Professional">Professional</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Sheet Music (PDF)</label>
                        <div class="file-upload">
                            <label for="sheet_music" class="file-upload-label">
                                <i class="fas fa-file-pdf"></i>
                                <span>Choose PDF file</span>
                            </label>
                            <input type="file" id="sheet_music" name="sheet_music" accept=".pdf" required>
                            <div class="file-name"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Preview Image (Optional)</label>
                        <div class="file-upload">
                            <label for="preview" class="file-upload-label">
                                <i class="fas fa-image"></i>
                                <span>Choose image file</span>
                            </label>
                            <input type="file" id="preview" name="preview" accept="image/*">
                            <div class="file-name"></div>
                        </div>
                    </div>

                    <div class="btn-container">
                        <a href="manage.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Composition
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Add file name display functionality
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function () {
                const fileName = this.files[0]?.name || 'No file chosen';
                this.closest('.file-upload').querySelector('.file-name').textContent = fileName;
            });
        });
    </script>
</body>

</html>
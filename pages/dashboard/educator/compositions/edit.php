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
$messageType = '';
$composition_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$composition_id) {
    header('Location: manage.php');
    exit();
}

// Fetch composition details
$stmt = $conn->prepare("SELECT * FROM compositions WHERE composition_id = ?");
$stmt->bind_param("i", $composition_id);
$stmt->execute();
$composition = $stmt->get_result()->fetch_assoc();

if (!$composition) {
    header('Location: manage.php');
    exit();
}

// Fetch composers for the dropdown
$composers = $conn->query("SELECT composer_id, name FROM composers ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
    $composer_id = !empty($_POST['composer_id']) ? (int) $_POST['composer_id'] : null;
    $period = htmlspecialchars(trim($_POST['period']), ENT_QUOTES, 'UTF-8');
    $genre = htmlspecialchars(trim($_POST['genre']), ENT_QUOTES, 'UTF-8');
    $difficulty_level = $_POST['difficulty_level'];
    $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');

    // Handle sheet music file upload if new file is provided
    $sheet_music_path = $composition['sheet_music_file'];
    if (isset($_FILES['sheet_music']) && $_FILES['sheet_music']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf'];
        if (in_array($_FILES['sheet_music']['type'], $allowed_types)) {
            // Delete old file if it exists
            if ($sheet_music_path && file_exists('../../../../' . $sheet_music_path)) {
                unlink('../../../../' . $sheet_music_path);
            }
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

    // Handle preview file upload if new file is provided
    $preview_path = $composition['preview_file'];
    if (isset($_FILES['preview']) && $_FILES['preview']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['preview']['type'], $allowed_types)) {
            // Delete old file if it exists
            if ($preview_path && file_exists('../../../../' . $preview_path)) {
                unlink('../../../../' . $preview_path);
            }
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
            UPDATE compositions SET 
                title = ?, composer_id = ?, period = ?, genre = ?, 
                difficulty_level = ?, description = ?, sheet_music_file = ?, 
                preview_file = ?
            WHERE composition_id = ?
        ");
        $stmt->bind_param(
            "sissssssi",
            $title,
            $composer_id,
            $period,
            $genre,
            $difficulty_level,
            $description,
            $sheet_music_path,
            $preview_path,
            $composition_id
        );

        if ($stmt->execute()) {
            $message = "Composition updated successfully";
            $messageType = 'success';
            // Refresh composition data
            $stmt = $conn->prepare("SELECT * FROM compositions WHERE composition_id = ?");
            $stmt->bind_param("i", $composition_id);
            $stmt->execute();
            $composition = $stmt->get_result()->fetch_assoc();
        } else {
            $message = "Error updating composition: " . $conn->error;
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Composition - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Include the same styles as add.php */
        :root {
            --bg-dark: #121212;
            --bg-light: #1e1e1e;
            --text-primary: #fef5e7;
            --text-secondary: #c4b69c;
            --accent: #8b7355;
            --border-color: #3a3a3a;
        }

        /* Copy all the styles from add.php */
        /* ... */
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Include your sidebar -->

        <main class="main-content">
            <div class="form-container">
                <div class="page-header">
                    <h2>Edit Composition</h2>
                    <p>Update composition details and files</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Composition Title</label>
                        <input type="text" id="title" name="title"
                            value="<?php echo htmlspecialchars($composition['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="composer_id">Composer</label>
                        <select id="composer_id" name="composer_id">
                            <option value="">Select a composer</option>
                            <?php while ($composer = $composers->fetch_assoc()): ?>
                                <?php $selected = ($composition['composer_id'] == $composer['composer_id']) ? 'selected' : ''; ?>
                                <option value="<?php echo $composer['composer_id']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($composer['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="period">Period</label>
                        <select id="period" name="period" required>
                            <option value="">Select a period</option>
                            <?php
                            $periods = ['Medieval', 'Renaissance', 'Baroque', 'Classical', 'Romantic', '20th Century', 'Contemporary'];
                            foreach ($periods as $p):
                                $selected = ($composition['period'] == $p) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $p; ?>" <?php echo $selected; ?>>
                                    <?php echo $p; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <input type="text" id="genre" name="genre"
                            value="<?php echo htmlspecialchars($composition['genre']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="difficulty_level">Difficulty Level</label>
                        <select id="difficulty_level" name="difficulty_level" required>
                            <option value="">Select difficulty level</option>
                            <?php
                            $levels = ['Beginner', 'Intermediate', 'Advanced', 'Professional'];
                            foreach ($levels as $level):
                                $selected = ($composition['difficulty_level'] == $level) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $level; ?>" <?php echo $selected; ?>>
                                    <?php echo $level; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"
                            required><?php echo htmlspecialchars($composition['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Sheet Music (PDF)</label>
                        <?php if ($composition['sheet_music_file']): ?>
                            <div class="current-file">
                                Current file: <?php echo basename($composition['sheet_music_file']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="file-upload">
                            <label for="sheet_music" class="file-upload-label">
                                <i class="fas fa-file-pdf"></i>
                                <span>Choose new PDF file</span>
                            </label>
                            <input type="file" id="sheet_music" name="sheet_music" accept=".pdf">
                            <div class="file-name"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Preview Image</label>
                        <?php if ($composition['preview_file']): ?>
                            <div class="current-file">
                                <img src="<?php echo htmlspecialchars($composition['preview_file']); ?>"
                                    alt="Current preview" style="max-width: 200px;">
                            </div>
                        <?php endif; ?>
                        <div class="file-upload">
                            <label for="preview" class="file-upload-label">
                                <i class="fas fa-image"></i>
                                <span>Choose new image</span>
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
                            <i class="fas fa-save"></i> Save Changes
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
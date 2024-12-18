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
$event_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$event_id) {
    header('Location: manage.php');
    exit();
}

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM timeline_events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    header('Location: manage.php');
    exit();
}

// Fetch composers for the dropdown
$composers = $conn->query("SELECT composer_id, name FROM composers ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');
    $event_date = $_POST['event_date'];
    $composer_id = !empty($_POST['composer_id']) ? (int) $_POST['composer_id'] : null;

    if ($composer_id) {
        $update_stmt = $conn->prepare("UPDATE timeline_events SET title = ?, description = ?, event_date = ?, composer_id = ? WHERE event_id = ?");
        $update_stmt->bind_param("sssii", $title, $description, $event_date, $composer_id, $event_id);
    } else {
        $update_stmt = $conn->prepare("UPDATE timeline_events SET title = ?, description = ?, event_date = ?, composer_id = NULL WHERE event_id = ?");
        $update_stmt->bind_param("sssi", $title, $description, $event_date, $event_id);
    }

    if ($update_stmt->execute()) {
        $message = "Event updated successfully";
        // Refresh event data
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
    } else {
        $message = "Error updating event: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Timeline Event - Classical Music Hub</title>
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

        /* Main content and form styles */
        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
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

        .form-container {
            background: var(--bg-light);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease forwards;
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
            padding: 0.8rem;
            background: rgba(254, 245, 231, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(139, 115, 85, 0.2);
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
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--text-primary);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            background: rgba(139, 115, 85, 0.9);
        }

        .btn-secondary:hover {
            background: rgba(254, 245, 231, 0.05);
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
                transform: translateY(10px);
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
    </style>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li><a href="../educators_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="../composers/manage.php"><i class="fas fa-music"></i> Manage Composers</a></li>
                <li><a href="manage.php" class="active"><i class="fas fa-clock"></i> Timeline</a></li>
                <li><a href="../compositions/manage.php"><i class="fas fa-file-audio"></i> Compositions</a></li>
                <li><a href="../community/manage.php"><i class="fas fa-users"></i> Community</a></li>
                <li><a href="../../../settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="form-container">
                <div class="page-header">
                    <h2>Edit Timeline Event</h2>
                    <p>Update the details of this historical event</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Event Title</label>
                        <input type="text" id="title" name="title"
                            value="<?php echo htmlspecialchars($event['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="event_date">Event Date</label>
                        <input type="date" id="event_date" name="event_date" value="<?php echo $event['event_date']; ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="composer_id">Related Composer (Optional)</label>
                        <select id="composer_id" name="composer_id">
                            <option value="">Select a composer</option>
                            <?php while ($composer = $composers->fetch_assoc()): ?>
                                <?php $selected = ($event['composer_id'] == $composer['composer_id']) ? 'selected' : ''; ?>
                                <option value="<?php echo $composer['composer_id']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($composer['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Event Description</label>
                        <textarea id="description" name="description"
                            required><?php echo htmlspecialchars($event['description']); ?></textarea>
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
</body>

</html>
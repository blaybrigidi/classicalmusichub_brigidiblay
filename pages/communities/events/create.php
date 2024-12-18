<?php
session_start();
require_once '../../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

$community_id = $_GET['community_id'] ?? 0;

// Check if user is a member
$check_member = $conn->prepare("
    SELECT role FROM community_members 
    WHERE community_id = ? AND user_id = ?
");
$check_member->bind_param("ii", $community_id, $_SESSION['user_id']);
$check_member->execute();
$member_result = $check_member->get_result();

if ($member_result->num_rows === 0) {
    header('Location: ../view.php?id=' . $community_id);
    exit();
}

// Add at the top of the file, after session_start()
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $event_date = $_POST['event_date'];
        $event_type = $_POST['event_type'];
        $location = trim($_POST['location']);
        $max_participants = (int) $_POST['max_participants'];

        $stmt = $conn->prepare("
            INSERT INTO community_events 
            (community_id, title, description, event_date, event_type, location, max_participants, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssssii",
            $community_id,
            $title,
            $description,
            $event_date,
            $event_type,
            $location,
            $max_participants,
            $_SESSION['user_id']
        );

        if ($stmt->execute()) {
            header('Location: ../view.php?id=' . $community_id . '&tab=events');
            exit();
        } else {
            throw new Exception("Failed to create event: " . $stmt->error);
        }
    } catch (Exception $e) {
        echo '<div class="error-message">' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Event - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #121212;
            --bg-light: #1e1e1e;
            --text-primary: #fef5e7;
            --text-secondary: #c4b69c;
            --accent: #8b7355;
            --border-color: #3a3a3a;
            --glass: rgba(254, 245, 231, 0.03);
            --glass-hover: rgba(254, 245, 231, 0.08);
            --border: rgba(254, 245, 231, 0.1);
        }

        body {
            background: linear-gradient(135deg, var(--bg-dark), #1a1a1a);
            color: var(--text-primary);
            font-family: 'Futura', sans-serif;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            background: linear-gradient(45deg, var(--text-primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .event-form {
            background: var(--glass);
            padding: 2.5rem;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 1rem;
            background: rgba(254, 245, 231, 0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(254, 245, 231, 0.08);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .datetime-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--accent), #d4b877);
            color: #000;
            border: none;
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            filter: brightness(1.1);
        }

        .btn-secondary:hover {
            border-color: var(--accent);
            background: var(--glass-hover);
        }

        .form-tip {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        .back-link {
            color: var(--text-primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .back-link:hover {
            color: var(--accent);
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .datetime-group {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Add error message styling */
        .error-message {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff4444;
            color: #ff4444;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="../view.php?id=<?php echo $community_id; ?>&tab=events" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>

        <div class="page-header">
            <h1>Create New Event</h1>
        </div>

        <form method="POST" class="event-form">
            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" required>
                <div class="form-tip">Choose a clear, descriptive title for your event</div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required></textarea>
                <div class="form-tip">Describe what your event is about, what to expect, and any requirements</div>
            </div>

            <div class="datetime-group">
                <div class="form-group">
                    <label for="event_date">Date and Time</label>
                    <input type="datetime-local" id="event_date" name="event_date" required>
                    <div class="form-tip">Select when your event will take place</div>
                </div>

                <div class="form-group">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type" required>
                        <option value="online">Online</option>
                        <option value="in-person">In Person</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                    <div class="form-tip">Choose how participants will attend</div>
                </div>
            </div>

            <div class="form-group">
                <label for="location">Location/Link</label>
                <input type="text" id="location" name="location" required>
                <div class="form-tip">Provide the venue address or online meeting link</div>
            </div>

            <div class="form-group">
                <label for="max_participants">Maximum Participants</label>
                <input type="number" id="max_participants" name="max_participants" min="0" value="0">
                <div class="form-tip">Set to 0 for unlimited participants</div>
            </div>

            <div class="btn-group">
                <a href="../view.php?id=<?php echo $community_id; ?>&tab=events" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Event
                </button>
            </div>
        </form>
    </div>
</body>

</html>
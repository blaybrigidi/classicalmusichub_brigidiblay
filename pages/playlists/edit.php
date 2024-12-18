<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$playlist_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch playlist details
$stmt = $conn->prepare("SELECT * FROM playlists WHERE playlist_id = ? AND user_id = ?");
$stmt->bind_param("ii", $playlist_id, $_SESSION['user_id']);
$stmt->execute();
$playlist = $stmt->get_result()->fetch_assoc();

if (!$playlist) {
    header("Location: ../dashboard/enthusiast/enthusiast_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $is_public = isset($_POST['is_public']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE playlists SET name = ?, description = ?, is_public = ? WHERE playlist_id = ? AND user_id = ?");
        $stmt->bind_param("ssiii", $name, $description, $is_public, $playlist_id, $_SESSION['user_id']);

        if ($stmt->execute()) {
            header("Location: view.php?id=" . $playlist_id);
            exit();
        } else {
            throw new Exception("Error updating playlist");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Playlist - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Futura', sans-serif;
            background: black;
            color: #fef5e7;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #fef5e7;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .back-button:hover {
            background: rgba(254, 245, 231, 0.1);
        }

        .form-container {
            background: rgba(254, 245, 231, 0.05);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(254, 245, 231, 0.2), transparent);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #fef5e7;
        }

        .error {
            background: rgba(255, 69, 58, 0.1);
            border: 1px solid rgba(255, 69, 58, 0.3);
            color: #ff453a;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            color: rgba(254, 245, 231, 0.7);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 1rem;
            background: rgba(254, 245, 231, 0.05);
            border: 1px solid rgba(254, 245, 231, 0.1);
            border-radius: 8px;
            color: #fef5e7;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: rgba(254, 245, 231, 0.3);
            background: rgba(254, 245, 231, 0.08);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
        }

        input[type="checkbox"] {
            appearance: none;
            width: 24px;
            height: 24px;
            border: 2px solid rgba(254, 245, 231, 0.3);
            border-radius: 6px;
            background: rgba(254, 245, 231, 0.05);
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }

        input[type="checkbox"]:checked {
            background: #fef5e7;
            border-color: #fef5e7;
        }

        input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            color: black;
            font-size: 16px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-primary {
            background: #fef5e7;
            color: black;
        }

        .btn-secondary {
            background: rgba(254, 245, 231, 0.1);
            color: #fef5e7;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(254, 245, 231, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(254, 245, 231, 0.2);
        }

        @media (max-width: 768px) {
            .container {
                padding: 2rem 1rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            h1 {
                font-size: 2rem;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>

        <h1>Edit Playlist</h1>

        <div class="form-container">
            <?php if (isset($error)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name">Playlist Name</label>
                    <input type="text" id="name" name="name" required maxlength="100"
                        value="<?php echo htmlspecialchars($playlist['name']); ?>" placeholder="Enter playlist name">
                </div>

                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description"
                        placeholder="Add a description for your playlist"><?php echo htmlspecialchars($playlist['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_public" name="is_public" <?php echo $playlist['is_public'] ? 'checked' : ''; ?>>
                        <label for="is_public">Make this playlist public</label>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="view.php?id=<?php echo $playlist_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
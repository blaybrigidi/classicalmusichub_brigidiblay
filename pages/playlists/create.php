<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO playlists (user_id, name, description, is_public) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $user_id, $name, $description, $is_public);

        if ($stmt->execute()) {
            $playlist_id = $stmt->insert_id;
            header("Location: view.php?id=" . $playlist_id);
            exit();
        } else {
            throw new Exception("Error creating playlist");
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
    <title>Create Playlist - Classical Music Hub</title>
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
            padding: 2rem;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-container {
            background: rgba(254, 245, 231, 0.05);
            padding: 2rem;
            border-radius: 8px;
        }

        h1 {
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 0.8rem;
            background: rgba(254, 245, 231, 0.1);
            border: 1px solid rgba(254, 245, 231, 0.2);
            border-radius: 4px;
            color: #fef5e7;
            font-size: 1rem;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        input[type="checkbox"] {
            width: auto;
        }

        .btn {
            display: inline-block;
            background: #fef5e7;
            color: black;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #fef5e7;
            color: #fef5e7;
        }

        .error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            color: #ff6b6b;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create New Playlist</h1>

        <div class="form-container">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name">Playlist Name</label>
                    <input type="text" id="name" name="name" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description"></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_public" name="is_public" checked>
                        <label for="is_public">Make this playlist public</label>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn">Create Playlist</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
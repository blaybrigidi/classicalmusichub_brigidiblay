<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle composition actions (delete/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $composition_id = $_POST['composition_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM compositions WHERE composition_id = ?");
            $stmt->bind_param("i", $composition_id);
            $stmt->execute();
            break;

        case 'edit':
            $title = $_POST['title'];
            $composer_id = $_POST['composer_id'];
            $difficulty_level = $_POST['difficulty_level'];
            $genre = $_POST['genre'];
            $description = $_POST['description'];

            $stmt = $conn->prepare("
    UPDATE compositions 
    SET title = ?, composer_id = ?, difficulty_level = ?, genre = ?, description = ?
    WHERE composition_id = ?
");
            $stmt->bind_param("sssiss", $title, $composer_id, $difficulty_level, $genre, $description, $composition_id);

            $stmt->bind_param("sisssi", $title, $composer_id, $year, $genre, $description, $composition_id);
            $stmt->execute();
            break;
    }
}

// Fetch compositions with pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$compositions_query = "
    SELECT c.*, 
           comp.name as composer_name,
           (SELECT COUNT(*) FROM favorites WHERE composition_id = c.composition_id) as favorite_count
    FROM compositions c
    JOIN composers comp ON c.composer_id = comp.composer_id
    ORDER BY c.title ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($compositions_query);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$compositions = $stmt->get_result();

// Get total compositions for pagination
$total_compositions = $conn->query("SELECT COUNT(*) as count FROM compositions")->fetch_assoc()['count'];
$total_pages = ceil($total_compositions / $per_page);

// Fetch all composers for the dropdown
$composers = $conn->query("SELECT composer_id, name FROM composers ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Music - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #fef5e7;
            --accent: #c9a959;
            --glass: rgba(254, 245, 231, 0.03);
            --border: rgba(254, 245, 231, 0.1);
            --glass-hover: rgba(254, 245, 231, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Futura', sans-serif;
            background: linear-gradient(135deg, #000000, #1a1a1a);
            color: var(--primary);
            min-height: 100vh;
        }

        .page-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .main-content {
            padding: 2rem;
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: var(--accent);
            font-family: 'Didot', serif;
        }

        .add-composition-btn {
            padding: 0.75rem 1.5rem;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin-bottom: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .add-composition-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .music-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            background: var(--glass);
            border-radius: 15px;
            overflow: hidden;
        }

        .music-table th,
        .music-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .music-table th {
            background: rgba(201, 169, 89, 0.1);
            color: var(--accent);
            font-weight: 500;
            font-family: 'Didot', serif;
        }

        .music-table tr:hover {
            background: var(--glass-hover);
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 0.5rem;
        }

        .btn-edit {
            background: var(--accent);
            color: var(--primary);
        }

        .btn-delete {
            background: #ff6b6b;
            color: var(--primary);
        }

        .btn-view {
            background: rgba(201, 169, 89, 0.2);
            color: var(--accent);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            background: var(--glass);
            color: var(--primary);
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .pagination a:hover,
        .pagination a.active {
            background: var(--accent);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }

        .modal-content {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 2rem;
            max-width: 600px;
            margin: 2rem auto;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: var(--primary);
            cursor: pointer;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--accent);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            background: rgba(254, 245, 231, 0.05);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--primary);
        }

        .form-group select option {
            background: #1a1a1a;
        }

        .favorite-count {
            background: rgba(201, 169, 89, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--accent);
        }
    </style>
</head>

<body>
    <div class="page-container">
        <?php include '../includes/components/admin_sidebar.php'; ?>

        <main class="main-content">
            <h1>Manage Music</h1>


            <table class="music-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Composer</th>
                        <th>Year</th>
                        <th>Genre</th>
                        <th>Favorites</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($composition = $compositions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($composition['title']); ?></td>
                            <td><?php echo htmlspecialchars($composition['composer_name']); ?></td>
                            <td><?php echo htmlspecialchars($composition['difficulty_level']); ?></td>
                            <!-- Replaced 'year' with 'difficulty_level' -->
                            <td><?php echo htmlspecialchars($composition['genre']); ?></td>
                            <td>
                                <span class="favorite-count">
                                    <i class="fas fa-heart"></i> <?php echo $composition['favorite_count']; ?>
                                </span>
                            </td>
                            <td>

                                <button class="btn btn-delete"
                                    onclick="deleteComposition(<?php echo $composition['composition_id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Edit Composition</h2>
            <form id="editForm">
                <input type="hidden" name="composition_id" id="edit_composition_id">
                <input type="hidden" name="action" value="edit">

                <div class="form-group">
                    <label for="edit_title">Title</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="edit_composer">Composer</label>
                    <select id="edit_composer" name="composer_id" required>
                        <?php while ($composer = $composers->fetch_assoc()): ?>
                            <option value="<?php echo $composer['composer_id']; ?>">
                                <?php echo htmlspecialchars($composer['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_difficulty_level">Difficulty Level</label>
                    <input type="text" id="edit_difficulty_level" name="difficulty_level" required>
                </div>


                <div class="form-group">
                    <label for="edit_genre">Genre</label>
                    <input type="text" id="edit_genre" name="genre" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="4" required></textarea>
                </div>

                <button type="submit" class="btn btn-edit">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function deleteComposition(compositionId) {
            if (confirm('Are you sure you want to delete this composition?')) {
                const form = new FormData();
                form.append('composition_id', compositionId);
                form.append('action', 'delete');

                fetch('manage_music.php', {
                    method: 'POST',
                    body: form
                }).then(() => window.location.reload());
            }
        }

        function editComposition(compositionId) {
            fetch(`get_composition.php?id=${compositionId}`)
                .then(response => response.json())
                .then(composition => {
                    document.getElementById('edit_composition_id').value = composition.composition_id;
                    document.getElementById('edit_title').value = composition.title;
                    document.getElementById('edit_composer').value = composition.composer_id;
                    document.getElementById('edit_year').value = composition.year;
                    document.getElementById('edit_genre').value = composition.genre;
                    document.getElementById('edit_description').value = composition.description;

                    document.getElementById('editModal').style.display = 'block';
                });
        }

        function viewComposition(compositionId) {
            window.location.href = `view_composition.php?id=${compositionId}`;
        }

        // Close modal when clicking the close button or outside the modal
        document.querySelector('.close-modal').onclick = function () {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function (event) {
            if (event.target == document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = 'none';
            }
        }

        // Handle form submission
        document.getElementById('editForm').onsubmit = function (e) {
            e.preventDefault();
            const form = new FormData(e.target);

            fetch('manage_music.php', {
                method: 'POST',
                body: form
            }).then(() => {
                document.getElementById('editModal').style.display = 'none';
                window.location.reload();
            });
        }
    </script>
</body>

</html>
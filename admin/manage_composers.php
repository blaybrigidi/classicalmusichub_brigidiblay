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

// Handle composer actions (delete/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $composer_id = $_POST['composer_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM composers WHERE composer_id = ?");
            $stmt->bind_param("i", $composer_id);
            $stmt->execute();
            break;

        case 'edit':
            $name = $_POST['name'];
            $era = $_POST['era'];
            $nationality = $_POST['nationality'];
            $birth_date = $_POST['birth_date'];
            $death_date = $_POST['death_date'];
            $biography = $_POST['biography'];

            $stmt = $conn->prepare("
                UPDATE composers 
                SET name = ?, era = ?, nationality = ?, birth_date = ?, death_date = ?, biography = ?
                WHERE composer_id = ?
            ");
            $stmt->bind_param("ssssssi", $name, $era, $nationality, $birth_date, $death_date, $biography, $composer_id);
            $stmt->execute();
            break;
    }
}

// Fetch composers with pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$composers_query = "
    SELECT c.*, 
           COUNT(comp.composition_id) as composition_count
    FROM composers c
    LEFT JOIN compositions comp ON c.composer_id = comp.composer_id
    GROUP BY c.composer_id
    ORDER BY c.name ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($composers_query);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$composers = $stmt->get_result();

// Get total composers for pagination
$total_composers = $conn->query("SELECT COUNT(*) as count FROM composers")->fetch_assoc()['count'];
$total_pages = ceil($total_composers / $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Composers - Admin Panel</title>
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

        .add-composer-btn {
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

        .add-composer-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .composers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .composer-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .composer-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            background: var(--glass-hover);
        }

        .composer-name {
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 1rem;
            font-family: 'Didot', serif;
        }

        .composer-info {
            margin-bottom: 1rem;
            color: rgba(254, 245, 231, 0.7);
        }

        .composer-stats {
            display: flex;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
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
        }

        .btn-edit {
            background: var(--accent);
            color: var(--primary);
        }

        .btn-delete {
            background: #ff6b6b;
            color: var(--primary);
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
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            background: rgba(254, 245, 231, 0.05);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--primary);
        }
    </style>
</head>

<body>
    <div class="page-container">
        <?php include '../includes/components/admin_sidebar.php'; ?>

        <main class="main-content">
            <h1>Manage Composers</h1>

            <a href="add_composer.php" class="add-composer-btn">
                <i class="fas fa-plus"></i> Add New Composer
            </a>

            <div class="composers-grid">
                <?php while ($composer = $composers->fetch_assoc()): ?>
                    <div class="composer-card">
                        <h2 class="composer-name"><?php echo htmlspecialchars($composer['name']); ?></h2>
                        <div class="composer-info">
                            <p><i class="fas fa-clock"></i> <?php echo htmlspecialchars($composer['era']); ?></p>
                            <p><i class="fas fa-globe"></i> <?php echo htmlspecialchars($composer['nationality']); ?></p>
                            <p><i class="fas fa-calendar"></i>
                                <?php echo date('Y', strtotime($composer['birth_date'])); ?> -
                                <?php echo date('Y', strtotime($composer['death_date'])); ?>
                            </p>
                        </div>
                        <div class="composer-stats">
                            <span><?php echo $composer['composition_count']; ?> compositions</span>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-edit" onclick="editComposer(<?php echo $composer['composer_id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-delete"
                                onclick="deleteComposer(<?php echo $composer['composer_id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

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
            <h2>Edit Composer</h2>
            <form id="editForm">
                <input type="hidden" name="composer_id" id="edit_composer_id">
                <input type="hidden" name="action" value="edit">

                <div class="form-group">
                    <label for="edit_name">Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="edit_era">Era</label>
                    <input type="text" id="edit_era" name="era" required>
                </div>

                <div class="form-group">
                    <label for="edit_nationality">Nationality</label>
                    <input type="text" id="edit_nationality" name="nationality" required>
                </div>

                <div class="form-group">
                    <label for="edit_birth_date">Birth Date</label>
                    <input type="date" id="edit_birth_date" name="birth_date" required>
                </div>

                <div class="form-group">
                    <label for="edit_death_date">Death Date</label>
                    <input type="date" id="edit_death_date" name="death_date" required>
                </div>

                <div class="form-group">
                    <label for="edit_biography">Biography</label>
                    <textarea id="edit_biography" name="biography" rows="4" required></textarea>
                </div>

                <button type="submit" class="btn btn-edit">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function deleteComposer(composerId) {
            if (confirm('Are you sure you want to delete this composer?')) {
                const form = new FormData();
                form.append('composer_id', composerId);
                form.append('action', 'delete');

                fetch('manage_composers.php', {
                    method: 'POST',
                    body: form
                }).then(() => window.location.reload());
            }
        }

        function editComposer(composerId) {
            // Fetch composer details and populate modal
            fetch(`get_composer.php?id=${composerId}`)
                .then(response => response.json())
                .then(composer => {
                    document.getElementById('edit_composer_id').value = composer.composer_id;
                    document.getElementById('edit_name').value = composer.name;
                    document.getElementById('edit_era').value = composer.era;
                    document.getElementById('edit_nationality').value = composer.nationality;
                    document.getElementById('edit_birth_date').value = composer.birth_date;
                    document.getElementById('edit_death_date').value = composer.death_date;
                    document.getElementById('edit_biography').value = composer.biography;

                    document.getElementById('editModal').style.display = 'block';
                });
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

            fetch('manage_composers.php', {
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
<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$playlist_id = isset($_GET['playlist']) ? (int)$_GET['playlist'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$composer = isset($_GET['composer']) ? (int)$_GET['composer'] : 0;

// Fetch composers for filter
$composers = $conn->query("SELECT composer_id, name FROM composers ORDER BY name");

// Build query
$query = "
    SELECT c.*, comp.name as composer_name,
           CASE WHEN pi.playlist_id IS NOT NULL THEN 1 ELSE 0 END as in_playlist
    FROM compositions c
    JOIN composers comp ON c.composer_id = comp.composer_id
    LEFT JOIN playlist_items pi ON c.composition_id = pi.composition_id AND pi.playlist_id = ?
    WHERE 1=1
";
$params = [$playlist_id];
$types = "i";

if ($search) {
    $query .= " AND (c.title LIKE ? OR comp.name LIKE ?)";
    $search = "%$search%";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

if ($composer) {
    $query .= " AND comp.composer_id = ?";
    $params[] = $composer;
    $types .= "i";
}

$query .= " ORDER BY comp.name, c.title";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$compositions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Compositions - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
body {
    font-family: 'Helvetica Neue', 'Futura', sans-serif;
    background: linear-gradient(135deg, #000000, #1a1a1a);
    color: #fef5e7;
    line-height: 1.6;
    padding: 2rem;
}

.container {
    max-width: 800px;
    margin: 0 auto;
}

h1 {
    font-size: 2.5rem;
    margin-bottom: 2rem;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 2px;
    background: linear-gradient(45deg, #fef5e7, #d4af37);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.filters {
    background: rgba(254, 245, 231, 0.08);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.search-form {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 1rem;
    align-items: center;
}

input[type="text"],
select {
    width: 100%;
    padding: 0.8rem 1.2rem;
    background: rgba(254, 245, 231, 0.1);
    border: 1px solid rgba(254, 245, 231, 0.2);
    border-radius: 8px;
    color: #fef5e7;
    font-size: 1rem;
    transition: all 0.3s ease;
}

input[type="text"]:focus,
select:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
}

.composition-item {
    background: rgba(254, 245, 231, 0.08);
    padding: 1.2rem;
    margin-bottom: 1rem;
    border-radius: 10px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.composition-item:hover {
    transform: translateY(-2px);
    border-color: rgba(212, 175, 55, 0.3);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.composition-title {
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0.3rem;
}

.composition-composer {
    font-size: 0.9rem;
    color: rgba(254, 245, 231, 0.7);
}

.add-btn {
    background: linear-gradient(45deg, #d4af37, #ffd700);
    color: black;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
}

.add-btn:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}

.add-btn:disabled {
    background: rgba(254, 245, 231, 0.2);
    color: rgba(254, 245, 231, 0.5);
}

.success-message {
    background: linear-gradient(45deg, #4CAF50, #45a049);
    color: white;
    padding: 1rem 2rem;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Loading animation for button */
.add-btn.loading {
    position: relative;
    color: transparent;
}

.add-btn.loading::after {
    content: "";
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin: -10px 0 0 -10px;
    border: 2px solid rgba(0, 0, 0, 0.3);
    border-top-color: #000;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Browse Compositions</h1>

        <div class="filters">
            <form method="GET" class="search-form">
                <input type="hidden" name="playlist" value="<?php echo $playlist_id; ?>">
                
                <input type="text" name="search" placeholder="Search compositions..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="composer">
                    <option value="">All Composers</option>
                    <?php while ($composer_row = $composers->fetch_assoc()): ?>
                        <option value="<?php echo $composer_row['composer_id']; ?>"
                                <?php echo $composer_row['composer_id'] == $composer ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($composer_row['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" class="btn">Filter</button>
            </form>
        </div>

        <ul class="composition-list">
            <?php foreach ($compositions as $composition): ?>
                <li class="composition-item">
                    <div class="composition-info">
                        <div class="composition-title">
                            <?php echo htmlspecialchars($composition['title']); ?>
                        </div>
                        <div class="composition-composer">
                            <?php echo htmlspecialchars($composition['composer_name']); ?>
                        </div>
                    </div>
                    <button class="add-btn" 
                            onclick="addToPlaylist(<?php echo $composition['composition_id']; ?>)"
                            <?php echo $composition['in_playlist'] ? 'disabled' : ''; ?>>
                        <?php echo $composition['in_playlist'] ? 'Added' : 'Add to Playlist'; ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="success-message" id="successMessage">
            Added to playlist!
        </div>
    </div>

    <script>
       function addToPlaylist(compositionId) {
    const btn = event.target;
    btn.classList.add('loading');
    
    fetch('../playlists/add_track.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `playlist_id=<?php echo $playlist_id; ?>&composition_id=${compositionId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.classList.remove('loading');
            btn.disabled = true;
            btn.textContent = 'Added';
            
            const message = document.getElementById('successMessage');
            message.style.display = 'block';
            setTimeout(() => {
                message.style.display = 'none';
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.classList.remove('loading');
    });
}
    </script>
</body>
</html>
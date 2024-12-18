<?php
if (!isset($_SESSION)) {
    session_start();
}
?>

<aside class="sidebar">
    <div class="sidebar-logo">Classical Music Hub</div>
    <ul class="sidebar-menu">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'educator'): ?>
            <li><a href="../pages/dashboard/educator/educators_dashboard.php"><i class="fas fa-home"></i>
                    Dashboard</a></li>
            <li><a href="../pages/compositions/list.php"><i class="fas fa-music"></i> Browse Music</a>
            </li>
            <li><a href="../pages/dashboard/educator/composers/manage.php"><i class="fas fa-user"></i>
                    Composers</a></li>
            <li><a href="../pages/dashboard/educator/timeline/manage.php"><i class="fas fa-clock"></i>
                    Timeline</a></li>
            <li><a href="../pages/communities/list.php"><i class="fas fa-users"></i> Communities</a></li>
            <li><a href="../pages/settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'enthusiast'): ?>
            <li><a href="../pages/dashboard/enthusiast/enthusiast_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            </li>
            <li><a href="../pages/compositions/list.php"><i class="fas fa-music"></i> Browse Music</a>
            </li>
            <li><a href="../pages/library/timeline.php"><i class="fas fa-clock"></i> Timeline</a></li>
            <li><a href="../pages/dashboard/enthusiast/playlists.php"><i class="fas fa-list"></i> My
                    Playlists</a></li>
            <li><a href="../pages/library/favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
            <li><a href="../pages/communities/list.php"><i class="fas fa-users"></i> Communities</a></li>
            <li><a href="../pages/settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php else: ?>
            <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="../pages/compositions/list.php"><i class="fas fa-music"></i> Browse Music</a>
            </li>
            <li><a href="../pages/communities/list.php"><i class="fas fa-users"></i> Communities</a></li>
            <li><a href="../auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
        <?php endif; ?>
    </ul>
</aside>
<?php
function isCurrentPage($filename)
{
    return basename($_SERVER['PHP_SELF']) == $filename;
}
?>

<aside class="sidebar">
    <div class="sidebar-logo">Classical Music Hub</div>
    <ul class="sidebar-menu">
        <li>
            <a href="../pages/dashboard/educator/educators_dashboard.php"
                class="<?php echo isCurrentPage('educators_dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="../pages/compositions/list.php" class="<?php echo isCurrentPage('list.php') ? 'active' : ''; ?>">
                <i class="fas fa-music"></i> Browse Music
            </a>
        </li>
        <li>
            <a href="../pages/library/favorites.php"
                class="<?php echo isCurrentPage('favorites.php') ? 'active' : ''; ?>">
                <i class="fas fa-heart"></i> Favorites
            </a>
        </li>
        <li>
            <a href="../pages/dashboard/educator/composers/manage.php"
                class="<?php echo isCurrentPage('manage.php') && strpos($_SERVER['PHP_SELF'], 'composers') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-edit"></i> Manage Composers
            </a>
        </li>
        <li>
            <a href="../pages/dashboard/educator/timeline/manage.php"
                class="<?php echo isCurrentPage('manage.php') && strpos($_SERVER['PHP_SELF'], 'timeline') !== false ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Manage Timeline
            </a>
        </li>
        <li>
            <a href="../pages/settings/settings.php"
                class="<?php echo isCurrentPage('settings.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
        <li>
            <a href="../auth/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</aside>
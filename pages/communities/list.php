<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';

// Fetch all communities with member count
$communities = $conn->query("
    SELECT c.*, 
           u.username as creator_name,
           COUNT(DISTINCT cm.user_id) as member_count
    FROM communities c
    LEFT JOIN users u ON c.created_by = u.user_id
    LEFT JOIN community_members cm ON c.community_id = cm.community_id
    GROUP BY c.community_id
    ORDER BY c.created_at DESC
");
?>
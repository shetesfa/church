<?php
// bulk_approve.php - Approve multiple members with category support
require_once 'config.php';

$return_page = isset($_GET['return']) ? $_GET['return'] : 'temporary.php';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

if(isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = implode(',', array_map('intval', $_POST['ids']));
    
    // Get category counts for message
    $count_result = $conn->query("SELECT category, COUNT(*) as count FROM members WHERE id IN ($ids) GROUP BY category");
    $children_count = 0;
    $youth_count = 0;
    
    while($row = $count_result->fetch_assoc()) {
        if($row['category'] == 'ህጻናት') {
            $children_count = $row['count'];
        } else {
            $youth_count = $row['count'];
        }
    }
    
    // Update status
    $conn->query("UPDATE members SET status='approved' WHERE id IN ($ids)");
    
    // Build message
    $message_parts = [];
    if($children_count > 0) {
        $message_parts[] = "🧒 $children_count ህጻናት";
    }
    if($youth_count > 0) {
        $message_parts[] = "👦 $youth_count ወጣቶች";
    }
    
    $total = $children_count + $youth_count;
    $message = "የተመረጡ $total አባላት (" . implode(' እና ', $message_parts) . ") በተሳካ ሁኔታ ጸድቀዋል!";
    
    $_SESSION['message'] = $message;
    $_SESSION['msg_type'] = "success";
}

// Redirect back with category filter
$redirect = $return_page;
if($category != 'all') {
    $redirect .= (strpos($redirect, '?') === false) ? "?category=" . urlencode($category) : "&category=" . urlencode($category);
}

header("Location: $redirect");
exit();
?>
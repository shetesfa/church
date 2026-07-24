<?php
// delete.php - Delete single member with category support
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$return_page = isset($_GET['return']) ? $_GET['return'] : 'members.php';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

if($id) {
    // Get member details for logging and photo deletion
    $result = $conn->query("SELECT full_name, photo, category, member_id_number FROM members WHERE id=$id");
    if($row = $result->fetch_assoc()) {
        $member_name = $row['full_name'];
        $member_category = $row['category'];
        $member_id = $row['member_id_number'];
        
        // Delete photo if exists
        if(!empty($row['photo'])) {
            $photo = "uploads/student_photos/" . $row['photo'];
            if(file_exists($photo)) {
                unlink($photo);
            }
        }
        
        // Delete member
        $conn->query("DELETE FROM members WHERE id=$id");
        
        // Set success message with member info
        $category_display = ($member_category == 'ህጻናት') ? '🧒 ህጻናት' : '👦 ወጣቶች';
        $_SESSION['message'] = "አባል በተሳካ ሁኔታ ተሰርዟል! ($category_display - $member_name)";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "አባል አልተገኘም!";
        $_SESSION['msg_type'] = "error";
    }
}

// Redirect back to appropriate page
$redirect = $return_page;
if($category != 'all') {
    $redirect .= (strpos($redirect, '?') === false) ? "?category=" . urlencode($category) : "&category=" . urlencode($category);
}

header("Location: $redirect");
exit();
?>
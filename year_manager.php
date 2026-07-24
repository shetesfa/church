<?php
// year_manager.php - Fixed version with category support
require_once 'config.php';

// Open new year
if(isset($_POST['open_year'])) {
    $new_year = intval($_POST['new_year']);
    $copy_from = isset($_POST['copy_from_previous']) ? $new_year - 1 : null;
    
    // Validate year
    if($new_year < 2010 || $new_year > 2100) {
        $_SESSION['message'] = "እባክዎ ትክክለኛ ዓመት ያስገቡ (2010-2100)";
        $_SESSION['msg_type'] = "error";
        header("Location: index.php");
        exit();
    }
    
    // Check if year already exists
    $check = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $new_year");
    if($check) {
        $exists = $check->fetch_assoc()['c'] > 0;
        
        if(!$exists) {
            if($copy_from) {
                // Check if source year has data
                $source_check = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $copy_from");
                if($source_check && $source_check->fetch_assoc()['c'] > 0) {
                    // Get category counts from source year
                    $category_counts = $conn->query("SELECT category, COUNT(*) as count FROM members WHERE year = $copy_from GROUP BY category");
                    $children_copied = 0;
                    $youth_copied = 0;
                    
                    while($cat = $category_counts->fetch_assoc()) {
                        if($cat['category'] == 'ህጻናት') {
                            $children_copied = $cat['count'];
                        } else {
                            $youth_copied = $cat['count'];
                        }
                    }
                    
                    // Copy all members from previous year with status 'temporary'
                    $conn->query("INSERT INTO members (
                        full_name, christian_name, phone, birth_date, gender,
                        emergency_name, emergency_phone,
                        confession_father, confession_phone,
                        registration_date, meshena,
                        marital_status, member_type,
                        education_level, profession,
                        subcity, woreda,
                        church_rank, member_id_number, photo,
                        year, status, category, remarks
                    ) SELECT 
                        full_name, christian_name, phone, birth_date, gender,
                        emergency_name, emergency_phone,
                        confession_father, confession_phone,
                        registration_date, meshena,
                        marital_status, member_type,
                        education_level, profession,
                        subcity, woreda,
                        church_rank, member_id_number, photo,
                        $new_year, 'temporary', category, remarks
                    FROM members WHERE year = $copy_from");
                    
                    $copied = $conn->affected_rows;
                    
                    // Build message with category breakdown
                    $message_parts = [];
                    if($children_copied > 0) {
                        $message_parts[] = "🧒 $children_copied ህጻናት";
                    }
                    if($youth_copied > 0) {
                        $message_parts[] = "👦 $youth_copied ወጣቶች";
                    }
                    
                    $_SESSION['message'] = "ዓመተ ምህረት $new_year ተከፍቷል! " . implode(' እና ', $message_parts) . " ከ$copy_from ተቀድተዋል።";
                } else {
                    $_SESSION['message'] = "ዓመተ ምህረት $new_year ተከፍቷል! (ምንም መረጃ አልተቀዳም)";
                }
            } else {
                $_SESSION['message'] = "ዓመተ ምህረት $new_year በተሳካ ሁኔታ ተከፍቷል!";
            }
            $_SESSION['msg_type'] = "success";
            $_SESSION['current_year'] = $new_year;
        } else {
            $_SESSION['message'] = "ዓመተ ምህረት $new_year አስቀድሞ አለ!";
            $_SESSION['msg_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "የውሂብ ጎታ ስህተት: " . $conn->error;
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: members.php?year=$new_year");
    exit();
}

// Set active year
if(isset($_POST['set_active'])) {
    $year = intval($_POST['year']);
    $_SESSION['current_year'] = $year;
    
    // Get category counts for this year
    $children_in_year = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $year AND category='ህጻናት'")->fetch_assoc()['c'];
    $youth_in_year = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $year AND category='ወጣቶች'")->fetch_assoc()['c'];
    
    $message_parts = [];
    if($children_in_year > 0) {
        $message_parts[] = "🧒 $children_in_year ህጻናት";
    }
    if($youth_in_year > 0) {
        $message_parts[] = "👦 $youth_in_year ወጣቶች";
    }
    
    $message = "ዓመተ ምህረት $year ንቁ ዓመት ሆኗል!";
    if(!empty($message_parts)) {
        $message .= " በዚህ ዓመት " . implode(' እና ', $message_parts) . " አሉ።";
    }
    
    $_SESSION['message'] = $message;
    $_SESSION['msg_type'] = "success";
    header("Location: members.php?year=$year");
    exit();
}

// Close year
if(isset($_POST['close_year'])) {
    $year = intval($_POST['year']);
    
    // Get category counts before closing
    $children_in_year = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $year AND category='ህጻናት'")->fetch_assoc()['c'];
    $youth_in_year = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $year AND category='ወጣቶች'")->fetch_assoc()['c'];
    
    $_SESSION['closed_years'][$year] = true;
    
    $message_parts = [];
    if($children_in_year > 0) {
        $message_parts[] = "🧒 $children_in_year ህጻናት";
    }
    if($youth_in_year > 0) {
        $message_parts[] = "👦 $youth_in_year ወጣቶች";
    }
    
    $message = "ዓመተ ምህረት $year ተዘግቷል!";
    if(!empty($message_parts)) {
        $message .= " በዚህ ዓመት " . implode(' እና ', $message_parts) . " ነበሩ።";
    }
    $message .= " (መረጃዎች ግን አሁንም ሊስተካከሉ ይችላሉ)";
    
    $_SESSION['message'] = $message;
    $_SESSION['msg_type'] = "success";
    header("Location: index.php");
    exit();
}

header("Location: index.php");
exit();
?>
<?php
// config.php - Database configuration with Ethiopian Calendar and Categories
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'church_system';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

session_start();

// Ethiopian years (2010-2018 current, plus future)
$ethiopian_years = [2010, 2011, 2012, 2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025];

// Current active year (default 2018, can be changed)
$current_year = isset($_SESSION['current_year']) ? $_SESSION['current_year'] : 2018;

// Categories
$categories = ['ህጻናት', 'ወጣቶች'];

// Function to generate next ATS ID
function generateNextATSId($conn, $year = null) {
    if($year) {
        // Get highest ID for specific year
        $result = $conn->query("SELECT member_id_number FROM members WHERE member_id_number LIKE 'ATS%' ORDER BY CAST(SUBSTRING(member_id_number, 4) AS UNSIGNED) DESC LIMIT 1");
    } else {
        // Get highest ID overall
        $result = $conn->query("SELECT member_id_number FROM members WHERE member_id_number LIKE 'ATS%' ORDER BY CAST(SUBSTRING(member_id_number, 4) AS UNSIGNED) DESC LIMIT 1");
    }
    
    if($result->num_rows > 0) {
        $last_id = $result->fetch_assoc()['member_id_number'];
        $last_number = intval(substr($last_id, 3));
        $next_number = $last_number + 1;
    } else {
        $next_number = 1;
    }
    
    return 'ATS' . str_pad($next_number, 5, '0', STR_PAD_LEFT);
}

// Function to check if year exists
function yearExists($conn, $year) {
    $result = $conn->query("SELECT COUNT(*) as count FROM members WHERE year = $year");
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

// Function to open new year
function openNewYear($conn, $year, $copy_from = null) {
    if($copy_from) {
        // Copy all members from previous year
        $conn->query("INSERT INTO members (full_name, christian_name, phone, birth_date, gender, emergency_name, emergency_phone, confession_father, confession_phone, registration_date, meshena, marital_status, member_type, education_level, profession, subcity, woreda, church_rank, member_id_number, year, status, category, remarks) SELECT full_name, christian_name, phone, birth_date, gender, emergency_name, emergency_phone, confession_father, confession_phone, registration_date, meshena, marital_status, member_type, education_level, profession, subcity, woreda, church_rank, member_id_number, $year, status, category, remarks FROM members WHERE year = $copy_from");
    }
    
    $_SESSION['current_year'] = $year;
    $_SESSION['message'] = "ዓመተ ምህረት $year በተሳካ ሁኔታ ተከፍቷል!";
    $_SESSION['msg_type'] = "success";
}

// Function to close year (just changes view, data still editable)
function closeYear($year) {
    $_SESSION['closed_years'][$year] = true;
    $_SESSION['message'] = "ዓመተ ምህረት $year ተዘግቷል! (መረጃዎች ግን አሁንም ሊስተካከሉ ይችላሉ)";
    $_SESSION['msg_type'] = "success";
}

// Helper function to check missing fields
function hasMissingFields($row) {
    $required = ['full_name', 'phone', 'gender', 'birth_date'];
    foreach($required as $field) {
        if(empty($row[$field])) return true;
    }
    return false;
}

// Get missing fields list
function getMissingFields($row) {
    $missing = [];
    if(empty($row['phone'])) $missing[] = 'ስልክ';
    if(empty($row['birth_date']) || $row['birth_date']=='0000-00-00') $missing[] = 'ልደት';
    if(empty($row['gender'])) $missing[] = 'ጾታ';
    if(empty($row['emergency_name'])) $missing[] = 'የአደጋ ጊዜ ተጠሪ';
    return $missing;
}

// NEW: Get count by category
function getCategoryCount($conn, $category) {
    $result = $conn->query("SELECT COUNT(*) as c FROM members WHERE category = '$category'");
    if($result) {
        return $result->fetch_assoc()['c'];
    }
    return 0;
}

// NEW: Get category badge HTML
function getCategoryBadge($category) {
    if($category == 'ህጻናት') {
        return '<span class="category-badge children">🧒 ህጻናት</span>';
    } else {
        return '<span class="category-badge youth">👦 ወጣቶች</span>';
    }
}

// NEW: Check if member can be upgraded (from ህጻናት to ወጣቶች)
function canUpgradeToYouth($member) {
    return ($member['category'] == 'ህጻናት');
}

// NEW: Calculate age from birth date
function calculateAge($birth_date) {
    if(!$birth_date || $birth_date == '0000-00-00') return '?';
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    return $birth->diff($today)->y;
}

// NEW: Format category for display
function formatCategory($category) {
    if($category == 'ህጻናት') return '🧒 ህጻናት';
    if($category == 'ወጣቶች') return '👦 ወጣቶች';
    return $category;
}

// Format date to Ethiopian
function formatEthiopianDate($date) {
    if(!$date || $date == '0000-00-00') return '';
    // Convert Gregorian to Ethiopian (simplified - you can implement full conversion)
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp); // For now, keep same format
}

// Get message from session
$message = $_SESSION['message'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['message'], $_SESSION['msg_type']);
?>
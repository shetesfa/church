<?php
// members.php - Complete fixed version with category tabs
require_once 'config.php';

// Get current year from session or default
$current_year = isset($_SESSION['current_year']) ? $_SESSION['current_year'] : 2018;

// Get filter parameters
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all'; // NEW: category filter
$member_type = isset($_GET['member_type']) ? $_GET['member_type'] : 'all';
$marital_status = isset($_GET['marital_status']) ? $_GET['marital_status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$query = "SELECT * FROM members WHERE 1=1";

if($selected_year != 'all') {
    $query .= " AND year = " . intval($selected_year);
}

// NEW: Category filter
if($selected_category != 'all') {
    $selected_category = $conn->real_escape_string($selected_category);
    $query .= " AND category = '$selected_category'";
}

if($member_type != 'all') {
    $member_type = $conn->real_escape_string($member_type);
    $query .= " AND member_type = '$member_type'";
}

if($marital_status != 'all') {
    $marital_status = $conn->real_escape_string($marital_status);
    $query .= " AND marital_status = '$marital_status'";
}

if(!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (full_name LIKE '%$search%' OR phone LIKE '%$search%' OR member_id_number LIKE '%$search%' OR christian_name LIKE '%$search%')";
}

$query .= " ORDER BY year DESC, id DESC";
$result = $conn->query($query);

// Check if query executed successfully
if(!$result) {
    die("Query Error: " . $conn->error);
}

// Get all years that have data
$years_with_data = [];
$year_result = $conn->query("SELECT DISTINCT year FROM members ORDER BY year DESC");
if($year_result) {
    while($row = $year_result->fetch_assoc()) {
        $years_with_data[] = $row['year'];
    }
}

// Get statistics by category - NEW
$children_count = getCategoryCount($conn, 'ህጻናት');
$youth_count = getCategoryCount($conn, 'ወጣቶች');

// Get statistics for current view
$total_members = 0;
$total_male = 0;
$total_female = 0;

$result_total = $conn->query("SELECT COUNT(*) as c FROM members");
if($result_total) {
    $total_members = $result_total->fetch_assoc()['c'];
}

// Gender statistics
$result_male = $conn->query("SELECT COUNT(*) as c FROM members WHERE gender = 'ወ' OR gender = 'Male'");
if($result_male) {
    $total_male = $result_male->fetch_assoc()['c'];
}

$result_female = $conn->query("SELECT COUNT(*) as c FROM members WHERE gender = 'ሴ' OR gender = 'Female'");
if($result_female) {
    $total_female = $result_female->fetch_assoc()['c'];
}

// Get counts for current year
$year_total = 0;
$year_male = 0;
$year_female = 0;

if($selected_year != 'all') {
    $result_year_total = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $selected_year");
    if($result_year_total) {
        $year_total = $result_year_total->fetch_assoc()['c'];
    }
    
    $result_year_male = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $selected_year AND (gender = 'ወ' OR gender = 'Male')");
    if($result_year_male) {
        $year_male = $result_year_male->fetch_assoc()['c'];
    }
    
    $result_year_female = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $selected_year AND (gender = 'ሴ' OR gender = 'Female')");
    if($result_year_female) {
        $year_female = $result_year_female->fetch_assoc()['c'];
    }
}

$message = $_SESSION['message'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['message'], $_SESSION['msg_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>አባላት | Members</title>
    <style>
        /* ============================================================
           CERTIFICATE STYLES - Gold Gradient, Professional
           ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Ethiopic', 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #5D3A1A, #6B2E2E, #8B6913);
            padding: 20px;
            min-height: 100vh;
        }

        /* Main Container - Gold Gradient */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: linear-gradient(135deg, #FDB931, #F39C12, #C0392B);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        /* Decorative Border */
        .container::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px double rgba(255,255,255,0.3);
            border-radius: 15px;
            pointer-events: none;
        }

        /* Header with Logo */
        .header {
            text-align: center;
            padding: 30px 20px;
            position: relative;
            z-index: 2;
        }

        .church-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: transform 0.3s;
        }

        .church-logo:hover {
            transform: scale(1.05);
        }

        .church-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header h1 {
            font-size: 36px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .header .amharic {
            font-size: 32px;
            color: #FFD700;
            border-bottom: 2px solid #FFD700;
            display: inline-block;
            padding-bottom: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        /* Navigation */
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 10px 20px;
            background: rgba(0,0,0,0.3);
            position: relative;
            z-index: 2;
        }

        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            background: rgba(255,255,255,0.15);
            transition: all 0.2s;
            border: 1px solid rgba(255,215,0,0.3);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .nav a:hover, .nav a.active {
            background: #FFD700;
            color: #5D3A1A;
            transform: translateY(-2px);
        }

        /* Year Tabs */
        .year-tabs {
            padding: 20px 20px 10px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            background: rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }

        .year-tab {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid transparent;
        }

        .year-tab.active {
            background: #FFD700;
            color: #5D3A1A;
            box-shadow: 0 4px 10px rgba(255,215,0,0.3);
        }

        .year-tab:not(.active) {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,215,0,0.3);
        }

        .year-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .year-tab.has-data {
            border-left: 3px solid #27ae60;
        }

        .btn-open-year {
            background: #27ae60;
            color: white;
            margin-left: auto;
        }

        /* NEW: Category Tabs */
        .category-tabs {
            padding: 10px 20px 20px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background: rgba(0,0,0,0.15);
            position: relative;
            z-index: 2;
            border-bottom: 1px solid rgba(255,215,0,0.2);
        }

        .category-tab {
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid transparent;
        }

        .category-tab.all {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .category-tab.children {
            background: rgba(52, 152, 219, 0.3);
            color: white;
            border-color: #3498db;
        }

        .category-tab.youth {
            background: rgba(46, 204, 113, 0.3);
            color: white;
            border-color: #2ecc71;
        }

        .category-tab.active {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .category-tab.active.all {
            background: #FFD700;
            color: #5D3A1A;
        }

        .category-tab.active.children {
            background: #3498db;
            color: white;
        }

        .category-tab.active.youth {
            background: #2ecc71;
            color: white;
        }

        .category-count {
            background: rgba(0,0,0,0.3);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 5px;
        }

        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            border-left: 4px solid #FFD700;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .stat-value {
            font-size: 32px;
            color: #FFD700;
            font-weight: bold;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 14px;
            color: white;
            font-weight: 500;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .stat-sub {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            margin-top: 5px;
        }

        /* Category Badge */
        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            min-width: 70px;
        }

        .badge-children {
            background: #3498db;
            color: white;
        }

        .badge-youth {
            background: #2ecc71;
            color: white;
        }

        /* Filter Bar */
        .filter-bar {
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            background: rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }

        .filter-input {
            padding: 10px 15px;
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 25px;
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 14px;
            flex: 1;
            min-width: 200px;
        }

        .filter-input::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .filter-input:focus {
            outline: none;
            border-color: #FFD700;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 25px;
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 14px;
            min-width: 150px;
        }

        .filter-select option {
            background: #5D3A1A;
            color: white;
        }

        .filter-btn {
            padding: 10px 25px;
            background: #FFD700;
            color: #5D3A1A;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .reset-btn {
            padding: 10px 25px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-size: 14px;
            border: 1px solid rgba(255,215,0,0.3);
        }

        /* Table Container */
        .table-container {
            padding: 20px;
            overflow-x: auto;
            position: relative;
            z-index: 2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            overflow: hidden;
        }

        th {
            background: rgba(0,0,0,0.4);
            color: #FFD700;
            padding: 15px 10px;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
            white-space: nowrap;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255,215,0,0.2);
            color: white;
            font-size: 14px;
        }

        tr {
            transition: all 0.2s;
            cursor: pointer;
        }

        tr:hover {
            background: rgba(255,215,0,0.15);
        }

        /* ATS ID Badge */
        .ats-id {
            font-family: monospace;
            font-weight: bold;
            color: #FFD700;
            background: rgba(0,0,0,0.3);
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block;
            font-size: 12px;
        }

        /* Photo Cell */
        .photo-cell {
            width: 50px;
            text-align: center;
        }

        .member-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #FFD700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .member-photo:hover {
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(255,215,0,0.5);
        }

        .no-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            border: 2px solid #FFD700;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }

        .status-approved {
            background: rgba(39, 174, 96, 0.3);
            color: #2ecc71;
            border: 1px solid #27ae60;
        }

        .status-temporary {
            background: rgba(241, 196, 15, 0.3);
            color: #f1c40f;
            border: 1px solid #f39c12;
        }

        .member-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            background: rgba(255,215,0,0.2);
            color: #FFD700;
        }

        /* Upgrade Button */
        .upgrade-btn {
            background: #f39c12;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .upgrade-btn:hover {
            background: #e67e22;
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            text-decoration: none;
            color: white;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,215,0,0.3);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .action-btn:hover {
            background: #FFD700;
            color: #5D3A1A;
            transform: translateY(-2px);
        }

        /* Missing field indicator */
        .missing-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f39c12;
            margin-left: 5px;
            box-shadow: 0 0 5px #f39c12;
        }

        /* Photo Modal */
        .photo-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .photo-modal img {
            max-width: 90%;
            max-height: 90%;
            border: 5px solid #FFD700;
            border-radius: 10px;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 15px;
            border-top: 1px solid rgba(255,215,0,0.2);
            color: white;
            background: rgba(0,0,0,0.2);
            font-size: 12px;
            position: relative;
            z-index: 2;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header h1 { font-size: 28px; }
            .header .amharic { font-size: 24px; }
            .filter-bar { flex-direction: column; }
            .filter-input, .filter-select { width: 100%; }
            .year-tabs { justify-content: center; }
            .category-tabs { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Logo -->
        <div class="header">
            <div class="church-logo" onclick="window.location.href='index.php'">
                <img src="images/icon.png" alt="Church Logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'45\' fill=\'%23FFD700\'/%3E%3Ctext x=\'50\' y=\'70\' font-size=\'50\' text-anchor=\'middle\' fill=\'%238B4513\'%3E⛪%3C/text%3E%3C/svg%3E';">
            </div>
            <h1>አጸደ ትጉሃን ሰንበት ትምህርት ቤት</h1>
            <div class="amharic">የአባላት ዝርዝር</div>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php">ዋና ገጽ</a>
            <a href="members.php" class="active">አባላት</a>
            <a href="temporary.php">ጊዜያዊ</a>
            <a href="register.php">አዲስ መዝግብ</a>
            <a href="birthdays.php">ልደት</a>
            <a href="statistics.php">ስታቲስቲክስ</a>
        </div>

        <!-- Year Tabs -->
        <div class="year-tabs">
            <a href="members.php?year=all<?php echo $selected_category != 'all' ? '&category='.$selected_category : ''; ?>" class="year-tab <?php echo $selected_year == 'all' ? 'active' : ''; ?>">
                ሁሉም ዓመታት
            </a>
            <?php 
            $all_years = [2010,2011,2012,2013,2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025];
            foreach($all_years as $y): 
                $has_data = in_array($y, $years_with_data);
                $url = "members.php?year=$y";
                if($selected_category != 'all') {
                    $url .= "&category=" . urlencode($selected_category);
                }
            ?>
            <a href="<?php echo $url; ?>" 
               class="year-tab <?php echo $y == $selected_year ? 'active' : ''; ?> <?php echo $has_data ? 'has-data' : ''; ?>">
                <?php echo $y; ?>
                <?php if($has_data): ?>
                    <span style="font-size: 10px;">📊</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <a href="index.php#open-year" class="year-tab btn-open-year" onclick="openNewYearModal(); return false;">
                ➕ አዲስ ዓመት
            </a>
        </div>

        <!-- NEW: Category Tabs -->
        <div class="category-tabs">
            <?php
            $all_url = "members.php?year=" . urlencode($selected_year);
            $children_url = "members.php?category=" . urlencode('ህጻናት') . "&year=" . urlencode($selected_year);
            $youth_url = "members.php?category=" . urlencode('ወጣቶች') . "&year=" . urlencode($selected_year);
            ?>
            
            <a href="<?php echo $all_url; ?>" class="category-tab all <?php echo $selected_category == 'all' ? 'active' : ''; ?>">
                👥 ሁሉም አባላት
                <span class="category-count"><?php echo $total_members; ?></span>
            </a>
            
            <a href="<?php echo $children_url; ?>" class="category-tab children <?php echo $selected_category == 'ህጻናት' ? 'active' : ''; ?>">
                🧒 ህጻናት
                <span class="category-count"><?php echo $children_count; ?></span>
            </a>
            
            <a href="<?php echo $youth_url; ?>" class="category-tab youth <?php echo $selected_category == 'ወጣቶች' ? 'active' : ''; ?>">
                👦 ወጣቶች
                <span class="category-count"><?php echo $youth_count; ?></span>
            </a>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-value"><?php echo $selected_year == 'all' ? $total_members : $year_total; ?></div>
                <div class="stat-label">ጠቅላላ አባላት</div>
                <div class="stat-sub"><?php echo $selected_year == 'all' ? 'ከሁሉም ዓመታት' : "በ$selected_year ዓ.ም"; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $selected_year == 'all' ? $total_male : $year_male; ?></div>
                <div class="stat-label">ወንድ</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $selected_year == 'all' ? $total_female : $year_female; ?></div>
                <div class="stat-label">ሴት</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $result->num_rows; ?></div>
                <div class="stat-label">የአሁን እይታ</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
            <?php if($selected_year != 'all'): ?>
            <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
            <?php endif; ?>
            <?php if($selected_category != 'all'): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
            <?php endif; ?>
            
            <input type="text" name="search" class="filter-input" placeholder="በስም፣ በስልክ ወይም በመታወቂያ ቁጥር ፈልግ..." value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="member_type" class="filter-select">
                <option value="all">ሁሉም አይነት</option>
                <option value="እጩ" <?php echo $member_type == 'እጩ' ? 'selected' : ''; ?>>እጩ</option>
                <option value="መደበኛ" <?php echo $member_type == 'መደበኛ' ? 'selected' : ''; ?>>መደበኛ</option>
            </select>

            <select name="marital_status" class="filter-select">
                <option value="all">ሁሉም የጋብቻ ሁኔታ</option>
                <option value="ያገባ" <?php echo $marital_status == 'ያገባ' ? 'selected' : ''; ?>>ያገባ</option>
                <option value="ያላገባ" <?php echo $marital_status == 'ያላገባ' ? 'selected' : ''; ?>>ያላገባ</option>
            </select>

            <button type="submit" class="filter-btn">🔍 አጣራ</button>
            <a href="members.php?year=<?php echo $selected_year; ?><?php echo $selected_category != 'all' ? '&category='.urlencode($selected_category) : ''; ?>" class="reset-btn">🔄 አጽዳ</a>
        </form>

        <!-- Members Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>መታወቂያ ቁጥር</th>
                        <th>ፎቶ</th>
                        <th>ሙሉ ስም</th>
                        <th>ምድብ</th> <!-- NEW: Category column -->
                        <th>ክርስትና ስም</th>
                        <th>ስልክ</th>
                        <th>ዓመት</th>
                        <th>አይነት</th>
                        <th>ጾታ</th>
                        <th>ዕድሜ</th> <!-- NEW: Age column -->
                        <th>ሁኔታ</th>
                        <th>ድርጊት</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $missing = getMissingFields($row);
                            $has_missing = !empty($missing);
                            $age = calculateAge($row['birth_date']);
                        ?>
                        <tr onclick="window.location.href='view.php?id=<?php echo $row['member_id_number']; ?>'" style="cursor: pointer;">
                            <td>
                                <span class="ats-id" onclick="event.stopPropagation();"><?php echo $row['member_id_number'] ?? 'ATS'.str_pad($row['id'],5,'0',STR_PAD_LEFT); ?></span>
                                <?php if($has_missing): ?>
                                <span class="missing-indicator" title="የጎደሉ መረጃዎች: <?php echo implode(', ', $missing); ?>"></span>
                                <?php endif; ?>
                            </td>
                            <td class="photo-cell" onclick="event.stopPropagation();">
                                <?php if(!empty($row['photo'])): ?>
                                    <img src="uploads/student_photos/<?php echo $row['photo']; ?>" class="member-photo" onclick="showPhoto('<?php echo $row['photo']; ?>', event)">
                                <?php else: ?>
                                    <div class="no-photo" onclick="showPhoto(null, event)">📸</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td>
                                <?php 
                                if($row['category'] == 'ህጻናት') {
                                    echo '<span class="category-badge badge-children">🧒 ህጻናት</span>';
                                } else {
                                    echo '<span class="category-badge badge-youth">👦 ወጣቶች</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['christian_name'] ?? '---'); ?></td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? '---'); ?></td>
                            <td><span class="ats-id"><?php echo $row['year']; ?></span></td>
                            <td><span class="member-type"><?php echo $row['member_type'] ?? '---'; ?></span></td>
                            <td><?php echo $row['gender'] ?? '---'; ?></td>
                            <td><?php echo $age; ?></td>
                            <td>
                                <?php if($row['status'] == 'approved'): ?>
                                    <span class="status-badge status-approved">የተረጋገጠ</span>
                                <?php else: ?>
                                    <span class="status-badge status-temporary">ጊዜያዊ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns" onclick="event.stopPropagation();">
                                    <a href="view.php?id=<?php echo $row['member_id_number']; ?>" class="action-btn">👁️</a>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="action-btn">✏️</a>
                                    <?php if($row['status'] == 'temporary'): ?>
                                    <a href="temporary.php?approve=<?php echo $row['id']; ?>" class="action-btn" onclick="return confirm('ማጽደቅ እርግጠኛ ነህ?')">✅</a>
                                    <?php endif; ?>
                                    <?php if($row['category'] == 'ህጻናት'): ?>
                                    <a href="upgrade.php?id=<?php echo $row['id']; ?>" class="action-btn upgrade-btn" onclick="return confirm('ወደ ወጣቶች ማሻሻል እርግጠኛ ነህ?')">⬆️ አሻሽል</a>
                                    <?php endif; ?>
                                    <a href="print.php?id=<?php echo $row['id']; ?>" class="action-btn" target="_blank">🖨️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" style="text-align: center; padding: 50px;">
                                <h3 style="color: #FFD700;">😢 ምንም አባላት አልተገኙም</h3>
                                <a href="register.php" style="color: white; background: #FFD700; padding: 10px 20px; border-radius: 25px; text-decoration: none; display: inline-block; margin-top: 15px;">አዲስ አባል መዝግብ</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            © <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት | ጠቅላላ: <?php echo $result->num_rows; ?> አባላት
        </div>
    </div>

    <!-- Photo Modal -->
    <div id="photoModal" class="photo-modal" onclick="closePhotoModal()">
        <span class="close-modal" onclick="closePhotoModal()">&times;</span>
        <img id="modalPhoto" src="" alt="Member Photo">
    </div>

    <!-- New Year Modal -->
    <div id="newYearModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; justify-content:center; align-items:center;">
        <div class="modal-content" style="background: linear-gradient(135deg, #FDB931, #F39C12); padding:30px; border-radius:20px; max-width:400px; width:90%;">
            <div class="modal-title" style="font-size:24px; color:#FFD700; margin-bottom:20px;">➕ አዲስ ዓመት ክፈት</div>
            <form action="year_manager.php" method="POST">
                <input type="number" name="new_year" class="modal-input" style="width:100%; padding:12px; margin-bottom:15px; border:1px solid rgba(255,215,0,0.3); border-radius:8px; background:rgba(255,255,255,0.1); color:white;" placeholder="ዓመት (ለምሳሌ 2019)" required min="2010" max="2100">
                
                <div class="modal-checkbox" style="margin:15px 0;">
                    <label style="color:white;">
                        <input type="checkbox" name="copy_from_previous" value="1" checked>
                        ካለፈው ዓመት መረጃ ይቅዳ
                    </label>
                </div>
                
                <div class="modal-buttons" style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="modal-btn modal-btn-secondary" style="padding:10px 20px; background:rgba(255,255,255,0.2); color:white; border:none; border-radius:25px; cursor:pointer;" onclick="document.getElementById('newYearModal').style.display='none'">ዝጋ</button>
                    <button type="submit" name="open_year" class="modal-btn modal-btn-primary" style="padding:10px 20px; background:#FFD700; color:#5D3A1A; border:none; border-radius:25px; cursor:pointer;">ክፈት</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Photo modal functions
        function showPhoto(photoSrc, event) {
            event.stopPropagation();
            const modal = document.getElementById('photoModal');
            const modalImg = document.getElementById('modalPhoto');
            
            if(photoSrc) {
                modalImg.src = 'uploads/student_photos/' + photoSrc;
            } else {
                modalImg.src = 'images/icon.png';
            }
            
            modal.style.display = 'flex';
        }

        function closePhotoModal() {
            document.getElementById('photoModal').style.display = 'none';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                closePhotoModal();
            }
        });

        // Open new year modal
        function openNewYearModal() {
            document.getElementById('newYearModal').style.display = 'flex';
        }

        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => {
                if(msg) msg.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
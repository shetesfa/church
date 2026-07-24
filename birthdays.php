<?php
// birthdays.php - Ethiopian Calendar Birthdays with category support
require_once 'config.php';

// Ethiopian month names
$ethiopian_months = [
    1 => 'መስከረም', 2 => 'ጥቅምት', 3 => 'ኅዳር', 4 => 'ታኅሣሥ',
    5 => 'ጥር', 6 => 'የካቲት', 7 => 'መጋቢት', 8 => 'ሚያዝያ',
    9 => 'ግንቦት', 10 => 'ሰኔ', 11 => 'ሐምሌ', 12 => 'ነሐሴ', 13 => 'ጳጉሜን'
];

// Get selected month (default to current Ethiopian month)
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : (int)date('m');
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all'; // NEW: category filter

// Get birthdays for selected month with category filter
$query = "SELECT * FROM members WHERE 
          MONTH(birth_date) = $selected_month 
          AND birth_date IS NOT NULL 
          AND birth_date != '0000-00-00'";

if($selected_category != 'all') {
    $query .= " AND category = '" . $conn->real_escape_string($selected_category) . "'";
}

$query .= " ORDER BY DAY(birth_date) ASC";

$result = $conn->query($query);

// Get counts by year
$years_count = [];
$year_query = "SELECT year, COUNT(*) as c FROM members WHERE MONTH(birth_date) = $selected_month AND birth_date IS NOT NULL AND birth_date != '0000-00-00'";
if($selected_category != 'all') {
    $year_query .= " AND category = '" . $conn->real_escape_string($selected_category) . "'";
}
$year_query .= " GROUP BY year ORDER BY year DESC";

$year_result = $conn->query($year_query);
while($row = $year_result->fetch_assoc()) {
    $years_count[$row['year']] = $row['c'];
}

// Get category counts for this month
$children_count = $conn->query("SELECT COUNT(*) as c FROM members WHERE MONTH(birth_date) = $selected_month AND birth_date IS NOT NULL AND birth_date != '0000-00-00' AND category='ህጻናት'")->fetch_assoc()['c'];
$youth_count = $conn->query("SELECT COUNT(*) as c FROM members WHERE MONTH(birth_date) = $selected_month AND birth_date IS NOT NULL AND birth_date != '0000-00-00' AND category='ወጣቶች'")->fetch_assoc()['c'];

$message = $_SESSION['message'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['message'], $_SESSION['msg_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>የልደት ቀን ዝርዝር | Birthdays</title>
    <style>
        /* ============================================================
           CERTIFICATE STYLES - Birthday Theme
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: linear-gradient(135deg, #FDB931, #F39C12, #C0392B);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

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

        /* NEW: Category Tabs */
        .category-tabs {
            padding: 10px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background: rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }

        .category-tab {
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

        .category-tab.all {
            background: rgba(255,255,255,0.2);
            color: white;
            border-color: rgba(255,215,0,0.3);
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
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 5px;
        }

        /* Category Badge */
        .category-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 5px;
        }

        .badge-children {
            background: #3498db;
            color: white;
        }

        .badge-youth {
            background: #2ecc71;
            color: white;
        }

        /* Month Selector */
        .month-selector {
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            background: rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }

        .month-btn {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,215,0,0.3);
        }

        .month-btn.active {
            background: #FFD700;
            color: #5D3A1A;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(255,215,0,0.3);
        }

        .month-btn:hover {
            transform: translateY(-2px);
            background: #FFD700;
            color: #5D3A1A;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }

        .stat-card.children {
            border-left-color: #3498db;
        }

        .stat-card.youth {
            border-left-color: #2ecc71;
        }

        .stat-value {
            font-size: 36px;
            color: #FFD700;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 14px;
            color: white;
        }

        /* Year Summary */
        .year-summary {
            padding: 0 20px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            position: relative;
            z-index: 2;
        }

        .year-badge {
            padding: 5px 12px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            font-size: 12px;
            color: white;
            border: 1px solid rgba(255,215,0,0.3);
        }

        .year-badge span {
            color: #FFD700;
            font-weight: bold;
            margin-left: 5px;
        }

        /* Birthday Grid */
        .birthday-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .birthday-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 15px;
            border-left: 4px solid #FFD700;
            transition: all 0.2s;
            cursor: pointer;
        }

        .birthday-card.children {
            border-left-color: #3498db;
        }

        .birthday-card.youth {
            border-left-color: #2ecc71;
        }

        .birthday-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.25);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .birthday-icon {
            width: 50px;
            height: 50px;
            background: #FFD700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #5D3A1A;
        }

        .card-title {
            flex: 1;
        }

        .member-name {
            font-size: 18px;
            font-weight: bold;
            color: white;
        }

        .member-id {
            font-size: 12px;
            color: #FFD700;
            font-family: monospace;
        }

        .card-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,215,0,0.2);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .detail-label {
            color: rgba(255,255,255,0.7);
        }

        .detail-value {
            color: #FFD700;
            font-weight: bold;
        }

        .birthday-date-large {
            font-size: 20px;
            color: #FFD700;
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
        }

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

        /* No birthdays */
        .no-birthdays {
            grid-column: 1/-1;
            text-align: center;
            padding: 50px;
            color: white;
        }

        .no-birthdays h3 {
            color: #FFD700;
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header h1 { font-size: 28px; }
            .header .amharic { font-size: 24px; }
            .month-btn { font-size: 12px; padding: 6px 12px; }
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
            <div class="amharic">የልደት ቀን ዝርዝር</div>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php">ዋና ገጽ</a>
            <a href="members.php">አባላት</a>
            <a href="temporary.php">ጊዜያዊ</a>
            <a href="register.php">አዲስ መዝግብ</a>
            <a href="birthdays.php" class="active">ልደት</a>
            <a href="statistics.php">ስታቲስቲክስ</a>
            <a href="excel_export.php">📥 ኤክሴል</a>
        </div>

        <!-- NEW: Category Tabs -->
        <div class="category-tabs">
            <?php
            $base_url = "birthdays.php?month=" . $selected_month;
            ?>
            
            <a href="<?php echo $base_url; ?>&category=all" class="category-tab all <?php echo $selected_category == 'all' ? 'active' : ''; ?>">
                👥 ሁሉም
                <span class="category-count"><?php echo $children_count + $youth_count; ?></span>
            </a>
            
            <a href="<?php echo $base_url; ?>&category=ህጻናት" class="category-tab children <?php echo $selected_category == 'ህጻናት' ? 'active' : ''; ?>">
                🧒 ህጻናት
                <span class="category-count"><?php echo $children_count; ?></span>
            </a>
            
            <a href="<?php echo $base_url; ?>&category=ወጣቶች" class="category-tab youth <?php echo $selected_category == 'ወጣቶች' ? 'active' : ''; ?>">
                👦 ወጣቶች
                <span class="category-count"><?php echo $youth_count; ?></span>
            </a>
        </div>

        <!-- Month Selector - Ethiopian Months -->
        <div class="month-selector">
            <?php for($m = 1; $m <= 13; $m++): ?>
            <a href="?month=<?php echo $m; ?>&category=<?php echo urlencode($selected_category); ?>" class="month-btn <?php echo $m == $selected_month ? 'active' : ''; ?>">
                <?php echo $ethiopian_months[$m]; ?>
            </a>
            <?php endfor; ?>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $result->num_rows; ?></div>
                <div class="stat-label">ጠቅላላ ልደት</div>
            </div>
            <div class="stat-card children">
                <div class="stat-value"><?php echo $children_count; ?></div>
                <div class="stat-label">🧒 ህጻናት</div>
            </div>
            <div class="stat-card youth">
                <div class="stat-value"><?php echo $youth_count; ?></div>
                <div class="stat-label">👦 ወጣቶች</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($years_count); ?></div>
                <div class="stat-label">የተሳተፉ ዓመታት</div>
            </div>
        </div>

        <!-- Year Summary -->
        <?php if(!empty($years_count)): ?>
        <div class="year-summary">
            <?php foreach($years_count as $year => $count): ?>
            <span class="year-badge">
                <?php echo $year; ?> <span><?php echo $count; ?></span>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Birthday Grid -->
        <div class="birthday-grid">
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $birth_date = strtotime($row['birth_date']);
                    $day = date('d', $birth_date);
                    $age = calculateAge($row['birth_date']);
                ?>
                <div class="birthday-card <?php echo $row['category']; ?>" onclick="window.location.href='view.php?id=<?php echo $row['member_id_number']; ?>'">
                    <div class="card-header">
                        <div class="birthday-icon">🎂</div>
                        <div class="card-title">
                            <div class="member-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                            <div class="member-id"><?php echo $row['member_id_number'] ?? 'ATS'.str_pad($row['id'],5,'0',STR_PAD_LEFT); ?></div>
                        </div>
                    </div>
                    
                    <div class="birthday-date-large">
                        <?php echo $day; ?> <?php echo $ethiopian_months[$selected_month]; ?>
                    </div>
                    
                    <div class="card-details">
                        <div class="detail-row">
                            <span class="detail-label">ምድብ</span>
                            <span class="detail-value">
                                <?php 
                                if($row['category'] == 'ህጻናት') {
                                    echo '🧒 ህጻናት';
                                } else {
                                    echo '👦 ወጣቶች';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">ዕድሜ</span>
                            <span class="detail-value"><?php echo $age; ?> ዓመት</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">ዓመተ ምህረት</span>
                            <span class="detail-value"><?php echo $row['year']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">ስልክ</span>
                            <span class="detail-value"><?php echo htmlspecialchars($row['phone'] ?? '---'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">አይነት</span>
                            <span class="detail-value"><?php echo $row['member_type'] ?? '---'; ?></span>
                        </div>
                        <?php if(!empty(getMissingFields($row))): ?>
                        <div class="detail-row">
                            <span class="detail-label">የጎደለ</span>
                            <span class="detail-value" style="color: #f39c12;">⚠️ <?php echo count(getMissingFields($row)); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Category badge for quick identification -->
                    <?php if($row['category'] == 'ህጻናት'): ?>
                    <div class="category-badge badge-children" style="margin-top: 10px;">🧒 ህጻናት</div>
                    <?php else: ?>
                    <div class="category-badge badge-youth" style="margin-top: 10px;">👦 ወጣቶች</div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-birthdays">
                    <h3>😢 በዚህ ወር ምንም ልደት የለም</h3>
                    <p style="color: rgba(255,255,255,0.7); margin-top: 10px;">እባክዎ ሌላ ወር ይምረጡ</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            © <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት | የኢትዮጵያ ዘመን አቆጣጠር
        </div>
    </div>

    <script>
        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => {
                if(msg) msg.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
<?php
// statistics.php - Fixed statistics with category counts
require_once 'config.php';

// Get current year
$current_year = isset($_SESSION['current_year']) ? $_SESSION['current_year'] : 2018;

// Total statistics
$total = $conn->query("SELECT COUNT(*) as c FROM members")->fetch_assoc()['c'];

// NEW: Category statistics
$children_total = getCategoryCount($conn, 'ህጻናት');
$youth_total = getCategoryCount($conn, 'ወጣቶች');

// Gender statistics
$male = $conn->query("SELECT COUNT(*) as c FROM members WHERE gender IN ('ወ','Male')")->fetch_assoc()['c'];
$female = $conn->query("SELECT COUNT(*) as c FROM members WHERE gender IN ('ሴ','Female')")->fetch_assoc()['c'];

// Gender by category
$male_children = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ህጻናት' AND gender IN ('ወ','Male')")->fetch_assoc()['c'];
$female_children = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ህጻናት' AND gender IN ('ሴ','Female')")->fetch_assoc()['c'];
$male_youth = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ወጣቶች' AND gender IN ('ወ','Male')")->fetch_assoc()['c'];
$female_youth = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ወጣቶች' AND gender IN ('ሴ','Female')")->fetch_assoc()['c'];

// Status statistics
$approved = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='approved'")->fetch_assoc()['c'];
$temporary = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='temporary'")->fetch_assoc()['c'];

// Member type statistics
$candidate = $conn->query("SELECT COUNT(*) as c FROM members WHERE member_type='እጩ'")->fetch_assoc()['c'];
$regular = $conn->query("SELECT COUNT(*) as c FROM members WHERE member_type='መደበኛ'")->fetch_assoc()['c'];

// Marital status statistics
$married = $conn->query("SELECT COUNT(*) as c FROM members WHERE marital_status='ያገባ'")->fetch_assoc()['c'];
$single = $conn->query("SELECT COUNT(*) as c FROM members WHERE marital_status='ያላገባ'")->fetch_assoc()['c'];
$unknown_marital = $conn->query("SELECT COUNT(*) as c FROM members WHERE marital_status IS NULL OR marital_status = ''")->fetch_assoc()['c'];

// Year statistics
$year_stats = [];
for($y = 2010; $y <= 2025; $y++) {
    $count = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $y")->fetch_assoc()['c'];
    if($count > 0) {
        $year_stats[$y] = $count;
    }
}

// Year statistics by category
$year_children = [];
$year_youth = [];
for($y = 2010; $y <= 2025; $y++) {
    $count_children = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $y AND category='ህጻናት'")->fetch_assoc()['c'];
    if($count_children > 0) {
        $year_children[$y] = $count_children;
    }
    $count_youth = $conn->query("SELECT COUNT(*) as c FROM members WHERE year = $y AND category='ወጣቶች'")->fetch_assoc()['c'];
    if($count_youth > 0) {
        $year_youth[$y] = $count_youth;
    }
}

// Missing fields statistics
$missing_phone = $conn->query("SELECT COUNT(*) as c FROM members WHERE phone IS NULL OR phone = ''")->fetch_assoc()['c'];
$missing_birth = $conn->query("SELECT COUNT(*) as c FROM members WHERE birth_date IS NULL OR birth_date = '0000-00-00'")->fetch_assoc()['c'];
$missing_gender = $conn->query("SELECT COUNT(*) as c FROM members WHERE gender IS NULL OR gender = ''")->fetch_assoc()['c'];
$missing_emergency = $conn->query("SELECT COUNT(*) as c FROM members WHERE emergency_name IS NULL OR emergency_name = ''")->fetch_assoc()['c'];

// Age statistics
$children_under_7 = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ህጻናት' AND birth_date IS NOT NULL AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 7")->fetch_assoc()['c'];
$children_7_12 = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ህጻናት' AND birth_date IS NOT NULL AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 7 AND 12")->fetch_assoc()['c'];
$youth_13_18 = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ወጣቶች' AND birth_date IS NOT NULL AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 13 AND 18")->fetch_assoc()['c'];
$youth_over_18 = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ወጣቶች' AND birth_date IS NOT NULL AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) > 18")->fetch_assoc()['c'];

// Upgrade statistics
$upgraded_count = $conn->query("SELECT COUNT(*) as c FROM members WHERE previous_category='ህጻናት' AND category='ወጣቶች'")->fetch_assoc()['c'];

$message = $_SESSION['message'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['message'], $_SESSION['msg_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ስታቲስቲክስ | Statistics</title>
    <style>
        /* ============================================================
           CERTIFICATE STYLES - Statistics Page
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
        }

        .nav a:hover, .nav a.active {
            background: #FFD700;
            color: #5D3A1A;
            transform: translateY(-2px);
        }

        /* Stats Sections */
        .stats-section {
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .section-title {
            font-size: 22px;
            color: #FFD700;
            margin-bottom: 20px;
            border-left: 4px solid #FFD700;
            padding-left: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #FFD700;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.25);
        }

        .stat-value {
            font-size: 42px;
            color: #FFD700;
            font-weight: bold;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 16px;
            color: white;
            margin-bottom: 5px;
        }

        .stat-percent {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
        }

        .stat-small {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
        }

        /* Category Cards */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .category-card {
            border-radius: 15px;
            padding: 20px;
            color: white;
        }

        .category-card.children {
            background: rgba(52, 152, 219, 0.3);
            border-left: 4px solid #3498db;
        }

        .category-card.youth {
            background: rgba(46, 204, 113, 0.3);
            border-left: 4px solid #2ecc71;
        }

        .category-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .category-stat {
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }

        .category-stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #FFD700;
        }

        .category-stat-label {
            font-size: 12px;
        }

        /* Year Grid */
        .year-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }

        .year-card {
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            border-left: 3px solid #FFD700;
        }

        .year-number {
            font-size: 20px;
            color: #FFD700;
            font-weight: bold;
        }

        .year-count {
            font-size: 14px;
            color: white;
            margin-top: 5px;
        }

        .year-children {
            font-size: 12px;
            color: #3498db;
        }

        .year-youth {
            font-size: 12px;
            color: #2ecc71;
        }

        /* Age Distribution */
        .age-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .age-card {
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .age-card.children-under7 { border-left: 4px solid #3498db; }
        .age-card.children-7-12 { border-left: 4px solid #2980b9; }
        .age-card.youth-13-18 { border-left: 4px solid #2ecc71; }
        .age-card.youth-over18 { border-left: 4px solid #27ae60; }

        .age-value {
            font-size: 28px;
            color: #FFD700;
            font-weight: bold;
        }

        .age-label {
            font-size: 13px;
            color: white;
        }

        /* Upgrade Stats */
        .upgrade-card {
            background: rgba(155, 89, 182, 0.3);
            border-left: 4px solid #9b59b6;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .upgrade-info {
            flex: 1;
        }

        .upgrade-title {
            font-size: 18px;
            color: #9b59b6;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .upgrade-count {
            font-size: 36px;
            color: #FFD700;
            font-weight: bold;
        }

        /* Missing Fields */
        .missing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .missing-card {
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 15px;
            border-left: 4px solid #f39c12;
        }

        .missing-title {
            color: #f39c12;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .missing-value {
            font-size: 28px;
            color: white;
            font-weight: bold;
        }

        /* Chart Bars */
        .chart-bar {
            margin: 15px 0;
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            color: white;
            font-size: 13px;
        }

        .bar-container {
            width: 100%;
            height: 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: #FFD700;
            border-radius: 5px;
            transition: width 0.3s;
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

        /* Responsive */
        @media (max-width: 768px) {
            .header h1 { font-size: 28px; }
            .header .amharic { font-size: 24px; }
            .stat-value { font-size: 32px; }
            .category-grid { grid-template-columns: 1fr; }
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
            <div class="amharic">ስታቲስቲክስ</div>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php">ዋና ገጽ</a>
            <a href="members.php">አባላት</a>
            <a href="temporary.php">ጊዜያዊ</a>
            <a href="register.php">አዲስ መዝግብ</a>
            <a href="birthdays.php">ልደት</a>
            <a href="statistics.php" class="active">ስታቲስቲክስ</a>
        </div>

        <!-- Messages -->
        <?php if($message): ?>
        <div style="margin:15px 20px; padding:10px; background:<?php echo $msg_type=='success'?'rgba(39,174,96,0.3)':'rgba(192,57,43,0.3)'; ?>; border-left:4px solid <?php echo $msg_type=='success'?'#27ae60':'#c0392b'; ?>; color:white;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Main Statistics -->
        <div class="stats-section">
            <div class="section-title">📊 አጠቃላይ ስታቲስቲክስ</div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total; ?></div>
                    <div class="stat-label">ጠቅላላ አባላት</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $approved; ?></div>
                    <div class="stat-label">የተረጋገጡ</div>
                    <div class="stat-percent"><?php echo $total > 0 ? round(($approved/$total)*100) : 0; ?>%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $temporary; ?></div>
                    <div class="stat-label">ጊዜያዊ</div>
                    <div class="stat-percent"><?php echo $total > 0 ? round(($temporary/$total)*100) : 0; ?>%</div>
                </div>
            </div>

            <!-- NEW: Category Distribution -->
            <div class="section-title" style="margin-top: 30px;">👥 የምድብ ክፍፍል</div>
            
            <div class="category-grid">
                <div class="category-card children">
                    <div class="category-title">
                        <span>🧒</span> ህጻናት
                    </div>
                    <div class="category-stats">
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $children_total; ?></div>
                            <div class="category-stat-label">ጠቅላላ</div>
                        </div>
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $total > 0 ? round(($children_total/$total)*100) : 0; ?>%</div>
                            <div class="category-stat-label">መቶኛ</div>
                        </div>
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $male_children; ?></div>
                            <div class="category-stat-label">ወንድ</div>
                        </div>
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $female_children; ?></div>
                            <div class="category-stat-label">ሴት</div>
                        </div>
                    </div>
                </div>

                <div class="category-card youth">
                    <div class="category-title">
                        <span>👦</span> ወጣቶች
                    </div>
                    <div class="category-stats">
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $youth_total; ?></div>
                            <div class="category-stat-label">ጠቅላላ</div>
                        </div>
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $total > 0 ? round(($youth_total/$total)*100) : 0; ?>%</div>
                            <div class="category-stat-label">መቶኛ</div>
                        </div>
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $male_youth; ?></div>
                            <div class="category-stat-label">ወንድ</div>
                        </div>
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $female_youth; ?></div>
                            <div class="category-stat-label">ሴት</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gender Distribution -->
            <div class="section-title" style="margin-top: 30px;">👥 የጾታ ክፍፍል</div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $male; ?></div>
                    <div class="stat-label">ወንድ</div>
                    <div class="stat-percent"><?php echo $total > 0 ? round(($male/$total)*100) : 0; ?>%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $female; ?></div>
                    <div class="stat-label">ሴት</div>
                    <div class="stat-percent"><?php echo $total > 0 ? round(($female/$total)*100) : 0; ?>%</div>
                </div>
            </div>

            <!-- Age Distribution -->
            <div class="section-title" style="margin-top: 30px;">🎂 የዕድሜ ክፍፍል</div>
            <div class="age-grid">
                <div class="age-card children-under7">
                    <div class="age-value"><?php echo $children_under_7; ?></div>
                    <div class="age-label">ህጻናት (ከ7 ዓመት በታች)</div>
                </div>
                <div class="age-card children-7-12">
                    <div class="age-value"><?php echo $children_7_12; ?></div>
                    <div class="age-label">ህጻናት (7-12 ዓመት)</div>
                </div>
                <div class="age-card youth-13-18">
                    <div class="age-value"><?php echo $youth_13_18; ?></div>
                    <div class="age-label">ወጣቶች (13-18 ዓመት)</div>
                </div>
                <div class="age-card youth-over18">
                    <div class="age-value"><?php echo $youth_over_18; ?></div>
                    <div class="age-label">ወጣቶች (ከ18 ዓመት በላይ)</div>
                </div>
            </div>

            <!-- Upgrade Statistics -->
            <?php if($upgraded_count > 0): ?>
            <div class="upgrade-card">
                <div class="upgrade-info">
                    <div class="upgrade-title">⬆️ ከህጻናት ወደ ወጣቶች የተሻሻሉ</div>
                    <div class="upgrade-count"><?php echo $upgraded_count; ?> አባላት</div>
                </div>
                <div style="font-size: 48px;">⬆️</div>
            </div>
            <?php endif; ?>

            <!-- Member Type -->
            <div class="section-title" style="margin-top: 30px;">🏷️ የአባልነት አይነት</div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $candidate; ?></div>
                    <div class="stat-label">እጩ</div>
                    <div class="stat-percent"><?php echo $total > 0 ? round(($candidate/$total)*100) : 0; ?>%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $regular; ?></div>
                    <div class="stat-label">መደበኛ</div>
                    <div class="stat-percent"><?php echo $total > 0 ? round(($regular/$total)*100) : 0; ?>%</div>
                </div>
            </div>

            <!-- Marital Status -->
            <div class="section-title" style="margin-top: 30px;">💍 የጋብቻ ሁኔታ</div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $married; ?></div>
                    <div class="stat-label">ያገቡ</div>
                    <div class="stat-percent"><?php echo $total > 0 ? round(($married/$total)*100) : 0; ?>%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $single; ?></div>
                    <div class="stat-label">ያላገቡ</div>
                    <div class="stat-percent"><?php echo $total > 0 ? round(($single/$total)*100) : 0; ?>%</div>
                </div>
                <?php if($unknown_marital > 0): ?>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $unknown_marital; ?></div>
                    <div class="stat-label">ያልተመዘገበ</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Year Statistics -->
            <div class="section-title" style="margin-top: 30px;">📅 በዓመት ክፍፍል</div>
            <div class="year-grid">
                <?php foreach($year_stats as $year => $count): ?>
                <div class="year-card">
                    <div class="year-number"><?php echo $year; ?></div>
                    <div class="year-count"><?php echo $count; ?> አባላት</div>
                    <div class="year-children">🧒 <?php echo $year_children[$year] ?? 0; ?></div>
                    <div class="year-youth">👦 <?php echo $year_youth[$year] ?? 0; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Missing Fields -->
            <div class="section-title" style="margin-top: 30px;">⚠️ የጎደሉ መረጃዎች</div>
            <div class="missing-grid">
                <div class="missing-card">
                    <div class="missing-title">ስልክ ቁጥር</div>
                    <div class="missing-value"><?php echo $missing_phone; ?></div>
                </div>
                <div class="missing-card">
                    <div class="missing-title">የትውልድ ቀን</div>
                    <div class="missing-value"><?php echo $missing_birth; ?></div>
                </div>
                <div class="missing-card">
                    <div class="missing-title">ጾታ</div>
                    <div class="missing-value"><?php echo $missing_gender; ?></div>
                </div>
                <div class="missing-card">
                    <div class="missing-title">የአደጋ ጊዜ ተጠሪ</div>
                    <div class="missing-value"><?php echo $missing_emergency; ?></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            © <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት | የተሰላበት ቀን: <?php echo date('d/m/Y'); ?>
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
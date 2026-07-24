<?php
// index.php - Main Dashboard with Category Management
require_once 'config.php';

// Get statistics
$total = $conn->query("SELECT COUNT(*) as c FROM members")->fetch_assoc()['c'];
$temporary = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='temporary'")->fetch_assoc()['c'];
$approved = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='approved'")->fetch_assoc()['c'];

// NEW: Category statistics
$children_total = getCategoryCount($conn, 'ህጻናት');
$youth_total = getCategoryCount($conn, 'ወጣቶች');

// Status by category
$children_approved = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ህጻናት' AND status='approved'")->fetch_assoc()['c'];
$children_temporary = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ህጻናት' AND status='temporary'")->fetch_assoc()['c'];
$youth_approved = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ወጣቶች' AND status='approved'")->fetch_assoc()['c'];
$youth_temporary = $conn->query("SELECT COUNT(*) as c FROM members WHERE category='ወጣቶች' AND status='temporary'")->fetch_assoc()['c'];

// Gender statistics
$male = $conn->query("SELECT COUNT(*) as c FROM members WHERE gender IN ('ወ','Male')")->fetch_assoc()['c'];
$female = $conn->query("SELECT COUNT(*) as c FROM members WHERE gender IN ('ሴ','Female')")->fetch_assoc()['c'];

// Get years that have data
$years_with_data = [];
$result = $conn->query("SELECT DISTINCT year FROM members ORDER BY year DESC");
while($row = $result->fetch_assoc()) {
    $years_with_data[] = $row['year'];
}

// Get birthdays this month (Ethiopian)
$current_month = date('m');
$birthdays = $conn->query("SELECT full_name, birth_date, member_id_number, category FROM members WHERE MONTH(birth_date) = $current_month AND birth_date IS NOT NULL AND birth_date != '0000-00-00' ORDER BY DAY(birth_date) ASC LIMIT 5");

// Get recent upgrades
$recent_upgrades = $conn->query("SELECT * FROM members WHERE previous_category='ህጻናት' AND category='ወጣቶች' ORDER BY upgrade_date DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ዋና ገጽ | Dashboard</title>
    <style>
        /* ============================================================
           CERTIFICATE STYLES - Dashboard
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
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            background: linear-gradient(135deg, #FDB931, #F39C12, #C0392B);
            color: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            animation: glow 3s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(218,165,32,0.2); }
            50% { box-shadow: 0 0 40px rgba(218,165,32,0.4); }
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
            font-weight: 500;
            background: rgba(255,255,255,0.15);
            transition: all 0.2s;
            border: 1px solid rgba(255,215,0,0.3);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .nav a:hover, .nav a.active {
            background: #FFD700;
            color: #5D3A1A;
            transform: translateY(-2px);
            border-color: #FFD700;
            font-weight: bold;
        }

        /* Category Summary Cards */
        .category-summary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .category-card {
            border-radius: 15px;
            padding: 20px;
            color: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .category-card.children {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .category-card.youth {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 24px;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding-bottom: 10px;
        }

        .category-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .category-stat {
            text-align: center;
        }

        .category-stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #FFD700;
        }

        .category-stat-label {
            font-size: 13px;
            opacity: 0.9;
        }

        .category-progress {
            margin-top: 15px;
            height: 8px;
            background: rgba(0,0,0,0.2);
            border-radius: 4px;
            overflow: hidden;
        }

        .category-progress-bar {
            height: 100%;
            background: #FFD700;
            border-radius: 4px;
        }

        /* Year Management */
        .year-management {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            margin: 10px 20px;
            border-radius: 15px;
            position: relative;
            z-index: 2;
        }

        .year-title {
            font-size: 18px;
            color: #FFD700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .year-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .year-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .year-btn.active {
            background: #FFD700;
            color: #5D3A1A;
            box-shadow: 0 4px 10px rgba(255,215,0,0.3);
        }

        .year-btn.inactive {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,215,0,0.3);
        }

        .year-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .btn-open {
            background: #27ae60;
            color: white;
            margin-left: auto;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #FFD700;
            transition: transform 0.2s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.25);
        }

        .stat-number {
            font-size: 42px;
            color: #FFD700;
            font-weight: bold;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 16px;
            color: white;
            font-weight: 500;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        /* Quick Actions */
        .quick-actions {
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .section-title {
            font-size: 20px;
            color: #FFD700;
            margin-bottom: 15px;
            border-left: 4px solid #FFD700;
            padding-left: 15px;
        }

        .actions-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .action-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-size: 14px;
            border: 1px solid rgba(255,215,0,0.3);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(5px);
        }

        .action-btn:hover {
            background: #FFD700;
            color: #5D3A1A;
            transform: translateY(-2px);
        }

        /* Birthday Widget */
        .birthday-widget, .upgrade-widget {
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .birthday-list, .upgrade-list {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 15px;
            backdrop-filter: blur(5px);
        }

        .birthday-item, .upgrade-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            border-bottom: 1px solid rgba(255,215,0,0.2);
            cursor: pointer;
        }

        .birthday-item:last-child, .upgrade-item:last-child {
            border-bottom: none;
        }

        .birthday-icon, .upgrade-icon {
            width: 40px;
            height: 40px;
            background: #FFD700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5D3A1A;
            font-size: 20px;
        }

        .birthday-info, .upgrade-info {
            flex: 1;
        }

        .birthday-name, .upgrade-name {
            font-size: 16px;
            font-weight: bold;
            color: white;
        }

        .birthday-id, .upgrade-id {
            font-size: 12px;
            color: #FFD700;
        }

        .birthday-date, .upgrade-date {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
        }

        .category-tag {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }

        .tag-children {
            background: #3498db;
            color: white;
        }

        .tag-youth {
            background: #2ecc71;
            color: white;
        }

        .footer {
            text-align: center;
            padding: 15px;
            font-size: 12px;
            border-top: 1px solid rgba(255,215,0,0.2);
            position: relative;
            z-index: 2;
            color: white;
            background: rgba(0,0,0,0.2);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: linear-gradient(135deg, #FDB931, #F39C12);
            padding: 30px;
            border-radius: 20px;
            max-width: 400px;
            width: 90%;
            color: white;
            position: relative;
        }

        .modal-title {
            font-size: 24px;
            color: #FFD700;
            margin-bottom: 20px;
        }

        .modal-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 16px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            cursor: pointer;
        }

        .modal-btn-primary {
            background: #FFD700;
            color: #5D3A1A;
        }

        .modal-btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .message {
            margin: 15px 20px;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            position: relative;
            z-index: 2;
        }

        .message.success {
            background: rgba(39, 174, 96, 0.3);
            color: #FFD700;
            border-left: 4px solid #27ae60;
        }

        @media (max-width: 768px) {
            .header h1 { font-size: 28px; }
            .header .amharic { font-size: 24px; }
            .category-summary { grid-template-columns: 1fr; }
            .year-buttons { flex-direction: column; }
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
            <div class="amharic">የአባላት አስተዳደር ሥርዓት</div>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php" class="active">ዋና ገጽ</a>
            <a href="members.php">አባላት</a>
            <a href="temporary.php">ጊዜያዊ</a>
            <a href="register.php">አዲስ መዝግብ</a>
            <a href="birthdays.php">ልደት</a>
            <a href="statistics.php">ስታቲስቲክስ</a>
            <a href="excel_import.php">ኤክሴል አምጣ</a>
            <a href="excel_export.php">ኤክሴል ላክ</a>
        </div>

        <!-- Messages -->
        <?php if($message): ?>
        <div class="message <?php echo $msg_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- NEW: Category Summary Cards -->
        <div class="category-summary">
            <div class="category-card children">
                <div class="category-header">
                    <span>🧒</span> ህጻናት
                </div>
                <div class="category-stats">
                    <div class="category-stat">
                        <div class="category-stat-value"><?php echo $children_total; ?></div>
                        <div class="category-stat-label">ጠቅላላ</div>
                    </div>
                    <div class="category-stat">
                        <div class="category-stat-value"><?php echo $children_approved; ?></div>
                        <div class="category-stat-label">የተረጋገጡ</div>
                    </div>
                    <div class="category-stat">
                        <div class="category-stat-value"><?php echo $children_temporary; ?></div>
                        <div class="category-stat-label">ጊዜያዊ</div>
                    </div>
                    <div class="category-stat">
                        <div class="category-stat-value"><?php echo $children_total > 0 ? round(($children_approved/$children_total)*100) : 0; ?>%</div>
                        <div class="category-stat-label">ማጠናቀቅ</div>
                    </div>
                </div>
                <div class="category-progress">
                    <div class="category-progress-bar" style="width: <?php echo $children_total > 0 ? ($children_approved/$children_total)*100 : 0; ?>%;"></div>
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="members.php?category=ህጻናት" style="color: white; text-decoration: none; font-size: 13px;">👥 ሁሉንም ህጻናት ተመልከት →</a>
                </div>
            </div>

            <div class="category-card youth">
                <div class="category-header">
                    <span>👦</span> ወጣቶች
                </div>
                <div class="category-stats">
                    <div class="category-stat">
                        <div class="category-stat-value"><?php echo $youth_total; ?></div>
                        <div class="category-stat-label">ጠቅላላ</div>
                    </div>
                    <div class="category-stat">
                        <div class="category-stat-value"><?php echo $youth_approved; ?></div>
                        <div class="category-stat-label">የተረጋገጡ</div>
                    </div>
                    <div class="category-stat">
                        <div class="category-stat-value"><?php echo $youth_temporary; ?></div>
                        <div class="category-stat-label">ጊዜያዊ</div>
                    </div>
                    <div class="category-stat">
                        <div class="category-stat-value"><?php echo $youth_total > 0 ? round(($youth_approved/$youth_total)*100) : 0; ?>%</div>
                        <div class="category-stat-label">ማጠናቀቅ</div>
                    </div>
                </div>
                <div class="category-progress">
                    <div class="category-progress-bar" style="width: <?php echo $youth_total > 0 ? ($youth_approved/$youth_total)*100 : 0; ?>%;"></div>
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="members.php?category=ወጣቶች" style="color: white; text-decoration: none; font-size: 13px;">👥 ሁሉንም ወጣቶች ተመልከት →</a>
                </div>
            </div>
        </div>

        <!-- Year Management Section -->
        <div class="year-management">
            <div class="year-title">
                <span>📅 ዓመተ ምህረት አስተዳደር</span>
                <span style="margin-left: auto;">አሁን ያለው ዓመት: <strong style="color: #FFD700;"><?php echo $current_year; ?></strong></span>
            </div>
            
            <div class="year-buttons">
                <?php 
                $all_years = [2010,2011,2012,2013,2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025];
                foreach($all_years as $y): 
                    $has_data = in_array($y, $years_with_data);
                ?>
                <a href="members.php?year=<?php echo $y; ?>" 
                   class="year-btn <?php echo $y == $current_year ? 'active' : 'inactive'; ?>"
                   style="<?php echo $has_data ? 'border-left: 3px solid #27ae60;' : ''; ?>">
                    <?php echo $y; ?>
                    <?php if($has_data): ?>
                        <span style="font-size: 10px;">📊</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                
                <button class="year-btn btn-open" onclick="openNewYearModal()">➕ አዲስ ዓመት ክፈት</button>
            </div>
            
            <div style="margin-top: 10px; font-size: 12px; color: rgba(255,255,255,0.7);">
                <span style="color: #27ae60;">🟢</span> መረጃ ያለበት ዓመት | 
                <span style="color: #FFD700;">🟡</span> አሁን ያለው ዓመት
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total; ?></div>
                <div class="stat-label">ጠቅላላ አባላት</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $approved; ?></div>
                <div class="stat-label">የተረጋገጡ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $temporary; ?></div>
                <div class="stat-label">ጊዜያዊ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $male; ?></div>
                <div class="stat-label">ወንድ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $female; ?></div>
                <div class="stat-label">ሴት</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="section-title">⚡ ፈጣን እርምጃዎች</div>
            <div class="actions-grid">
                <a href="register.php" class="action-btn">➕ አዲስ አባል መዝግብ</a>
                <a href="members.php?category=ህጻናት" class="action-btn">🧒 ህጻናት ተመልከት</a>
                <a href="members.php?category=ወጣቶች" class="action-btn">👦 ወጣቶች ተመልከት</a>
                <a href="temporary.php" class="action-btn">⏳ ጊዜያዊ አባላት</a>
                <a href="birthdays.php" class="action-btn">🎂 የወር ልደት</a>
                <a href="statistics.php" class="action-btn">📊 ስታቲስቲክስ</a>
            </div>
        </div>

        <!-- Recent Upgrades Widget -->
        <?php if($recent_upgrades->num_rows > 0): ?>
        <div class="upgrade-widget">
            <div class="section-title">⬆️ በቅርብ የተሻሻሉ</div>
            <div class="upgrade-list">
                <?php while($u = $recent_upgrades->fetch_assoc()): 
                    $age = calculateAge($u['birth_date']);
                ?>
                <div class="upgrade-item" onclick="window.location.href='view.php?id=<?php echo $u['member_id_number']; ?>'">
                    <div class="upgrade-icon">⬆️</div>
                    <div class="upgrade-info">
                        <div class="upgrade-name">
                            <?php echo htmlspecialchars($u['full_name']); ?>
                            <span class="category-tag tag-youth">👦 ወጣቶች</span>
                        </div>
                        <div class="upgrade-id"><?php echo $u['member_id_number']; ?> | ዕድሜ: <?php echo $age; ?></div>
                    </div>
                    <div class="upgrade-date"><?php echo date('d/m/Y', strtotime($u['upgrade_date'])); ?></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Birthdays This Month -->
        <?php if($birthdays->num_rows > 0): ?>
        <div class="birthday-widget">
            <div class="section-title">🎂 የዚህ ወር ልደት</div>
            <div class="birthday-list">
                <?php while($b = $birthdays->fetch_assoc()): 
                    $age = calculateAge($b['birth_date']);
                ?>
                <div class="birthday-item" onclick="window.location.href='view.php?id=<?php echo $b['member_id_number']; ?>'">
                    <div class="birthday-icon">🎉</div>
                    <div class="birthday-info">
                        <div class="birthday-name">
                            <?php echo htmlspecialchars($b['full_name']); ?>
                            <?php if($b['category'] == 'ህጻናት'): ?>
                            <span class="category-tag tag-children">🧒 ህጻናት</span>
                            <?php else: ?>
                            <span class="category-tag tag-youth">👦 ወጣቶች</span>
                            <?php endif; ?>
                        </div>
                        <div class="birthday-id"><?php echo $b['member_id_number']; ?> | ዕድሜ: <?php echo $age; ?></div>
                    </div>
                    <div class="birthday-date"><?php echo date('F j', strtotime($b['birth_date'])); ?></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            © <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት | ሁሉም መብት የተጠበቀ ነው
        </div>
    </div>

    <!-- Modal for Opening New Year -->
    <div id="newYearModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">➕ አዲስ ዓመት ክፈት</div>
            <form action="year_manager.php" method="POST">
                <input type="number" name="new_year" class="modal-input" placeholder="ዓመት (ለምሳሌ 2019)" required min="2010" max="2100">
                
                <div style="margin: 15px 0;">
                    <label>
                        <input type="checkbox" name="copy_from_previous" value="1" checked>
                        ካለፈው ዓመት መረጃ ይቅዳ
                    </label>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal()">ዝጋ</button>
                    <button type="submit" name="open_year" class="modal-btn modal-btn-primary">ክፈት</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openNewYearModal() {
            document.getElementById('newYearModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('newYearModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('newYearModal');
            if(event.target == modal) {
                modal.style.display = 'none';
            }
        }

        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.5s';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
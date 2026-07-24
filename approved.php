<?php
// approved.php - View approved members with category support
require_once 'config.php';

// Get filter parameters
$year = $_GET['year'] ?? 'all';
$category = $_GET['category'] ?? 'all'; // NEW: category filter
$member_type = $_GET['member_type'] ?? 'all';
$marital_status = $_GET['marital_status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM members WHERE status='approved'";
if($year != 'all') {
    $query .= " AND year = " . intval($year);
}
// NEW: Category filter
if($category != 'all') {
    $query .= " AND category = '" . $conn->real_escape_string($category) . "'";
}
if($member_type != 'all') {
    $query .= " AND member_type = '$member_type'";
}
if($marital_status != 'all') {
    $query .= " AND marital_status = '$marital_status'";
}
if(!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (full_name LIKE '%$search%' OR phone LIKE '%$search%')";
}
$query .= " ORDER BY full_name ASC";
$result = $conn->query($query);

// Get stats
$total_approved = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='approved'")->fetch_assoc()['c'];

// Category stats
$children_approved = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='approved' AND category='ህጻናት'")->fetch_assoc()['c'];
$youth_approved = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='approved' AND category='ወጣቶች'")->fetch_assoc()['c'];

// Gender stats
$male_count = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='approved' AND gender IN ('ወ','Male')")->fetch_assoc()['c'];
$female_count = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='approved' AND gender IN ('ሴ','Female')")->fetch_assoc()['c'];

// Member type stats
$candidate_count = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='approved' AND member_type='እጩ'")->fetch_assoc()['c'];
$regular_count = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='approved' AND member_type='መደበኛ'")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>የተረጋገጡ አባላት | Approved Members</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e3a5f 0%, #0a1a2f 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header .amharic {
            font-size: 1.8em;
            color: #28a745;
        }

        .nav {
            background: #1e3a5f;
            padding: 15px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s;
        }

        .nav a:hover, .nav a.active {
            background: #28a745;
            transform: translateY(-2px);
        }

        /* NEW: Category Tabs */
        .category-tabs {
            padding: 15px 20px;
            background: white;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 2px solid #dee2e6;
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
        }

        .category-tab.all {
            background: #6c757d;
            color: white;
        }

        .category-tab.children {
            background: #3498db;
            color: white;
        }

        .category-tab.youth {
            background: #2ecc71;
            color: white;
        }

        .category-tab.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .category-count {
            background: rgba(255,255,255,0.3);
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 5px;
        }

        /* Category Badge */
        .category-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-children {
            background: #3498db;
            color: white;
        }

        .badge-youth {
            background: #2ecc71;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
            background: white;
        }

        .stat-card {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }

        .stat-card.children {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }

        .stat-card.youth {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
        }

        .filter-bar {
            padding: 20px;
            background: white;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            border-top: 1px solid #dee2e6;
        }

        .filter-select, .filter-input {
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            flex: 1 1 200px;
        }

        .export-btns {
            padding: 20px;
            background: white;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .export-btn {
            padding: 12px 25px;
            background: #1e3a5f;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .export-btn:hover {
            background: #28a745;
            transform: translateY(-2px);
        }

        .print-btn {
            background: #17a2b8;
        }

        .table-container {
            padding: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        th {
            background: #28a745;
            color: white;
            padding: 12px;
            position: sticky;
            top: 0;
        }

        td {
            padding: 10px;
            border: 1px solid #dee2e6;
        }

        tr:hover {
            background: #f0f7ff;
        }

        .photo-thumb {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-size: 0.9em;
        }

        .view-btn { background: #17a2b8; }
        .edit-btn { background: #ffc107; color: #000; }
        .print-btn-small { background: #28a745; }
        .upgrade-btn { background: #3498db; }

        .year-badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }

        .footer {
            background: #1e3a5f;
            color: white;
            padding: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>አጸደ ትጉሃን ሰንበት ትምህርት ቤት</h1>
            <div class="amharic">✅ የተረጋገጡ አባላት - Approved Members</div>
        </div>

        <div class="nav">
            <a href="index.php">🏠 ዳሽቦርድ</a>
            <a href="register.php">📝 አዲስ መመዝገብ</a>
            <a href="temporary.php">⏳ ጊዜያዊ</a>
            <a href="approved.php" class="active">✅ የተረጋገጠ</a>
            <a href="members.php">👥 ሁሉም አባላት</a>
            <a href="excel_export.php" class="export-btn" style="margin-left: auto;">📥 ኤክሴል አውርድ</a>
        </div>

        <!-- NEW: Category Tabs -->
        <div class="category-tabs">
            <?php
            $base_url = "approved.php?year=" . urlencode($year);
            if($member_type != 'all') $base_url .= "&member_type=" . urlencode($member_type);
            if($marital_status != 'all') $base_url .= "&marital_status=" . urlencode($marital_status);
            if(!empty($search)) $base_url .= "&search=" . urlencode($search);
            ?>
            
            <a href="<?php echo $base_url; ?>&category=all" class="category-tab all <?php echo $category == 'all' ? 'active' : ''; ?>">
                👥 ሁሉም
                <span class="category-count"><?php echo $total_approved; ?></span>
            </a>
            
            <a href="<?php echo $base_url; ?>&category=ህጻናት" class="category-tab children <?php echo $category == 'ህጻናት' ? 'active' : ''; ?>">
                🧒 ህጻናት
                <span class="category-count"><?php echo $children_approved; ?></span>
            </a>
            
            <a href="<?php echo $base_url; ?>&category=ወጣቶች" class="category-tab youth <?php echo $category == 'ወጣቶች' ? 'active' : ''; ?>">
                👦 ወጣቶች
                <span class="category-count"><?php echo $youth_approved; ?></span>
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_approved; ?></div>
                <div>ጠቅላላ የተረጋገጡ</div>
            </div>
            <div class="stat-card children">
                <div class="stat-value"><?php echo $children_approved; ?></div>
                <div>🧒 ህጻናት</div>
            </div>
            <div class="stat-card youth">
                <div class="stat-value"><?php echo $youth_approved; ?></div>
                <div>👦 ወጣቶች</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                <div class="stat-value"><?php echo $male_count; ?></div>
                <div>ወንድ</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #e83e8c 0%, #c82360 100%);">
                <div class="stat-value"><?php echo $female_count; ?></div>
                <div>ሴት</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #b47c15 100%);">
                <div class="stat-value"><?php echo $candidate_count; ?></div>
                <div>እጩ</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);">
                <div class="stat-value"><?php echo $regular_count; ?></div>
                <div>መደበኛ</div>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <input type="text" name="search" class="filter-input" placeholder="🔍 በስም ወይም ስልክ ፈልግ..." value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="year" class="filter-select">
                <option value="all">ሁሉም ዓመታት</option>
                <?php for($y=2010; $y<=2018; $y++): ?>
                <option value="<?php echo $y; ?>" <?php echo $year==$y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>

            <select name="member_type" class="filter-select">
                <option value="all">ሁሉም አይነት</option>
                <option value="እጩ" <?php echo $member_type=='እጩ' ? 'selected' : ''; ?>>እጩ</option>
                <option value="መደበኛ" <?php echo $member_type=='መደበኛ' ? 'selected' : ''; ?>>መደበኛ</option>
            </select>

            <select name="marital_status" class="filter-select">
                <option value="all">ሁሉም የጋብቻ ሁኔታ</option>
                <option value="ያገባ" <?php echo $marital_status=='ያገባ' ? 'selected' : ''; ?>>ያገባ</option>
                <option value="ያላገባ" <?php echo $marital_status=='ያላገባ' ? 'selected' : ''; ?>>ያላገባ</option>
            </select>

            <button type="submit" class="export-btn" style="background: #1e3a5f;">🔍 አጣራ</button>
            <a href="approved.php<?php echo $category != 'all' ? '?category='.urlencode($category) : ''; ?>" class="export-btn" style="background: #6c757d;">🔄 ዳግም አስጀምር</a>
        </form>

        <div class="export-btns">
            <a href="excel_export.php?status=approved&year=<?php echo $year; ?>&category=<?php echo urlencode($category); ?>" class="export-btn">
                📥 ኤክሴል አውርድ (የተጣራ)
            </a>
            <a href="print.php?status=approved&year=<?php echo $year; ?>&category=<?php echo urlencode($category); ?>" class="export-btn print-btn" target="_blank">
                🖨️ አትም (የተጣራ)
            </a>
            <a href="print_cards.php?status=approved&category=<?php echo urlencode($category); ?>" class="export-btn" style="background: #6f42c1;">
                🃏 ካርድ አትም
            </a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>📸</th>
                        <th>📝 ሙሉ ስም</th>
                        <th>⛪ ክርስትና ስም</th>
                        <th>👥 ምድብ</th> <!-- NEW -->
                        <th>📞 ስልክ</th>
                        <th>🎂 ልደት</th>
                        <th>⚥ ጾታ</th>
                        <th>🏷️ አይነት</th>
                        <th>💍 ጋብቻ</th>
                        <th>📚 ትምህርት</th>
                        <th>💼 ሙያ</th>
                        <th>📅 ዓመት</th>
                        <th>⚡ ድርጊት</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $age = calculateAge($row['birth_date']);
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <?php if(!empty($row['photo'])): ?>
                                    <img src="uploads/student_photos/<?php echo $row['photo']; ?>" class="photo-thumb">
                                <?php else: ?>
                                    <div style="width:40px;height:40px;background:#dee2e6;border-radius:50%;display:flex;align-items:center;justify-content:center;">📸</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['christian_name'] ?? ''); ?></td>
                            <td>
                                <?php 
                                if($row['category'] == 'ህጻናት') {
                                    echo '<span class="category-badge badge-children">🧒 ህጻናት</span>';
                                } else {
                                    echo '<span class="category-badge badge-youth">👦 ወጣቶች</span>';
                                }
                                echo '<br><small>' . $age . ' ዓመት</small>';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                            <td><?php echo !empty($row['birth_date']) ? date('d/m/Y', strtotime($row['birth_date'])) : ''; ?></td>
                            <td><?php echo $row['gender']; ?></td>
                            <td><span class="year-badge"><?php echo $row['member_type']; ?></span></td>
                            <td><?php echo $row['marital_status']; ?></td>
                            <td><?php echo htmlspecialchars($row['education_level'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['profession'] ?? ''); ?></td>
                            <td><span class="year-badge"><?php echo $row['year']; ?></span></td>
                            <td>
                                <a href="view.php?id=<?php echo $row['member_id_number']; ?>" class="action-btn view-btn">👁️</a>
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">✏️</a>
                                <?php if($row['category'] == 'ህጻናት'): ?>
                                <a href="upgrade.php?id=<?php echo $row['id']; ?>" class="action-btn upgrade-btn" onclick="return confirm('ወደ ወጣቶች ማሻሻል እርግጠኛ ነህ?')">⬆️</a>
                                <?php endif; ?>
                                <a href="print.php?id=<?php echo $row['id']; ?>" class="action-btn print-btn-small" target="_blank">🖨️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="14" style="text-align: center; padding: 50px;">
                                <h3>😢 ምንም የተረጋገጡ አባላት የሉም</h3>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>© <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት | የተረጋገጡ አባላት: <?php echo $total_approved; ?></p>
        </div>
    </div>
</body>
</html>
<?php
// temporary.php - Shows ONLY temporary members with category support
require_once 'config.php';

// Handle approve
if(isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $conn->query("UPDATE members SET status='approved' WHERE id=$id");
    $_SESSION['message'] = "አባል በተሳካ ሁኔታ ጸድቋል!";
    header("Location: temporary.php");
    exit();
}

// Handle delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $photo = $conn->query("SELECT photo FROM members WHERE id=$id")->fetch_assoc();
    if($photo && !empty($photo['photo'])) {
        $path = "uploads/student_photos/" . $photo['photo'];
        if(file_exists($path)) unlink($path);
    }
    $conn->query("DELETE FROM members WHERE id=$id");
    $_SESSION['message'] = "አባል በተሳካ ሁኔታ ተሰርዟል!";
    header("Location: temporary.php");
    exit();
}

// Get filter
$year = $_GET['year'] ?? 'all';
$category = $_GET['category'] ?? 'all'; // NEW: category filter
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM members WHERE status='temporary'";
if($year != 'all') $query .= " AND year=" . intval($year);
if($category != 'all') $query .= " AND category='" . $conn->real_escape_string($category) . "'"; // NEW
if(!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (full_name LIKE '%$search%' OR phone LIKE '%$search%')";
}
$query .= " ORDER BY id DESC";
$result = $conn->query($query);

// Get counts by category for tabs
$children_temp = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='temporary' AND category='ህጻናት'")->fetch_assoc()['c'];
$youth_temp = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='temporary' AND category='ወጣቶች'")->fetch_assoc()['c'];
$total_temp = $conn->query("SELECT COUNT(*) as c FROM members WHERE status='temporary'")->fetch_assoc()['c'];

$message = $_SESSION['message'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['message'], $_SESSION['msg_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ጊዜያዊ አባላት | Temporary</title>
    <style>
        /* Same certificate CSS as members.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Noto Sans Ethiopic', serif; background: linear-gradient(135deg, #8B4513, #A52A2A, #DAA520); padding: 20px; }
        
        .container { max-width: 1400px; margin: 0 auto; background: linear-gradient(135deg, #FFD700, #FF8C00, #B22222); border-radius: 20px; color: white; position: relative; overflow: hidden; animation: glow 3s ease-in-out infinite; }
        .container::before { content: ''; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 2px double rgba(255,255,255,0.5); border-radius: 15px; }
        @keyframes glow { 0%,100% { box-shadow: 0 0 20px rgba(218,165,32,0.3); } 50% { box-shadow: 0 0 40px rgba(218,165,32,0.6); } }
        
        .header { text-align: center; padding: 30px; position: relative; z-index: 2; }
        .church-logo { width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; border: 4px solid white; overflow: hidden; }
        .church-logo img { width: 100%; height: 100%; object-fit: cover; }
        .header h1 { font-size: 36px; color: white; text-transform: uppercase; letter-spacing: 3px; }
        .header .amharic { font-size: 32px; color: #FFD700; border-bottom: 2px solid #FFD700; display: inline-block; }
        
        .nav { display: flex; flex-wrap: wrap; gap: 5px; padding: 10px 20px; background: rgba(0,0,0,0.2); position: relative; z-index: 2; }
        .nav a { color: white; text-decoration: none; padding: 6px 12px; border-radius: 15px; font-size: 13px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,215,0,0.3); }
        .nav a:hover, .nav a.active { background: #FFD700; color: #8B4513; }
        
        /* NEW: Category Tabs */
        .category-tabs {
            padding: 10px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background: rgba(0,0,0,0.15);
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
        }
        
        .category-tab.all {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .category-tab.children {
            background: rgba(52, 152, 219, 0.3);
            color: white;
            border: 1px solid #3498db;
        }
        
        .category-tab.youth {
            background: rgba(46, 204, 113, 0.3);
            color: white;
            border: 1px solid #2ecc71;
        }
        
        .category-tab.active.all {
            background: #FFD700;
            color: #8B4513;
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
        }
        
        .badge-children {
            background: #3498db;
            color: white;
        }
        
        .badge-youth {
            background: #2ecc71;
            color: white;
        }
        
        .filter-bar { padding: 15px 20px; display: flex; flex-wrap: wrap; gap: 10px; }
        .filter-input { padding: 8px 12px; border: 1px solid rgba(255,215,0,0.3); border-radius: 20px; background: rgba(255,255,255,0.1); color: white; flex: 1; }
        .filter-input::placeholder { color: rgba(255,255,255,0.7); }
        .search-btn { padding: 8px 20px; background: #FFD700; color: #8B4513; border: none; border-radius: 20px; cursor: pointer; }
        .reset-btn { padding: 8px 20px; background: rgba(255,255,255,0.15); color: white; text-decoration: none; border-radius: 20px; border: 1px solid rgba(255,215,0,0.3); }
        
        .table-container { padding: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1200px; background: rgba(255,255,255,0.05); }
        th { background: rgba(0,0,0,0.3); color: #FFD700; padding: 12px; font-size: 13px; }
        td { padding: 10px; border-bottom: 1px solid rgba(255,215,0,0.2); color: white; }
        
        /* Yellow highlight for missing fields */
        td.missing { background: rgba(255, 193, 7, 0.2); position: relative; }
        td.missing::after { content: '⚠️'; position: absolute; right: 2px; top: 2px; font-size: 10px; }
        
        .photo-thumb { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #FFD700; }
        .action-btns { display: flex; gap: 3px; flex-wrap: wrap; }
        .action-btn { padding: 4px 8px; border-radius: 12px; font-size: 11px; text-decoration: none; color: white; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,215,0,0.3); }
        .action-btn:hover { background: #FFD700; color: #8B4513; }
        .approve-btn { background: rgba(40, 167, 69, 0.3); }
        .delete-btn { background: rgba(220, 53, 69, 0.3); }
        .upgrade-btn { background: rgba(52, 152, 219, 0.3); }
        
        .year-badge { background: #FFD700; color: #8B4513; padding: 3px 8px; border-radius: 12px; font-size: 11px; }
        
        .footer { text-align: center; padding: 15px; border-top: 1px solid rgba(255,215,0,0.3); font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="church-logo"><img src="images/icon.png" alt="Logo"></div>
            <h1>አጸደ ትጉሃን ሰንበት ትምህርት ቤት</h1>
            <div class="amharic">ጊዜያዊ አባላት</div>
        </div>

        <div class="nav">
            <a href="index.php">ዋና ገጽ</a>
            <a href="members.php">አባላት</a>
            <a href="temporary.php" class="active">ጊዜያዊ</a>
            <a href="register.php">አዲስ መዝግብ</a>
        </div>

        <!-- NEW: Category Tabs -->
        <div class="category-tabs">
            <?php
            $base_url = "temporary.php?year=" . urlencode($year);
            if(!empty($search)) $base_url .= "&search=" . urlencode($search);
            ?>
            
            <a href="<?php echo $base_url; ?>&category=all" class="category-tab all <?php echo $category == 'all' ? 'active' : ''; ?>">
                👥 ሁሉም ጊዜያዊ
                <span class="category-count"><?php echo $total_temp; ?></span>
            </a>
            
            <a href="<?php echo $base_url; ?>&category=ህጻናት" class="category-tab children <?php echo $category == 'ህጻናት' ? 'active' : ''; ?>">
                🧒 ህጻናት
                <span class="category-count"><?php echo $children_temp; ?></span>
            </a>
            
            <a href="<?php echo $base_url; ?>&category=ወጣቶች" class="category-tab youth <?php echo $category == 'ወጣቶች' ? 'active' : ''; ?>">
                👦 ወጣቶች
                <span class="category-count"><?php echo $youth_temp; ?></span>
            </a>
        </div>

        <?php if($message): ?>
        <div style="margin:15px 20px; padding:10px; background:<?php echo $msg_type=='success'?'rgba(40,167,69,0.2)':'rgba(220,53,69,0.2)'; ?>; border-left:4px solid <?php echo $msg_type=='success'?'#28a745':'#dc3545'; ?>;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <form method="GET" class="filter-bar">
            <?php if($category != 'all'): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <?php endif; ?>
            
            <input type="text" name="search" class="filter-input" placeholder="ስም ወይም ስልክ ፈልግ..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">ፈልግ</button>
            <a href="temporary.php<?php echo $category != 'all' ? '?category='.urlencode($category) : ''; ?>" class="reset-btn">አጽዳ</a>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>መታወቂያ</th>
                        <th>ፎቶ</th>
                        <th>ሙሉ ስም</th>
                        <th>ምድብ</th> <!-- NEW -->
                        <th>ክርስትና ስም</th>
                        <th>ስልክ</th>
                        <th>ልደት</th>
                        <th>ዕድሜ</th> <!-- NEW -->
                        <th>ጾታ</th>
                        <th>ዓመት</th>
                        <th>ድርጊት</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): 
                        $missing = [];
                        if(empty($row['phone']) && $row['category'] == 'ወጣቶች') $missing[] = 'ስልክ'; // Only required for youth
                        if(empty($row['birth_date']) || $row['birth_date']=='0000-00-00') $missing[] = 'ልደት';
                        if(empty($row['gender'])) $missing[] = 'ጾታ';
                        
                        $age = calculateAge($row['birth_date']);
                    ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td>
                            <?php if(!empty($row['photo'])): ?>
                                <img src="uploads/student_photos/<?php echo $row['photo']; ?>" class="photo-thumb">
                            <?php else: ?>
                                <div class="no-photo">📸</div>
                            <?php endif; ?>
                        </td>
                        <td class="<?php echo in_array('ስም', $missing) ? 'missing' : ''; ?>">
                            <?php echo htmlspecialchars($row['full_name']); ?>
                            <?php if(!empty($missing)): ?>
                                <span style="background:#FFD700; color:#8B4513; padding:2px 5px; border-radius:10px; font-size:10px; margin-left:5px;">
                                    <?php echo count($missing); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if($row['category'] == 'ህጻናት') {
                                echo '<span class="category-badge badge-children">🧒 ህጻናት</span>';
                            } else {
                                echo '<span class="category-badge badge-youth">👦 ወጣቶች</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['christian_name'] ?? ''); ?></td>
                        <td class="<?php echo (in_array('ስልክ', $missing) && $row['category'] == 'ወጣቶች') ? 'missing' : ''; ?>">
                            <?php echo htmlspecialchars($row['phone'] ?? ''); ?>
                        </td>
                        <td class="<?php echo in_array('ልደት', $missing) ? 'missing' : ''; ?>">
                            <?php echo !empty($row['birth_date']) ? date('d/m/Y', strtotime($row['birth_date'])) : ''; ?>
                        </td>
                        <td><?php echo $age; ?></td>
                        <td class="<?php echo in_array('ጾታ', $missing) ? 'missing' : ''; ?>"><?php echo $row['gender'] ?? ''; ?></td>
                        <td><span class="year-badge"><?php echo $row['year']; ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="view.php?id=<?php echo $row['member_id_number']; ?>" class="action-btn">ተመልከት</a>
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="action-btn">አርትዕ</a>
                                <a href="?approve=<?php echo $row['id']; ?>" class="action-btn approve-btn" onclick="return confirm('ማጽደቅ እርግጠኛ ነህ?')">አጽድቅ</a>
                                <?php if($row['category'] == 'ህጻናት'): ?>
                                <a href="upgrade.php?id=<?php echo $row['id']; ?>" class="action-btn upgrade-btn" onclick="return confirm('ወደ ወጣቶች ማሻሻል እርግጠኛ ነህ?')">⬆️ አሻሽል</a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $row['id']; ?>" class="action-btn delete-btn" onclick="return confirm('መሰረዝ እርግጠኛ ነህ?')">ሰርዝ</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if($result->num_rows == 0): ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 50px;">
                            <h3 style="color: #FFD700;">😢 ምንም ጊዜያዊ አባላት የሉም</h3>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">© <?php echo date('Y'); ?> አጸደ ትጉሃን ሰንበት ትምህርት ቤት</div>
    </div>
</body>
</html>